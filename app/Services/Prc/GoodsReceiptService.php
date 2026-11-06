<?php
namespace App\Services\Prc;
use App\Models\PrcPurchaseOrder;
use App\Models\PrcGoodsReceipt;
use App\Models\PrcGoodsReceiptLine;
use App\Services\Inv\PricingService;
use App\Services\Inv\StockService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class GoodsReceiptService {
    public function createAndPost(array $data, array $lines): PrcGoodsReceipt {
        $tenantId = TenantContext::id();
        $po = PrcPurchaseOrder::findOrFail($data['po_id']);

        if (!in_array($po->status, ['approved', 'partially_received'])) {
            throw ValidationException::withMessages(['status' => 'Sadece onaylanmış siparişlere mal kabul yapılabilir.']);
        }

        return DB::transaction(function () use ($data, $lines, $tenantId, $po) {
            $gr = PrcGoodsReceipt::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'po_id' => $po->id, 'gr_no' => $this->nextGrNo($tenantId),
                'receipt_date' => $data['receipt_date'], 'status' => 'posted'
            ]);

            $stockService = app(StockService::class);
            $pricing = app(PricingService::class);
            $warehouse = $stockService->getDefaultWarehouse();

            $allReceived = true;

            foreach ($lines as $line) {
                if ((float)$line['quantity'] <= 0) continue;

                PrcGoodsReceiptLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'gr_id' => $gr->id,
                    'po_line_id' => $line['po_line_id'], 'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'], 'unit_cost' => $line['unit_cost'],
                ]);

                $stockService->recordIn(
                    $line['product_id'], $warehouse->id, $data['receipt_date'],
                    (float) $line['quantity'], (float) $line['unit_cost'], 'GR', $gr->id, "Mal Kabul: {$gr->gr_no}"
                );

                // Son alış fiyatını güncelle (yenileme maliyeti için)
                $pricing->updateReplacementCost($line['product_id'], (float) $line['unit_cost']);

                $poLine = $po->lines()->find($line['po_line_id']);
                $newReceived = (float)$poLine->received_quantity + (float)$line['quantity'];
                $poLine->update(['received_quantity' => $newReceived]);

                if ($newReceived < (float)$poLine->quantity) {
                    $allReceived = false;
                }
            }

            $po->update(['status' => $allReceived ? 'received' : 'partially_received']);
            return $gr;
        });
    }

    private function nextGrNo(string $tenantId): string {
        $year = date('Y'); $prefix = "GR-{$year}-";
        $last = PrcGoodsReceipt::where('tenant_id', $tenantId)->where('gr_no', 'like', $prefix . '%')->orderByDesc('gr_no')->value('gr_no');
        $seq = $last ? (int) end(explode('-', $last)) + 1 : 1;
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
