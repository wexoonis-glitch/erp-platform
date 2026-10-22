<?php

namespace App\Services\Fin;

use App\Events\EventBus;
use App\Models\FinAccount;
use App\Models\FinApInvoice;
use App\Models\FinApPayment;
use App\Models\FinApPaymentAllocation;
use App\Models\FinSupplier;
use App\Services\Platform\AuditService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ApPaymentService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly EventBus $bus,
    ) {}

    /**
     * Draft ödeme oluştur (yevmiye YOK).
     * @param array $data: supplier_id, bank_account_id, payment_date, amount, currency, method, reference, notes
     * @param array $allocations: [{invoice_id, amount}, ...]
     */
    public function create(array $data, array $allocations): FinApPayment
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        $this->validate($data, $allocations);

        $supplier = FinSupplier::find($data['supplier_id']);
        if (!$supplier) {
            throw ValidationException::withMessages(['supplier_id' => 'Tedarikçi bulunamadı.']);
        }

        // Dağıtım toplamı = ödeme tutarı mı?
        $allocTotal = array_sum(array_map(fn($a) => (float) $a['amount'], $allocations));
        if (abs($allocTotal - (float) $data['amount']) > 0.0001) {
            throw ValidationException::withMessages([
                'amount' => "Ödeme tutarı (" . number_format((float) $data['amount'], 2) . ") ≠ dağıtım toplamı (" . number_format($allocTotal, 2) . ")",
            ]);
        }

        return DB::transaction(function () use ($data, $allocations, $tenantId) {
            $payment = FinApPayment::create([
                'id'              => (string) Str::uuid(),
                'tenant_id'       => $tenantId,
                'supplier_id'     => $data['supplier_id'],
                'bank_account_id' => $data['bank_account_id'],
                'payment_no'      => $this->nextPaymentNo($tenantId, $data['payment_date']),
                'payment_date'    => $data['payment_date'],
                'amount'          => $data['amount'],
                'currency'        => $data['currency'] ?? 'TRY',
                'method'          => $data['method'] ?? 'bank',
                'reference'       => $data['reference'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'status'          => 'draft',
            ]);

            foreach ($allocations as $alloc) {
                FinApPaymentAllocation::create([
                    'id'         => (string) Str::uuid(),
                    'tenant_id'  => $tenantId,
                    'payment_id' => $payment->id,
                    'invoice_id' => $alloc['invoice_id'],
                    'amount'     => $alloc['amount'],
                ]);
            }

            return $payment->fresh();
        });
    }

    /**
     * Ödemeyi post et: OTOMATİK YEVMIYE oluşturur + fatura paid_total günceller.
     *   Borç: 320 Satıcılar (ödeme tutarı)
     *   Alacak: 102 Banka / 100 Kasa (ödeme tutarı)
     */
    public function post(string $id): FinApPayment
    {
        $payment = FinApPayment::findOrFail($id);

        if ($payment->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => "Sadece draft ödeme post edilebilir (mevcut: {$payment->status}).",
            ]);
        }

        $supplierAccount = FinAccount::where('code', '320')->first();
        if (!$supplierAccount) {
            throw ValidationException::withMessages(['accounts' => '320 Satıcılar hesabı eksik.']);
        }

        return DB::transaction(function () use ($payment, $supplierAccount) {
            // 1) Yevmiye oluştur
            $entryLines = [
                [
                    'account_id'  => $supplierAccount->id,
                    'debit'       => (float) $payment->amount,
                    'credit'      => 0,
                    'description' => "Tedarikçi ödemesi {$payment->payment_no}",
                ],
                [
                    'account_id'  => $payment->bank_account_id,
                    'debit'       => 0,
                    'credit'      => (float) $payment->amount,
                    'description' => "Ödeme {$payment->payment_no}",
                ],
            ];

            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create([
                'entry_date'  => $payment->payment_date->toDateString(),
                'description' => "AP Ödemesi {$payment->payment_no} - {$payment->supplier->name}",
            ], $entryLines);
            $journalService->post($entry->id);

            // 2) Faturalara dağıtım + paid_total güncelle
            foreach ($payment->allocations as $alloc) {
                $invoice = FinApInvoice::find($alloc->invoice_id);
                if (!$invoice) continue;

                $newPaid = (float) $invoice->paid_total + (float) $alloc->amount;
                $newStatus = $newPaid >= (float) $invoice->grand_total - 0.0001
                    ? 'paid'
                    : 'partially_paid';

                $invoice->update([
                    'paid_total' => $newPaid,
                    'status'     => $newStatus,
                ]);
            }

            // 3) Ödeme durumunu güncelle
            $payment->update([
                'status'           => 'posted',
                'journal_entry_id' => $entry->id,
            ]);

            $this->audit->record([
                'tenant_id'    => $payment->tenant_id,
                'entity'       => 'fin_ap_payment',
                'entity_id'    => $payment->id,
                'action'       => 'update',
                'before_state' => ['status' => 'draft'],
                'after_state'  => ['status' => 'posted', 'journal_entry_id' => $entry->id],
            ]);

            $this->bus->publish('fin.ap_payment.posted', [
                'id'         => $payment->id,
                'payment_no' => $payment->payment_no,
                'amount'     => (float) $payment->amount,
                'journal_id' => $entry->id,
            ], $payment->tenant_id);

            return $payment->fresh();
        });
    }

    public function delete(string $id): void
    {
        $payment = FinApPayment::findOrFail($id);
        if ($payment->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Sadece draft ödemeler silinebilir.',
            ]);
        }
        $payment->delete();
    }

    private function validate(array $data, array $allocations): void
    {
        if (empty($data['supplier_id'])) {
            throw ValidationException::withMessages(['supplier_id' => 'Tedarikçi zorunlu.']);
        }
        if (empty($data['bank_account_id'])) {
            throw ValidationException::withMessages(['bank_account_id' => 'Ödeme hesabı zorunlu.']);
        }
        if (empty($data['payment_date'])) {
            throw ValidationException::withMessages(['payment_date' => 'Ödeme tarihi zorunlu.']);
        }
        if ((float) ($data['amount'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Ödeme tutarı > 0 olmalı.']);
        }
        if (count($allocations) < 1) {
            throw ValidationException::withMessages(['allocations' => 'En az bir faturaya dağıtım yapılmalı.']);
        }

        // Her dağıtım için fatura geçerliliği
        foreach ($allocations as $i => $a) {
            if (empty($a['invoice_id'])) {
                throw ValidationException::withMessages(["allocations.{$i}.invoice_id" => 'Fatura zorunlu.']);
            }
            if ((float) $a['amount'] <= 0) {
                throw ValidationException::withMessages(["allocations.{$i}.amount" => 'Tutar > 0 olmalı.']);
            }

            $invoice = FinApInvoice::find($a['invoice_id']);
            if (!$invoice) {
                throw ValidationException::withMessages(["allocations.{$i}.invoice_id" => 'Fatura bulunamadı.']);
            }
            if (!in_array($invoice->status, ['posted', 'partially_paid'], true)) {
                throw ValidationException::withMessages([
                    "allocations.{$i}.invoice_id" => "Fatura post edilmemiş veya zaten ödenmiş ({$invoice->status}).",
                ]);
            }

            $remaining = (float) $invoice->grand_total - (float) $invoice->paid_total;
            if ((float) $a['amount'] > $remaining + 0.0001) {
                throw ValidationException::withMessages([
                    "allocations.{$i}.amount" => "Fatura açık bakiyesinden ({$remaining}) fazla ödeme yapılamaz.",
                ]);
            }
        }
    }

    private function nextPaymentNo(string $tenantId, string $date): string
    {
        $year = date('Y', strtotime($date));
        $prefix = "PAY-{$year}-";
        $last = FinApPayment::where('tenant_id', $tenantId)
            ->where('payment_no', 'like', $prefix . '%')
            ->orderByDesc('payment_no')
            ->value('payment_no');

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = (int) end($parts) + 1;
        }

        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
