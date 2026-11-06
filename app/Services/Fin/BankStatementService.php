<?php
namespace App\Services\Fin;

use App\Models\FinBankTransaction;
use App\Models\SysBankStatement;
use App\Models\SysBankStatementLine;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BankStatementService
{
    /**
     * CSV içe aktar: value_date;amount;description;reference
     * Örnek: 2026-10-01;-1500.00;Kira odemesi;TR123
     */
    public function importCsv(string $bankAccountId, UploadedFile $file): SysBankStatement
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw ValidationException::withMessages(['file' => 'Dosya okunamadı.']);
        }

        $rows = [];
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $contents);
        rewind($handle);
        $lineNo = 0;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lineNo++;
            if ($lineNo === 1 && !is_numeric(str_replace(',', '.', $row[1] ?? ''))) {
                continue; // başlık satırı
            }
            if (count($row) < 2) continue;
            $rows[] = [
                'date'   => trim($row[0] ?? ''),
                'amount' => trim(str_replace(',', '.', $row[1] ?? '0')),
                'desc'   => trim($row[2] ?? ''),
                'ref'    => trim($row[3] ?? ''),
            ];
        }
        fclose($handle);

        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'Dosyada geçerli satır bulunamadı.']);
        }

        return $this->persist($tenantId, $bankAccountId, $file, 'csv', $rows);
    }

    /**
     * Basit MT940 içe aktar. :61: satırlarından tarih+tutar, :86: satırlarından açıklama çeker.
     */
    public function importMt940(string $bankAccountId, UploadedFile $file): SysBankStatement
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw ValidationException::withMessages(['file' => 'Dosya okunamadı.']);
        }

        $lines = preg_split("/\r\n|\n|\r/", $contents);
        $rows = [];
        $pendingDate = null;
        $pendingAmount = null;

        foreach ($lines as $raw) {
            $line = trim($raw);
            if (str_starts_with($line, ':61:')) {
                // :61:YYMMDD[YYMMDD]C/D[amount]
                if (preg_match('/^:61:(\d{6})(?:\d{4})?(C|D|CR|DR)([0-9.,]+)/', $line, $m)) {
                    $yy = (int) substr($m[1], 0, 2);
                    $mm = (int) substr($m[1], 2, 2);
                    $dd = (int) substr($m[1], 4, 2);
                    $year = 2000 + $yy;
                    $pendingDate = sprintf('%04d-%02d-%02d', $year, $mm, $dd);
                    $amount = (float) str_replace(',', '.', $m[3]);
                    $isDebit = str_starts_with($m[2], 'D');
                    $pendingAmount = $isDebit ? -$amount : $amount;
                }
            } elseif (str_starts_with($line, ':86:') && $pendingDate !== null && $pendingAmount !== null) {
                $desc = trim(substr($line, 4));
                $rows[] = ['date' => $pendingDate, 'amount' => (string) $pendingAmount, 'desc' => $desc, 'ref' => ''];
                $pendingDate = null;
                $pendingAmount = null;
            }
        }

        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'MT940 dosyasında :61:/:86: satırları bulunamadı.']);
        }

        return $this->persist($tenantId, $bankAccountId, $file, 'mt940', $rows);
    }

    private function persist(string $tenantId, string $bankAccountId, UploadedFile $file, string $format, array $rows): SysBankStatement
    {
        $storedPath = $file->storeAs(
            "statements/{$tenantId}",
            Str::uuid() . '.' . $file->getClientOriginalExtension(),
            'local'
        );

        return DB::transaction(function () use ($tenantId, $bankAccountId, $file, $format, $rows, $storedPath) {
            $dates = array_filter(array_map(fn($r) => $r['date'] ?: null, $rows));
            sort($dates);

            $statement = SysBankStatement::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'bank_account_id' => $bankAccountId,
                'file_name' => $file->getClientOriginalName(),
                'format' => $format, 'file_path' => $storedPath,
                'statement_from' => $dates[0] ?? null,
                'statement_to' => $dates ? end($dates) : null,
                'total_lines' => count($rows),
                'matched_lines' => 0,
                'status' => 'imported',
            ]);

            $no = 0;
            foreach ($rows as $r) {
                $no++;
                try { $valueDate = Carbon::parse($r['date'])->toDateString(); }
                catch (\Throwable) { continue; }
                $amount = (float) $r['amount'];
                if ($amount == 0.0) continue;

                SysBankStatementLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                    'statement_id' => $statement->id, 'line_no' => $no,
                    'value_date' => $valueDate, 'amount' => $amount,
                    'description' => $r['desc'] ?: null,
                    'reference' => $r['ref'] ?: null,
                    'status' => 'unmatched',
                ]);
            }

            return $statement->fresh();
        });
    }

    /**
     * Otomatik eşleştirme: aynı banka hesabında, aynı mutlak tutarda, ±3 gün içinde
     * eşleşmemiş bir fin_bank_transaction bul.
     */
    public function autoMatch(string $statementId): array
    {
        $tenantId = TenantContext::id();
        $statement = SysBankStatement::findOrFail($statementId);

        $matched = 0; $unmatched = 0;

        DB::transaction(function () use ($statement, &$matched, &$unmatched) {
            $lines = SysBankStatementLine::where('statement_id', $statement->id)
                ->where('status', 'unmatched')
                ->get();

            foreach ($lines as $line) {
                $absAmount = abs((float) $line->amount);
                $date = $line->value_date;

                // Eşleşme: aynı banka hesabı + aynı tutar (abs) + ±3 gün + daha önce eşleşmemiş
                $candidates = FinBankTransaction::where('bank_account_id', $statement->bank_account_id)
                    ->whereNotIn('id', function ($q) use ($statement) {
                        $q->select('matched_transaction_id')
                            ->from('sys_bank_statement_line')
                            ->where('tenant_id', $statement->tenant_id)
                            ->whereNotNull('matched_transaction_id');
                    })
                    ->whereBetween('transaction_date', [
                        $date->copy()->subDays(3)->toDateString(),
                        $date->copy()->addDays(3)->toDateString(),
                    ])
                    ->where(function ($q) use ($absAmount) {
                        $q->whereRaw('ABS(amount::numeric) = ?', [$absAmount])
                          ->orWhereRaw('ABS(amount::numeric) BETWEEN ? AND ?', [$absAmount - 0.01, $absAmount + 0.01]);
                    })
                    ->get();

                // En yakın tarihli olanı seç
                $best = $candidates->sortBy(fn($c) => abs($c->transaction_date->diffInDays($date)))->first();

                if ($best) {
                    $line->update([
                        'status' => 'matched',
                        'matched_transaction_id' => $best->id,
                        'matched_at' => now(),
                    ]);
                    $matched++;
                } else {
                    $unmatched++;
                }
            }

            $statement->update([
                'matched_lines' => SysBankStatementLine::where('statement_id', $statement->id)->where('status', 'matched')->count(),
                'status' => $unmatched === 0 ? 'reconciled' : 'partially_reconciled',
            ]);
        });

        return ['matched' => $matched, 'unmatched' => $unmatched];
    }

    public function manualMatch(string $lineId, string $transactionId): void
    {
        $line = SysBankStatementLine::findOrFail($lineId);
        if ($line->status === 'matched') {
            throw ValidationException::withMessages(['status' => 'Bu satır zaten eşleştirilmiş.']);
        }

        $exists = SysBankStatementLine::where('matched_transaction_id', $transactionId)->exists();
        if ($exists) {
            throw ValidationException::withMessages(['transaction' => 'Bu banka hareketi başka bir satırla eşleştirilmiş.']);
        }

        $line->update([
            'status' => 'matched',
            'matched_transaction_id' => $transactionId,
            'matched_at' => now(),
        ]);

        $stmt = $line->statement;
        $stmt->update([
            'matched_lines' => SysBankStatementLine::where('statement_id', $stmt->id)->where('status', 'matched')->count(),
            'status' => SysBankStatementLine::where('statement_id', $stmt->id)->where('status', 'unmatched')->count() === 0 ? 'reconciled' : 'partially_reconciled',
        ]);
    }

    public function unmatch(string $lineId): void
    {
        $line = SysBankStatementLine::findOrFail($lineId);
        $line->update(['status' => 'unmatched', 'matched_transaction_id' => null, 'matched_at' => null]);

        $stmt = $line->statement;
        $stmt->update([
            'matched_lines' => SysBankStatementLine::where('statement_id', $stmt->id)->where('status', 'matched')->count(),
            'status' => 'partially_reconciled',
        ]);
    }

    public function ignore(string $lineId): void
    {
        $line = SysBankStatementLine::findOrFail($lineId);
        $line->update(['status' => 'ignored', 'matched_transaction_id' => null, 'matched_at' => null]);
    }

    public function delete(string $id): void
    {
        $stmt = SysBankStatement::findOrFail($id);
        if ($stmt->file_path && Storage::disk('local')->exists($stmt->file_path)) {
            Storage::disk('local')->delete($stmt->file_path);
        }
        $stmt->delete();
    }
}
