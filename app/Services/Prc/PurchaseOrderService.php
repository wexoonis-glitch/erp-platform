<?php
namespace App\Services\Prc;

use App\Models\PrcPurchaseOrder;
use App\Models\PrcPurchaseOrderLine;
use App\Services\Platform\WorkflowService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PurchaseOrderService
{
    public function create(array $data, array $lines): PrcPurchaseOrder
    {
        $tenantId = TenantContext::id();
        $this->validate($data, $lines);

        return DB::transaction(function () use ($data, $lines, $tenantId) {
            $totals = $this->calculateTotals($lines);
            $po = PrcPurchaseOrder::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'supplier_id' => $data['supplier_id'], 'po_no' => $this->nextPoNo($tenantId),
                'order_date' => $data['order_date'], 'expected_date' => $data['expected_date'] ?? null,
                'subtotal' => $totals['subtotal'], 'vat_total' => $totals['vat_total'], 'grand_total' => $totals['grand_total'],
                'status' => 'draft', 'notes' => $data['notes'] ?? null,
            ]);
            foreach ($lines as $line) {
                $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
                $vatAmount = $lineTotal * ((float) $line['vat_rate'] / 100);
                PrcPurchaseOrderLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'po_id' => $po->id,
                    'product_id' => $line['product_id'], 'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'], 'unit_price' => $line['unit_price'], 'vat_rate' => $line['vat_rate'],
                    'received_quantity' => 0, 'subtotal' => round($lineTotal, 4), 'vat_amount' => round($vatAmount, 4), 'total' => round($lineTotal + $vatAmount, 4),
                ]);
            }
            return $po->fresh();
        });
    }

    /**
     * Onaya gönderir. Aktif workflow varsa başlatır; yoksa eski davranış (doğrudan approve).
     */
    public function approve(string $id): void
    {
        $po = PrcPurchaseOrder::findOrFail($id);
        if ($po->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Sadece draft onaylanabilir.']);
        }

        $started = app(WorkflowService::class)->startRequest(
            'purchase_order',
            $po->id,
            "PO: {$po->po_no}",
            (float) $po->grand_total,
            [
                'amount' => (float) $po->grand_total,
                'po_no' => $po->po_no,
                'supplier' => $po->supplier?->name ?? '—',
            ]
        );

        if ($started) {
            // Workflow başladı; PO draft kalır. Onay bitince markApproved() çağrılır.
            return;
        }

        // Workflow tanımlı değil, doğrudan onayla (eski davranış)
        $po->update(['status' => 'approved']);
    }

    /**
     * Workflow sonu onayından sonra çağrılır. Zaten approve edildiyse no-op.
     */
    public function markApproved(string $id): void
    {
        $po = PrcPurchaseOrder::findOrFail($id);
        if ($po->status === 'draft') {
            $po->update(['status' => 'approved']);
        }
    }

    private function validate(array $data, array $lines): void
    {
        if (empty($data['supplier_id'])) throw ValidationException::withMessages(['supplier_id' => 'Tedarikçi zorunlu.']);
        if (empty($data['order_date'])) throw ValidationException::withMessages(['order_date' => 'Tarih zorunlu.']);
        if (count($lines) < 1) throw ValidationException::withMessages(['lines' => 'En az 1 satır.']);
    }

    private function calculateTotals(array $lines): array
    {
        $subtotal = 0; $vatTotal = 0;
        foreach ($lines as $line) {
            $lt = (float) $line['quantity'] * (float) $line['unit_price'];
            $subtotal += $lt; $vatTotal += $lt * ((float) $line['vat_rate'] / 100);
        }
        return ['subtotal' => round($subtotal, 4), 'vat_total' => round($vatTotal, 4), 'grand_total' => round($subtotal + $vatTotal, 4)];
    }

    private function nextPoNo(string $tenantId): string
    {
        $year = date('Y'); $prefix = "PO-{$year}-";
        $last = PrcPurchaseOrder::where('tenant_id', $tenantId)->where('po_no', 'like', $prefix . '%')->orderByDesc('po_no')->value('po_no');
        $seq = 1;
        if ($last) { $parts = explode('-', $last); $seq = (int) end($parts) + 1; }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
