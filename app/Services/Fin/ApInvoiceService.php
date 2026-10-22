<?php

namespace App\Services\Fin;

use App\Events\EventBus;
use App\Models\FinAccount;
use App\Models\FinApInvoice;
use App\Models\FinApInvoiceLine;
use App\Models\FinJournalEntry;
use App\Models\FinSupplier;
use App\Services\Platform\AuditService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ApInvoiceService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly EventBus $bus,
    ) {}

    /**
     * Draft fatura oluştur (yevmiye YOK).
     * @param array $data: supplier_id, invoice_no, invoice_date, due_date, currency, notes
     * @param array $lines: [{expense_account_id, description, quantity, unit_price, vat_rate}, ...]
     */
    public function create(array $data, array $lines): FinApInvoice
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        $this->validate($data, $lines);

        // Tedarikçi var mı?
        $supplier = FinSupplier::find($data['supplier_id']);
        if (!$supplier) {
            throw ValidationException::withMessages(['supplier_id' => 'Tedarikçi bulunamadı.']);
        }

        // Fatura no benzersiz mi?
        if (FinApInvoice::where('supplier_id', $data['supplier_id'])
            ->where('invoice_no', $data['invoice_no'])->exists()) {
            throw ValidationException::withMessages(['invoice_no' => 'Bu tedarikçide bu fatura no zaten var.']);
        }

        return DB::transaction(function () use ($data, $lines, $tenantId) {
            $totals = $this->calculateTotals($lines);

            $invoice = FinApInvoice::create([
                'id'           => (string) Str::uuid(),
                'tenant_id'    => $tenantId,
                'supplier_id'  => $data['supplier_id'],
                'invoice_no'   => $data['invoice_no'],
                'invoice_date' => $data['invoice_date'],
                'due_date'     => $data['due_date'],
                'currency'     => $data['currency'] ?? 'TRY',
                'subtotal'     => $totals['subtotal'],
                'vat_total'    => $totals['vat_total'],
                'grand_total'  => $totals['grand_total'],
                'paid_total'   => 0,
                'status'       => 'draft',
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($lines as $i => $line) {
                $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
                $vatAmount = $lineTotal * ((float) $line['vat_rate'] / 100);

                FinApInvoiceLine::create([
                    'id'                 => (string) Str::uuid(),
                    'tenant_id'          => $tenantId,
                    'invoice_id'         => $invoice->id,
                    'expense_account_id' => $line['expense_account_id'],
                    'description'        => $line['description'],
                    'quantity'           => $line['quantity'],
                    'unit_price'         => $line['unit_price'],
                    'vat_rate'           => $line['vat_rate'],
                    'subtotal'           => round($lineTotal, 4),
                    'vat_amount'         => round($vatAmount, 4),
                    'total'              => round($lineTotal + $vatAmount, 4),
                    'line_no'            => $i + 1,
                ]);
            }

            return $invoice->fresh();
        });
    }

    /**
     * Faturayı post et: OTOMATİK YEVMIYE oluşturur.
     *   Borç: her satırın gider hesabı (subtotal)
     *   Borç: 191 İndirilecek KDV (vat_total)
     *   Alacak: 320 Satıcılar (grand_total)
     */
    public function post(string $id): FinApInvoice
    {
        $invoice = FinApInvoice::findOrFail($id);

        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => "Sadece draft post edilebilir (mevcut: {$invoice->status}).",
            ]);
        }

        // Sabit hesapları bul
        $vatAccount = FinAccount::where('code', '191')->first();
        $supplierAccount = FinAccount::where('code', '320')->first();

        if (!$vatAccount || !$supplierAccount) {
            throw ValidationException::withMessages([
                'accounts' => 'Standart hesaplar eksik (191 İndirilecek KDV, 320 Satıcılar). Seed çalıştırın.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $vatAccount, $supplierAccount) {
            $entryLines = [];

            // 1) Her satır için borç
            foreach ($invoice->lines as $line) {
                $entryLines[] = [
                    'account_id'  => $line->expense_account_id,
                    'debit'       => (float) $line->subtotal,
                    'credit'      => 0,
                    'description' => $line->description,
                ];
            }

            // 2) KDV borç (varsa)
            if ((float) $invoice->vat_total > 0) {
                $entryLines[] = [
                    'account_id'  => $vatAccount->id,
                    'debit'       => (float) $invoice->vat_total,
                    'credit'      => 0,
                    'description' => "KDV - Fatura {$invoice->invoice_no}",
                ];
            }

            // 3) Satıcılar alacak
            $entryLines[] = [
                'account_id'  => $supplierAccount->id,
                'debit'       => 0,
                'credit'      => (float) $invoice->grand_total,
                'description' => "Tedarikçi faturası {$invoice->invoice_no}",
            ];

            // Yevmiye oluştur
            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create([
                'entry_date'  => $invoice->invoice_date->toDateString(),
                'description' => "AP Faturası {$invoice->invoice_no} - {$invoice->supplier->name}",
            ], $entryLines);

            // Yevmiyeyi post et
            $journalService->post($entry->id);

            // Faturayı güncelle
            $invoice->update([
                'status'           => 'posted',
                'journal_entry_id' => $entry->id,
            ]);

            $this->audit->record([
                'tenant_id'    => $invoice->tenant_id,
                'entity'       => 'fin_ap_invoice',
                'entity_id'    => $invoice->id,
                'action'       => 'update',
                'before_state' => ['status' => 'draft'],
                'after_state'  => ['status' => 'posted', 'journal_entry_id' => $entry->id],
            ]);

            $this->bus->publish('fin.ap_invoice.posted', [
                'id'          => $invoice->id,
                'invoice_no'  => $invoice->invoice_no,
                'grand_total' => (float) $invoice->grand_total,
                'journal_id'  => $entry->id,
            ], $invoice->tenant_id);

            return $invoice->fresh();
        });
    }

    public function delete(string $id): void
    {
        $invoice = FinApInvoice::findOrFail($id);
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Sadece draft faturalar silinebilir.',
            ]);
        }
        $invoice->delete();
    }

    private function validate(array $data, array $lines): void
    {
        if (empty($data['supplier_id'])) {
            throw ValidationException::withMessages(['supplier_id' => 'Tedarikçi zorunlu.']);
        }
        if (empty(trim($data['invoice_no'] ?? ''))) {
            throw ValidationException::withMessages(['invoice_no' => 'Fatura no zorunlu.']);
        }
        if (empty($data['invoice_date'])) {
            throw ValidationException::withMessages(['invoice_date' => 'Fatura tarihi zorunlu.']);
        }
        if (empty($data['due_date'])) {
            throw ValidationException::withMessages(['due_date' => 'Vade tarihi zorunlu.']);
        }
        if (count($lines) < 1) {
            throw ValidationException::withMessages(['lines' => 'En az 1 satır olmalı.']);
        }

        foreach ($lines as $i => $line) {
            if (empty($line['expense_account_id'])) {
                throw ValidationException::withMessages(["lines.{$i}.expense_account_id" => 'Hesap zorunlu.']);
            }
            if (empty($line['description'])) {
                throw ValidationException::withMessages(["lines.{$i}.description" => 'Açıklama zorunlu.']);
            }
            if ((float) ($line['quantity'] ?? 0) <= 0) {
                throw ValidationException::withMessages(["lines.{$i}.quantity" => 'Miktar > 0 olmalı.']);
            }
            if ((float) ($line['unit_price'] ?? 0) < 0) {
                throw ValidationException::withMessages(["lines.{$i}.unit_price" => 'Birim fiyat negatif olamaz.']);
            }
            if ((float) ($line['vat_rate'] ?? 0) < 0 || (float) ($line['vat_rate'] ?? 0) > 100) {
                throw ValidationException::withMessages(["lines.{$i}.vat_rate" => 'KDV oranı 0-100 arası olmalı.']);
            }
        }
    }

    private function calculateTotals(array $lines): array
    {
        $subtotal = 0;
        $vatTotal = 0;
        foreach ($lines as $line) {
            $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
            $subtotal += $lineTotal;
            $vatTotal += $lineTotal * ((float) $line['vat_rate'] / 100);
        }
        return [
            'subtotal'    => round($subtotal, 4),
            'vat_total'   => round($vatTotal, 4),
            'grand_total' => round($subtotal + $vatTotal, 4),
        ];
    }
}
