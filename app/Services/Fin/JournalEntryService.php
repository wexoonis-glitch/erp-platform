<?php
namespace App\Services\Fin;
use App\Events\EventBus;
use App\Models\FinAccount;
use App\Models\FinAccountingPeriod;
use App\Models\FinJournalEntry;
use App\Models\FinJournalEntryLine;
use App\Services\Platform\AuditService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

final class JournalEntryService {
    public function __construct(private readonly AuditService $audit, private readonly EventBus $bus) {}

    public function create(array $data, array $lines): FinJournalEntry {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new LogicException('Tenant yok.');

        if (FinAccountingPeriod::isLocked($data['entry_date'])) {
            throw ValidationException::withMessages(['entry_date' => 'Bu tarih kilitli bir döneme ait. Geçmiş dönemlere kayıt atılamaz.']);
        }

        $this->validateEntry($data, $lines);

        return DB::transaction(function () use ($data, $lines, $tenantId) {
            $entry = FinJournalEntry::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'entry_no' => $this->nextEntryNo($tenantId, $data['entry_date']),
                'entry_date' => $data['entry_date'], 'description' => $data['description'], 'status' => 'draft',
            ]);
            foreach ($lines as $i => $line) {
                FinJournalEntryLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'], 'debit' => $line['debit'] ?? 0, 'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null, 'line_no' => $i + 1,
                ]);
            }
            return $entry->fresh();
        });
    }

    public function post(string $id): FinJournalEntry {
        $entry = FinJournalEntry::findOrFail($id);
        if ($entry->status !== 'draft') throw ValidationException::withMessages(['status' => "Sadece draft post edilebilir."]);
        if (FinAccountingPeriod::isLocked($entry->entry_date->toDateString())) throw ValidationException::withMessages(['status' => 'Kilitli dönem.']);
        if (!$entry->isBalanced()) throw ValidationException::withMessages(['balance' => 'Borç = Alacak değil.']);

        $entry->update(['status' => 'posted', 'posted_at' => now(), 'posted_by' => \Auth::id()]);
        $this->audit->record(['tenant_id' => $entry->tenant_id, 'entity' => 'fin_journal_entry', 'entity_id' => $entry->id, 'action' => 'update', 'before_state' => ['status' => 'draft'], 'after_state' => ['status' => 'posted']]);
        $this->bus->publish('fin.journal.posted', ['id' => $entry->id, 'entry_no' => $entry->entry_no, 'total' => $entry->totalDebit()], $entry->tenant_id);

        return $entry->fresh();
    }

    public function reverse(string $id, ?string $reason = null): FinJournalEntry {
        $original = FinJournalEntry::findOrFail($id);
        if ($original->status !== 'posted') throw ValidationException::withMessages(['status' => 'Sadece posted kayıtlar ters kayıt edilebilir.']);
        if (FinAccountingPeriod::isLocked(now()->toDateString())) throw ValidationException::withMessages(['status' => 'Kilitli dönem.']);

        return DB::transaction(function () use ($original, $reason) {
            $reversal = FinJournalEntry::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $original->tenant_id,
                'entry_no' => $this->nextEntryNo($original->tenant_id, now()->toDateString()),
                'entry_date' => now()->toDateString(), 'description' => 'REVERSAL: ' . $original->description . ($reason ? " — {$reason}" : ''),
                'status' => 'posted', 'posted_at' => now(), 'posted_by' => \Auth::id(), 'reverses_entry_id' => $original->id,
            ]);
            foreach ($original->lines as $i => $line) {
                FinJournalEntryLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $original->tenant_id, 'journal_entry_id' => $reversal->id,
                    'account_id' => $line->account_id, 'debit' => $line->credit, 'credit' => $line->debit,
                    'description' => $line->description, 'line_no' => $i + 1,
                ]);
            }
            $original->update(['status' => 'reversed']);
            $this->bus->publish('fin.journal.reversed', ['original_id' => $original->id, 'reversal_id' => $reversal->id], $original->tenant_id);
            return $reversal->fresh();
        });
    }

    public function delete(string $id): void {
        $entry = FinJournalEntry::findOrFail($id);
        if ($entry->status !== 'draft') throw ValidationException::withMessages(['status' => 'Sadece draft silinebilir.']);
        $entry->delete();
    }

    private function validateEntry(array $data, array $lines): void {
        if (empty($data['entry_date'])) throw ValidationException::withMessages(['entry_date' => 'Tarih zorunlu.']);
        if (empty(trim($data['description'] ?? ''))) throw ValidationException::withMessages(['description' => 'Açıklama zorunlu.']);
        if (count($lines) < 2) throw ValidationException::withMessages(['lines' => 'En az 2 satır olmalı.']);
        $totalDebit = 0; $totalCredit = 0;
        foreach ($lines as $i => $line) {
            $debit = (float) ($line['debit'] ?? 0); $credit = (float) ($line['credit'] ?? 0);
            if ($debit < 0 || $credit < 0) throw ValidationException::withMessages(["lines.{$i}" => 'Negatif tutar olamaz.']);
            if ($debit > 0 && $credit > 0) throw ValidationException::withMessages(["lines.{$i}" => 'Ya borç ya alacak.']);
            if ($debit == 0 && $credit == 0) throw ValidationException::withMessages(["lines.{$i}" => 'Tutar boş olamaz.']);
            $account = FinAccount::find($line['account_id']);
            if (!$account || !$account->is_postable || $account->status !== 'active') throw ValidationException::withMessages(["lines.{$i}" => 'Hesap geçersiz veya postable değil.']);
            $totalDebit += $debit; $totalCredit += $credit;
        }
        if (abs($totalDebit - $totalCredit) > 0.0001) throw ValidationException::withMessages(['balance' => "Borç ({$totalDebit}) ≠ Alacak ({$totalCredit})"]);
    }

    private function nextEntryNo(string $tenantId, string $date): string {
        $year = date('Y', strtotime($date)); $prefix = "JE-{$year}-";
        $last = FinJournalEntry::where('tenant_id', $tenantId)->where('entry_no', 'like', $prefix . '%')->orderByDesc('entry_no')->value('entry_no');
        $seq = 1;
        if ($last) { $parts = explode('-', $last); $seq = (int) end($parts) + 1; }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
