<?php
namespace App\Services\Fin;

use App\Events\EventBus;
use App\Models\FinAccount;
use App\Models\FinApInvoice;
use App\Models\FinApInvoiceLine;
use App\Models\FinSupplier;
use App\Models\PrcGoodsReceipt;
use App\Services\Platform\AuditService;
use App\Services\Platform\WorkflowService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ApInvoiceService {
    public function __construct(private readonly AuditService $audit, private readonly EventBus $bus) {}

    public function create(array $data, array $lines): FinApInvoice {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');
        $this->validate($data, $lines);

        $supplier = FinSupplier::find($data['supplier_id']);
        if (!$supplier) throw ValidationException::withMessages(['supplier_id' => 'Tedarikçi bulunamadı.']);

        if (FinApInvoice::where('supplier_id', $data['supplier_id'])->where('invoice_no', $data['invoice_no'])->exists()) {
            throw ValidationException::withMessages(['invoice_no' => 'Bu tedarikçide bu fatura no zaten var.']);
        }

        return DB::transaction(function () use ($data, $lines, $tenantId) {
            $totals = $this->calculateTotals($lines);
            $invoice = FinApInvoice::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'supplier_id' => $data['supplier_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'goods_receipt_id' => $data['goods_receipt_id'] ?? null,
                'invoice_no' => $data['invoice_no'], 'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'], 'currency' => $data['currency'] ?? 'TRY',
                'subtotal' => $totals['subtotal'], 'vat_total' => $totals['vat_total'], 'grand_total' => $totals['grand_total'],
                'paid_total' => 0, 'status' => 'draft', 'notes' => $data['notes'] ?? null,
            ]);
            foreach ($lines as $i => $line) {
                $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
                $vatAmount = $lineTotal * ((float) $line['vat_rate'] / 100);
                FinApInvoiceLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'invoice_id' => $invoice->id,
                    'expense_account_id' => $line['expense_account_id'],
                    'description' => $line['description'], 'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'], 'vat_rate' => $line['vat_rate'],
                    'subtotal' => round($lineTotal, 4), 'vat_amount' => round($vatAmount, 4),
                    'total' => round($lineTotal + $vatAmount, 4), 'line_no' => $i + 1,
                ]);
            }
            return $invoice->fresh();
        });
    }

    public function createFromGoodsReceipt(string $grId): array {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        $gr = PrcGoodsReceipt::with(['purchaseOrder.supplier', 'purchaseOrder.lines', 'lines.product'])->findOrFail($grId);
        if ($gr->status !== 'posted') throw ValidationException::withMessages(['status' => 'Sadece posting yapılmış mal kabuller faturalanabilir.']);
        if (FinApInvoice::where('goods_receipt_id', $gr->id)->exists()) throw ValidationException::withMessages(['status' => 'Bu mal kabul zaten faturalanmış.']);

        $po = $gr->purchaseOrder;
        if (!$po) throw ValidationException::withMessages(['po' => 'PO bulunamadı.']);

        $matchIssues = [];
        foreach ($gr->lines as $grLine) {
            $poLine = $po->lines->firstWhere('id', $grLine->po_line_id);
            if (!$poLine) { $matchIssues[] = "PO satırı eşleşmedi (GR satır: {$grLine->id})"; continue; }
            $poPrice = (float) $poLine->unit_price;
            $grPrice = (float) $grLine->unit_cost;
            if ($poPrice > 0) {
                $diffPct = abs($poPrice - $grPrice) / $poPrice * 100;
                if ($diffPct > 2) {
                    $matchIssues[] = sprintf('Fiyat farkı > %%2: %s — PO: %.2f, GR: %.2f (fark: %%%.1f)', $grLine->product?->code ?? '—', $poPrice, $grPrice, $diffPct);
                }
            }
        }

        $defaultExpense = FinAccount::where('code', '153')->first() ?? FinAccount::where('code', '770')->first();
        if (!$defaultExpense) throw ValidationException::withMessages(['account' => '153 veya 770 hesabı bulunamadı.']);

        $lines = [];
        foreach ($gr->lines as $grLine) {
            $product = $grLine->product;
            $expenseAccountId = $product?->inventory_account_id ?? $defaultExpense->id;
            $lines[] = [
                'expense_account_id' => $expenseAccountId,
                'description' => $product?->name ?? 'Mal Kabul',
                'quantity' => (float) $grLine->quantity,
                'unit_price' => (float) $grLine->unit_cost,
                'vat_rate' => (float) ($product?->vat_rate ?? 20),
            ];
        }

        $data = [
            'supplier_id' => $po->supplier_id,
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $gr->id,
            'invoice_no' => $this->nextInvoiceNo($tenantId),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => $po->currency ?? 'TRY',
            'notes' => "PO: {$po->po_no} — GR: {$gr->gr_no}",
        ];

        $invoice = $this->create($data, $lines);
        return ['invoice' => $invoice, 'match_issues' => $matchIssues];
    }

    /**
     * Entry point: workflow varsa başlatır, yoksa doğrudan post eder.
     * Döndürülen invoice 'draft' ise workflow başladı demektir; 'posted' ise işlem tamamlandı.
     */
    public function post(string $id): FinApInvoice {
        $invoice = FinApInvoice::findOrFail($id);
        if ($invoice->status !== 'draft') throw ValidationException::withMessages(['status' => "Sadece draft post edilebilir."]);

        $started = app(WorkflowService::class)->startRequest(
            'ap_invoice',
            $invoice->id,
            "AP Faturası: {$invoice->invoice_no}",
            (float) $invoice->grand_total,
            [
                'amount' => (float) $invoice->grand_total,
                'invoice_no' => $invoice->invoice_no,
                'supplier' => $invoice->supplier?->name ?? '—',
            ]
        );

        if ($started) {
            return $invoice; // workflow başladı; onay bitince doPost() çağrılır
        }

        return $this->doPost($invoice);
    }

    /**
     * Gerçek posting işlemi (workflow sonundan da çağrılır).
     */
    public function doPost(FinApInvoice $invoice): FinApInvoice {
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages(['status' => "Sadece draft post edilebilir."]);
        }

        $vatAccount = FinAccount::where('code', '191')->first();
        $supplierAccount = FinAccount::where('code', '320')->first();
        if (!$vatAccount || !$supplierAccount) throw ValidationException::withMessages(['accounts' => 'Standart hesaplar eksik (191, 320).']);

        return DB::transaction(function () use ($invoice, $vatAccount, $supplierAccount) {
            $entryLines = [];
            foreach ($invoice->lines as $line) {
                $entryLines[] = [
                    'account_id' => $line->expense_account_id,
                    'debit' => (float) $line->subtotal,
                    'credit' => 0,
                    'description' => $line->description,
                ];
            }
            if ((float) $invoice->vat_total > 0) {
                $entryLines[] = [
                    'account_id' => $vatAccount->id,
                    'debit' => (float) $invoice->vat_total,
                    'credit' => 0,
                    'description' => "KDV - Alış Faturası {$invoice->invoice_no}",
                ];
            }
            $entryLines[] = [
                'account_id' => $supplierAccount->id,
                'debit' => 0,
                'credit' => (float) $invoice->grand_total,
                'description' => "Tedarikçi faturası {$invoice->invoice_no} - {$invoice->supplier->name}",
            ];

            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create([
                'entry_date' => $invoice->invoice_date->toDateString(),
                'description' => "AP Faturası {$invoice->invoice_no} - {$invoice->supplier->name}",
            ], $entryLines);
            $journalService->post($entry->id);

            $invoice->update(['status' => 'posted', 'journal_entry_id' => $entry->id]);

            $this->audit->record([
                'tenant_id' => $invoice->tenant_id, 'entity' => 'fin_ap_invoice', 'entity_id' => $invoice->id,
                'action' => 'update', 'before_state' => ['status' => 'draft'],
                'after_state' => ['status' => 'posted', 'journal_entry_id' => $entry->id],
            ]);
            $this->bus->publish('fin.ap_invoice.posted', [
                'id' => $invoice->id, 'invoice_no' => $invoice->invoice_no,
                'grand_total' => (float) $invoice->grand_total, 'journal_id' => $entry->id,
            ], $invoice->tenant_id);

            return $invoice->fresh();
        });
    }

    public function delete(string $id): void {
        $invoice = FinApInvoice::findOrFail($id);
        if ($invoice->status !== 'draft') throw ValidationException::withMessages(['status' => 'Sadece draft silinebilir.']);
        $invoice->delete();
    }

    private function validate(array $data, array $lines): void {
        if (empty($data['supplier_id'])) throw ValidationException::withMessages(['supplier_id' => 'Tedarikçi zorunlu.']);
        if (empty(trim($data['invoice_no'] ?? ''))) throw ValidationException::withMessages(['invoice_no' => 'Fatura no zorunlu.']);
        if (count($lines) < 1) throw ValidationException::withMessages(['lines' => 'En az 1 satır olmalı.']);
    }

    private function calculateTotals(array $lines): array {
        $subtotal = 0; $vatTotal = 0;
        foreach ($lines as $line) {
            $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
            $subtotal += $lineTotal;
            $vatTotal += $lineTotal * ((float) $line['vat_rate'] / 100);
        }
        return ['subtotal' => round($subtotal, 4), 'vat_total' => round($vatTotal, 4), 'grand_total' => round($subtotal + $vatTotal, 4)];
    }

    private function nextInvoiceNo(string $tenantId): string {
        $year = date('Y'); $prefix = "AP-{$year}-";
        $last = FinApInvoice::where('tenant_id', $tenantId)->where('invoice_no', 'like', $prefix . '%')->orderByDesc('invoice_no')->value('invoice_no');
        $seq = 1;
        if ($last) { $parts = explode('-', $last); $seq = (int) end($parts) + 1; }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
