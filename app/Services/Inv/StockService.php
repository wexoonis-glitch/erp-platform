<?php
namespace App\Services\Inv;
use App\Models\InvProduct;
use App\Models\InvStockMovement;
use App\Models\InvWarehouse;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StockService {
    public function getDefaultWarehouse(): InvWarehouse {
        $tenantId = TenantContext::id();
        $warehouse = InvWarehouse::where('tenant_id', $tenantId)->first();
        if (!$warehouse) {
            $warehouse = InvWarehouse::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'code' => 'MAIN',
                'name' => 'Ana Depo',
                'status' => 'active',
            ]);
        }
        return $warehouse;
    }

    public function getBalance(string $productId, ?string $warehouseId = null): float {
        $q = InvStockMovement::where('product_id', $productId);
        if ($warehouseId) $q->where('warehouse_id', $warehouseId);
        $in = (clone $q)->where('type', 'in')->sum('quantity');
        $out = (clone $q)->where('type', 'out')->sum('quantity');
        return (float) $in - (float) $out;
    }

    public function recordIn(string $productId, string $warehouseId, string $date, float $qty, float $unitCost, ?string $refType = null, ?string $refId = null, ?string $desc = null): void {
        $tenantId = TenantContext::id();
        InvStockMovement::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_date' => $date,
            'type' => 'in',
            'quantity' => $qty,
            'remaining_quantity' => $qty,   // FIFO katmanı
            'unit_cost' => $unitCost,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $desc,
        ]);
    }

    public function recordOut(string $productId, string $warehouseId, string $date, float $qty, float $unitCost, ?string $refType = null, ?string $refId = null, ?string $desc = null): void {
        $tenantId = TenantContext::id();
        InvStockMovement::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_date' => $date,
            'type' => 'out',
            'quantity' => $qty,
            'remaining_quantity' => 0,
            'unit_cost' => $unitCost,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $desc,
        ]);
    }

    /**
     * FIFO ile stok tüketimi. En eski 'in' hareketinden başlar.
     * Kullanılan katmanları `remaining_quantity` üzerinden düşer ve toplam maliyeti döner.
     *
     * @return array{total_cost: float, layers: array<int, array{id: string, qty: float, unit_cost: float, cost: float}>}
     */
    public function consumeFifo(string $productId, string $warehouseId, float $qty): array {
        if ($qty <= 0) return ['total_cost' => 0.0, 'layers' => []];

        return DB::transaction(function () use ($productId, $warehouseId, $qty) {
            $remaining = $qty;
            $totalCost = 0.0;
            $usedLayers = [];

            $layers = InvStockMovement::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('type', 'in')
                ->where('remaining_quantity', '>', 0)
                ->orderBy('movement_date')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($layers as $layer) {
                if ($remaining <= 0.0001) break;

                $take = min($remaining, (float) $layer->remaining_quantity);
                if ($take <= 0) continue;

                $cost = $take * (float) $layer->unit_cost;
                $totalCost += $cost;
                $remaining -= $take;

                $layer->update(['remaining_quantity' => (float) $layer->remaining_quantity - $take]);

                $usedLayers[] = [
                    'id' => $layer->id,
                    'qty' => $take,
                    'unit_cost' => (float) $layer->unit_cost,
                    'cost' => $cost,
                ];
            }

            return ['total_cost' => round($totalCost, 4), 'layers' => $usedLayers];
        });
    }

    /**
     * Satış/fatura sırasında maliyet hesabı. Ürünün cost_method'üne göre:
     *   - standard: purchase_price (mevcut davranış)
     *   - fifo:     consumeFifo (en eski katmandan)
     *
     * @return array{unit_cost: float, total_cost: float, method: string}
     */
    public function calculateCost(string $productId, string $warehouseId, float $qty): array {
        $product = InvProduct::find($productId);
        if (!$product) return ['unit_cost' => 0.0, 'total_cost' => 0.0, 'method' => 'none'];

        $method = $product->cost_method ?? 'standard';

        if ($method === 'fifo') {
            $result = $this->consumeFifo($productId, $warehouseId, $qty);
            $unitCost = $qty > 0 ? $result['total_cost'] / $qty : 0.0;
            return [
                'unit_cost' => round($unitCost, 4),
                'total_cost' => $result['total_cost'],
                'method' => 'fifo',
            ];
        }

        // standard (varsayılan) — eski davranış
        $unit = (float) $product->purchase_price;
        return [
            'unit_cost' => $unit,
            'total_cost' => round($unit * $qty, 4),
            'method' => 'standard',
        ];
    }
}
