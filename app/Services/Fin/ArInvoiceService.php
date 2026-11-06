<?php
namespace App\Services\Fin;
use App\Events\EventBus;
use App\Models\FinAccount;
use App\Models\FinArInvoice;
use App\Models\FinArInvoiceLine;
use App\Models\FinCustomer;
use App\Models\InvProduct;
use App\Models\InvWarehouse;
use App\Models\OtcSalesOrder;
use App\Services\Inv\StockService;
use App\Services\Platform\AuditService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ArInvoiceService {
    public function __construct(private readonly AuditService $audit, private readonly EventBus $bus) {}

    public function create(array $data, array $lines): FinArInvoice {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');
        $this->validate($data, $lines);

        $customer = FinCustomer::find($data['customer_id']);
        if (!$customer) throw ValidationException::withMessages(['customer_id' => 'Müşteri bulunamadı.']);

        if (FinArInvoice::where('customer_id', $data['customer_id'])->where('invoice_no', $data['invoice_no'])->exists()) {
            throw ValidationException::withMessages(['invoice_no' => 'Bu müşteride bu fatura no zaten var.']);
        }

        return DB::transaction(function () use ($data, $lines, $tenantId) {
            $totals = $this->calculateTotals($lines);
            $invoice = FinArInvoice::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'customer_id' => $data['customer_id'],
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'invoice_no' => $data['invoice_no'], 'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'], 'currency' => $data['currency'] ?? 'TRY',
                'subtotal' => $totals['subtotal'], 'vat_total' => $totals['vat_total'], 'grand_total' => $totals['grand_total'],
                'paid_total' => 0, 'status' => 'draft', 'notes' => $data['notes'] ?? null,
            ]);
            foreach ($lines as $i => $line) {
                $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
                $vatAmount = $lineTotal * ((float) $line['vat_rate'] / 100);
                FinArInvoiceLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'invoice_id' => $invoice->id,
                    'revenue_account_id' => $line['revenue_account_id'],
                    'product_id' => $line['product_id'] ?? null,
                    'tax_regime_id' => $line['tax_regime_id'] ?? null,
                    'description' => $line['description'], 'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'], 'vat_rate' => $line['vat_rate'],
                    'subtotal' => round($lineTotal, 4), 'vat_amount' => round($vatAmount, 4),
                    'total' => round($lineTotal + $vatAmount, 4), 'line_no' => $i + 1,
                ]);
            }
            return $invoice->fresh();
        });
    }

    public function createFromSalesOrder(string $soId): FinArInvoice {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');
        $so = OtcSalesOrder::with('lines')->findOrFail($soId);
        if (!in_array($so->status, ['approved', 'confirmed', 'processing'], true)) {
            throw ValidationException::withMessages(['status' => 'Sadece onaylanmış siparişler faturaya dönüştürülebilir.']);
        }
        if (FinArInvoice::where('sales_order_id', $so->id)->exists()) {
            throw ValidationException::withMessages(['status' => 'Bu sipariş zaten faturalanmış.']);
        }
        $defaultRevenue = FinAccount::where('code', '600')->first();
        $taxResolver = app(TaxResolverService::class);
        $lines = [];
        foreach ($so->lines as $line) {
            $product = $line->product_id ? InvProduct::find($line->product_id) : null;
            $revenueId = $line->revenue_account_id ?? ($product?->revenue_account_id) ?? $defaultRevenue?->id;
            if (!$revenueId) throw ValidationException::withMessages(['account' => "Satır için gelir hesabı bulunamadı."]);
            $tax = $product ? $taxResolver->resolveForProduct($product->id) : ['regime_id' => null, 'rate' => 0.0];
            $lines[] = [
                'revenue_account_id' => $revenueId,
                'product_id' => $line->product_id,
                'tax_regime_id' => $tax['regime_id'],
                'description' => $line->description ?? ($product?->name ?? 'Satır'),
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'vat_rate' => (float) $line->vat_rate ?: $tax['rate'],
            ];
        }
        $data = [
            'customer_id' => $so->customer_id, 'sales_order_id' => $so->id,
            'invoice_no' => $this->nextInvoiceNo($tenantId, now()->toDateString()),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => $so->currency ?? 'TRY',
            'notes' => "Satış Siparişi: {$so->so_no}",
        ];
        return $this->create($data, $lines);
    }

    public function post(string $id): FinArInvoice {
        $invoice = FinArInvoice::findOrFail($id);
        if ($invoice->status !== 'draft') throw ValidationException::withMessages(['status' => "Sadece draft post edilebilir."]);

        $arAccount = FinAccount::where('code', '120')->first();
        $vatAccount = FinAccount::where('code', '391')->first();
        if (!$arAccount || !$vatAccount) throw ValidationException::withMessages(['accounts' => 'Standart hesaplar eksik (120, 391).']);

        $stockService = app(StockService::class);
        $warehouse = $stockService->getDefaultWarehouse();

        // Stok yeterlilik kontrolü (sadece track_stock ürünler)
        foreach ($invoice->lines as $line) {
            if ($line->product_id) {
                $product = InvProduct::find($line->product_id);
                if ($product && $product->track_stock) {
                    $balance = $stockService->getBalance($product->id, $warehouse->id);
                    if ($balance < (float) $line->quantity) {
                        throw ValidationException::withMessages(['stock' => "Yetersiz Stok! [{$product->code}] {$product->name} — Mevcut: {$balance}, İstenen: {$line->quantity}"]);
                    }
                }
            }
        }

        return DB::transaction(function () use ($invoice, $arAccount, $vatAccount, $warehouse, $stockService) {
            // 1) Satış yevmiyesi
            $entryLines = [];
            $entryLines[] = ['account_id' => $arAccount->id, 'debit' => (float) $invoice->grand_total, 'credit' => 0, 'description' => "Müşteri faturası {$invoice->invoice_no}"];
            foreach ($invoice->lines as $line) {
                $entryLines[] = ['account_id' => $line->revenue_account_id, 'debit' => 0, 'credit' => (float) $line->subtotal, 'description' => $line->description];
            }
            if ((float) $invoice->vat_total > 0) {
                $entryLines[] = ['account_id' => $vatAccount->id, 'debit' => 0, 'credit' => (float) $invoice->vat_total, 'description' => "KDV - Fatura {$invoice->invoice_no}"];
            }

            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create(['entry_date' => $invoice->invoice_date->toDateString(), 'description' => "AR Faturası {$invoice->invoice_no} - {$invoice->customer->name}"], $entryLines);
            $journalService->post($entry->id);

            // 2) Stok çıkışı + COGS (cost_method'e göre)
            $cogsTotal = 0.0;
            $inventoryAccountId = null;
            $cogsAccountId = null;

            foreach ($invoice->lines as $line) {
                if (!$line->product_id) continue;

                $product = InvProduct::find($line->product_id);
                if (!$product || !$product->track_stock) continue;

                $qty = (float) $line->quantity;

                // FIFO ise katmanları tüket; standard ise sadece unit_cost al
                $cost = $stockService->calculateCost($product->id, $warehouse->id, $qty);

                $stockService->recordOut(
                    $product->id,
                    $warehouse->id,
                    $invoice->invoice_date->toDateString(),
                    $qty,
                    (float) $cost['unit_cost'],   // görsel/kayıt amaçlı ortalama birim maliyet
                    'AR_INVOICE',
                    $invoice->id,
                    $invoice->invoice_no
                );

                $cogsTotal += (float) $cost['total_cost'];
                $cogsAccountId = $product->cogs_account_id;
                $inventoryAccountId = $product->inventory_account_id;
            }

            if ($cogsTotal > 0 && $cogsAccountId && $inventoryAccountId) {
                $cogsEntry = $journalService->create(['entry_date' => $invoice->invoice_date->toDateString(), 'description' => "Maliyet (COGS) - {$invoice->invoice_no}"], [
                    ['account_id' => $cogsAccountId, 'debit' => $cogsTotal, 'credit' => 0, 'description' => "Satılan Malın Maliyeti"],
                    ['account_id' => $inventoryAccountId, 'debit' => 0, 'credit' => $cogsTotal, 'description' => "Stok Çıkışı"],
                ]);
                $journalService->post($cogsEntry->id);
            }

            $invoice->update(['status' => 'posted', 'journal_entry_id' => $entry->id]);
            $this->audit->record(['tenant_id' => $invoice->tenant_id, 'entity' => 'fin_ar_invoice', 'entity_id' => $invoice->id, 'action' => 'update', 'before_state' => ['status' => 'draft'], 'after_state' => ['status' => 'posted']]);
            $this->bus->publish('fin.ar_invoice.posted', ['id' => $invoice->id, 'invoice_no' => $invoice->invoice_no, 'grand_total' => (float) $invoice->grand_total], $invoice->tenant_id);
            return $invoice->fresh();
        });
    }

    public function delete(string $id): void {
        $invoice = FinArInvoice::findOrFail($id);
        if ($invoice->status !== 'draft') throw ValidationException::withMessages(['status' => 'Sadece draft silinebilir.']);
        $invoice->delete();
    }

    private function validate(array $data, array $lines): void {
        if (empty($data['customer_id'])) throw ValidationException::withMessages(['customer_id' => 'Müşteri zorunlu.']);
        if (empty(trim($data['invoice_no'] ?? ''))) throw ValidationException::withMessages(['invoice_no' => 'Fatura no zorunlu.']);
        if (count($lines) < 1) throw ValidationException::withMessages(['lines' => 'En az 1 satır olmalı.']);
    }

    private function calculateTotals(array $lines): array {
        $subtotal = 0; $vatTotal = 0;
        foreach ($lines as $line) {
            $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
            $subtotal += $lineTotal; $vatTotal += $lineTotal * ((float) $line['vat_rate'] / 100);
        }
        return ['subtotal' => round($subtotal, 4), 'vat_total' => round($vatTotal, 4), 'grand_total' => round($subtotal + $vatTotal, 4)];
    }

    private function nextInvoiceNo(string $tenantId, string $date): string {
        $year = date('Y', strtotime($date));
        $prefix = "INV-{$year}-";
        $last = FinArInvoice::where('tenant_id', $tenantId)->where('invoice_no', 'like', $prefix . '%')->orderByDesc('invoice_no')->value('invoice_no');
        $seq = 1;
        if ($last) { $parts = explode('-', $last); $seq = (int) end($parts) + 1; }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
