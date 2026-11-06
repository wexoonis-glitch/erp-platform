<?php
namespace App\Services\Otc;
use App\Models\OtcSalesOrder;
use App\Models\OtcSalesOrderLine;
use App\Models\FinArInvoice;
use App\Models\FinArInvoiceLine;
use App\Models\FinAccount;
use App\Models\InvProduct;
use App\Services\Fin\JournalEntryService;
use App\Services\Inv\StockService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SalesOrderService {
    public function create(array $data, array $lines): OtcSalesOrder {
        $tenantId = TenantContext::id();
        $this->validate($data, $lines);

        return DB::transaction(function () use ($data, $lines, $tenantId) {
            $totals = $this->calculateTotals($lines);
            $so = OtcSalesOrder::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'customer_id' => $data['customer_id'], 'so_no' => $this->nextSoNo($tenantId),
                'order_date' => $data['order_date'],
                'subtotal' => $totals['subtotal'], 'vat_total' => $totals['vat_total'], 'grand_total' => $totals['grand_total'],
                'status' => 'draft', 'notes' => $data['notes'] ?? null,
            ]);
            foreach ($lines as $line) {
                $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
                $vatAmount = $lineTotal * ((float) $line['vat_rate'] / 100);
                OtcSalesOrderLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'so_id' => $so->id,
                    'product_id' => $line['product_id'], 'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'], 'unit_price' => $line['unit_price'], 'vat_rate' => $line['vat_rate'],
                    'subtotal' => round($lineTotal, 4), 'vat_amount' => round($vatAmount, 4), 'total' => round($lineTotal + $vatAmount, 4),
                ]);
            }
            return $so->fresh();
        });
    }

    public function approve(string $id): void {
        $so = OtcSalesOrder::findOrFail($id);
        if ($so->status !== 'draft') throw ValidationException::withMessages(['status' => 'Sadece draft onaylanabilir.']);
        $so->update(['status' => 'approved']);
    }

    public function convertToInvoice(string $id): OtcSalesOrder {
        $so = OtcSalesOrder::findOrFail($id);
        if ($so->status !== 'approved') throw ValidationException::withMessages(['status' => 'Sadece onaylanmış siparişler faturalaştırılabilir.']);
        if ($so->ar_invoice_id) throw ValidationException::withMessages(['status' => 'Bu sipariş zaten faturalaştırılmış.']);

        return DB::transaction(function () use ($so) {
            $tenantId = TenantContext::id();
            
            $invoice = FinArInvoice::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'customer_id' => $so->customer_id,
                'invoice_no' => 'INV-' . $so->so_no,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'currency' => 'TRY',
                'subtotal' => $so->subtotal, 'vat_total' => $so->vat_total, 'grand_total' => $so->grand_total,
                'paid_total' => 0, 'status' => 'posted',
                'notes' => 'Sipariş No: ' . $so->so_no,
            ]);

            $entryLines = [];
            $arAccount = FinAccount::where('code', '120')->first();
            $vatAccount = FinAccount::where('code', '391')->first();
            
            if (!$arAccount || !$vatAccount) throw ValidationException::withMessages(['accounts' => 'Standart hesaplar eksik (120, 391).']);

            $entryLines[] = ['account_id' => $arAccount->id, 'debit' => (float)$so->grand_total, 'credit' => 0, 'description' => "Satış Faturası {$invoice->invoice_no}"];

            $cogsTotal = 0; $inventoryAccountId = null; $cogsAccountId = null;
            $stockService = app(StockService::class);
            $warehouse = $stockService->getDefaultWarehouse();

            foreach ($so->lines as $i => $line) {
                $product = InvProduct::find($line->product_id);
                
                FinArInvoiceLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'invoice_id' => $invoice->id,
                    'revenue_account_id' => $product->revenue_account_id, 'product_id' => $product->id,
                    'description' => $line->description ?? $product->name, 'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price, 'vat_rate' => $line->vat_rate,
                    'subtotal' => $line->subtotal, 'vat_amount' => $line->vat_amount, 'total' => $line->total, 'line_no' => $i + 1,
                ]);

                $entryLines[] = ['account_id' => $product->revenue_account_id, 'debit' => 0, 'credit' => (float)$line->subtotal, 'description' => $product->name];

                if ($product->track_stock) {
                    $stockService->recordOut($product->id, $warehouse->id, now()->toDateString(), (float)$line->quantity, (float)$product->purchase_price, 'SO_INVOICE', $so->id, $so->so_no);
                    $cogsTotal += (float)$line->quantity * (float)$product->purchase_price;
                    $cogsAccountId = $product->cogs_account_id;
                    $inventoryAccountId = $product->inventory_account_id;
                }
            }

            if ((float)$so->vat_total > 0) {
                $entryLines[] = ['account_id' => $vatAccount->id, 'debit' => 0, 'credit' => (float)$so->vat_total, 'description' => "KDV - Fatura {$invoice->invoice_no}"];
            }

            $journalService = app(JournalEntryService::class);
            $entry = $journalService->create(['entry_date' => now()->toDateString(), 'description' => "Satış Faturası {$invoice->invoice_no} - {$so->customer->name}"], $entryLines);
            $journalService->post($entry->id);

            if ($cogsTotal > 0 && $cogsAccountId && $inventoryAccountId) {
                $cogsEntry = $journalService->create(['entry_date' => now()->toDateString(), 'description' => "Maliyet (COGS) - {$invoice->invoice_no}"], [
                    ['account_id' => $cogsAccountId, 'debit' => $cogsTotal, 'credit' => 0, 'description' => "Satılan Malın Maliyeti"],
                    ['account_id' => $inventoryAccountId, 'debit' => 0, 'credit' => $cogsTotal, 'description' => "Stok Çıkışı"],
                ]);
                $journalService->post($cogsEntry->id);
            }

            $invoice->update(['journal_entry_id' => $entry->id]);
            $so->update(['status' => 'invoiced', 'ar_invoice_id' => $invoice->id]);

            return $so->fresh();
        });
    }

    private function validate(array $data, array $lines): void {
        if (empty($data['customer_id'])) throw ValidationException::withMessages(['customer_id' => 'Müşteri zorunlu.']);
        if (empty($data['order_date'])) throw ValidationException::withMessages(['order_date' => 'Tarih zorunlu.']);
        if (count($lines) < 1) throw ValidationException::withMessages(['lines' => 'En az 1 satır.']);
    }

    private function calculateTotals(array $lines): array {
        $subtotal = 0; $vatTotal = 0;
        foreach ($lines as $line) {
            $lt = (float) $line['quantity'] * (float) $line['unit_price'];
            $subtotal += $lt; $vatTotal += $lt * ((float) $line['vat_rate'] / 100);
        }
        return ['subtotal' => round($subtotal, 4), 'vat_total' => round($vatTotal, 4), 'grand_total' => round($subtotal + $vatTotal, 4)];
    }

    private function nextSoNo(string $tenantId): string {
        $year = date('Y'); $prefix = "SO-{$year}-";
        $last = OtcSalesOrder::where('tenant_id', $tenantId)->where('so_no', 'like', $prefix . '%')->orderByDesc('so_no')->value('so_no');
        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = (int) end($parts) + 1;
        }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
