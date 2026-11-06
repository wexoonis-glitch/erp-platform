<?php
namespace App\Services\Fin;
use App\Models\FinAccount;
use App\Models\FinArInvoice;
use App\Models\FinArReceipt;
use App\Models\FinArReceiptAllocation;
use App\Models\FinCustomer;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ArReceiptService {
    public function create(array $data, array $allocations): FinArReceipt {
        $tenantId = TenantContext::id();
        $this->validate($data, $allocations);

        $customer = FinCustomer::find($data['customer_id']);
        if (!$customer) throw ValidationException::withMessages(['customer_id' => 'Müşteri bulunamadı.']);

        $allocTotal = array_sum(array_map(fn($a) => (float) $a['amount'], $allocations));
        if (abs($allocTotal - (float) $data['amount']) > 0.0001) {
            throw ValidationException::withMessages(['amount' => "Tahsilat tutarı (" . number_format((float) $data['amount'], 2) . ") ≠ dağıtım toplamı (" . number_format($allocTotal, 2) . ")"]);
        }

        return DB::transaction(function () use ($data, $allocations, $tenantId) {
            $receipt = FinArReceipt::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'customer_id' => $data['customer_id'], 'bank_account_id' => $data['bank_account_id'],
                'receipt_no' => $this->nextReceiptNo($tenantId, $data['receipt_date']),
                'receipt_date' => $data['receipt_date'], 'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'TRY', 'method' => $data['method'] ?? 'bank',
                'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
                'status' => 'draft',
            ]);

            foreach ($allocations as $alloc) {
                FinArReceiptAllocation::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                    'receipt_id' => $receipt->id, 'invoice_id' => $alloc['invoice_id'], 'amount' => $alloc['amount'],
                ]);
            }
            return $receipt->fresh();
        });
    }

    public function post(string $id): FinArReceipt {
        $receipt = FinArReceipt::findOrFail($id);
        if ($receipt->status !== 'draft') throw ValidationException::withMessages(['status' => "Sadece draft tahsilat post edilebilir."]);

        $arAccount = FinAccount::where('code', '120')->first();
        if (!$arAccount) throw ValidationException::withMessages(['accounts' => '120 Alıcılar hesabı eksik.']);

        return DB::transaction(function () use ($receipt, $arAccount) {
            // 1. Yevmiye: Borç Banka/Kasa, Alacak Alıcılar
            $entryLines = [
                ['account_id' => $receipt->bank_account_id, 'debit' => (float)$receipt->amount, 'credit' => 0, 'description' => "Tahsilat {$receipt->receipt_no}"],
                ['account_id' => $arAccount->id, 'debit' => 0, 'credit' => (float)$receipt->amount, 'description' => "Müşteri Tahsilatı {$receipt->receipt_no}"],
            ];

            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create([
                'entry_date' => $receipt->receipt_date->toDateString(),
                'description' => "AR Tahsilatı {$receipt->receipt_no} - {$receipt->customer->name}",
            ], $entryLines);
            $journalService->post($entry->id);

            // 2. Faturaları güncelle
            foreach ($receipt->allocations as $alloc) {
                $invoice = FinArInvoice::find($alloc->invoice_id);
                if (!$invoice) continue;

                $newPaid = (float) $invoice->paid_total + (float) $alloc->amount;
                $newStatus = $newPaid >= (float) $invoice->grand_total - 0.0001 ? 'paid' : 'partially_paid';

                $invoice->update(['paid_total' => $newPaid, 'status' => $newStatus]);
            }

            // 3. Tahsilatı güncelle
            $receipt->update(['status' => 'posted', 'journal_entry_id' => $entry->id]);
            return $receipt->fresh();
        });
    }

    public function delete(string $id): void {
        $receipt = FinArReceipt::findOrFail($id);
        if ($receipt->status !== 'draft') throw ValidationException::withMessages(['status' => 'Sadece draft silinebilir.']);
        $receipt->delete();
    }

    private function validate(array $data, array $allocations): void {
        if (empty($data['customer_id'])) throw ValidationException::withMessages(['customer_id' => 'Müşteri zorunlu.']);
        if (empty($data['bank_account_id'])) throw ValidationException::withMessages(['bank_account_id' => 'Hesap zorunlu.']);
        if ((float) ($data['amount'] ?? 0) <= 0) throw ValidationException::withMessages(['amount' => 'Tutar > 0 olmalı.']);
        if (count($allocations) < 1) throw ValidationException::withMessages(['allocations' => 'En az bir faturaya dağıtım yapılmalı.']);

        foreach ($allocations as $i => $a) {
            $invoice = FinArInvoice::find($a['invoice_id']);
            if (!$invoice) throw ValidationException::withMessages(["allocations.{$i}.invoice_id" => 'Fatura bulunamadı.']);
            if (!in_array($invoice->status, ['posted', 'partially_paid'], true)) throw ValidationException::withMessages(["allocations.{$i}.invoice_id" => "Fatura post edilmemiş veya zaten ödenmiş."]);
            
            $remaining = (float) $invoice->grand_total - (float) $invoice->paid_total;
            if ((float) $a['amount'] > $remaining + 0.0001) throw ValidationException::withMessages(["allocations.{$i}.amount" => "Fatura açık bakiyesinden fazla tahsilat yapılamaz."]);
        }
    }

    private function nextReceiptNo(string $tenantId, string $date): string {
        $year = date('Y', strtotime($date)); $prefix = "RCP-{$year}-";
        $last = FinArReceipt::where('tenant_id', $tenantId)->where('receipt_no', 'like', $prefix . '%')->orderByDesc('receipt_no')->value('receipt_no');
        $seq = 1;
        if ($last) { $parts = explode('-', $last); $seq = (int) end($parts) + 1; }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
