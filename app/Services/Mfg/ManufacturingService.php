<?php
namespace App\Services\Mfg;
use App\Models\MfgBom;
use App\Models\MfgBomLine;
use App\Models\MfgProductionOrder;
use App\Models\InvProduct;
use App\Services\Inv\StockService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ManufacturingService {
    public function createBom(array $data, array $lines): MfgBom {
        $tenantId = TenantContext::id();
        if (empty($data['product_id']) || empty($data['code'])) throw ValidationException::withMessages(['code' => 'Kod ve Ürün zorunlu.']);
        if (count($lines) < 1) throw ValidationException::withMessages(['lines' => 'En az 1 hammadde eklemelisiniz.']);

        return DB::transaction(function () use ($data, $lines, $tenantId) {
            $bom = MfgBom::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'product_id' => $data['product_id'], 'code' => $data['code'], 'name' => $data['name'],
                'quantity' => $data['quantity'] ?? 1, 'status' => 'active',
            ]);
            foreach ($lines as $i => $line) {
                MfgBomLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'bom_id' => $bom->id,
                    'component_id' => $line['component_id'], 'quantity' => $line['quantity'], 'line_no' => $i + 1,
                ]);
            }
            return $bom->fresh();
        });
    }

    public function createProductionOrder(array $data): MfgProductionOrder {
        $tenantId = TenantContext::id();
        $bom = MfgBom::findOrFail($data['bom_id']);
        if ((float)$data['quantity'] <= 0) throw ValidationException::withMessages(['quantity' => 'Miktar 0 dan büyük olmalı.']);

        return DB::transaction(function () use ($data, $tenantId, $bom) {
            $order = MfgProductionOrder::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'product_id' => $bom->product_id, 'bom_id' => $bom->id,
                'order_no' => $this->nextOrderNo($tenantId), 'order_date' => $data['order_date'],
                'quantity' => $data['quantity'], 'status' => 'draft',
            ]);
            return $order->fresh();
        });
    }

    public function completeProductionOrder(string $id): MfgProductionOrder {
        $order = MfgProductionOrder::findOrFail($id);
        if ($order->status !== 'draft') throw ValidationException::withMessages(['status' => 'Sadece draft durumdaki emirler tamamlanabilir.']);

        return DB::transaction(function () use ($order) {
            $bom = $order->bom;
            $stockService = app(StockService::class);
            $warehouse = $stockService->getDefaultWarehouse();
            
            $totalCost = 0;

            // 1. Hammadde Çıkışı (Stoktan düş)
            foreach ($bom->lines as $line) {
                $requiredQty = (float)$line->quantity * (float)$order->quantity;
                $component = InvProduct::find($line->component_id);
                
                // Yetersiz stok kontrolü (İsteğe bağlı, şimdilik es geçebiliriz ama ekleyelim)
                $balance = $stockService->getBalance($component->id, $warehouse->id);
                if ($balance < $requiredQty) {
                    throw ValidationException::withMessages(['stock' => "Yetersiz hammadde: {$component->name}. Mevcut: {$balance}, Gerekli: {$requiredQty}"]);
                }

                $stockService->recordOut($component->id, $warehouse->id, $order->order_date->toDateString(), $requiredQty, (float)$component->purchase_price, 'MFG_ISSUE', $order->id, "Üretim Emri: {$order->order_no}");
                $totalCost += $requiredQty * (float)$component->purchase_price;
            }

            // 2. Mamul Girişi (Stoğa ekle)
            $finishedProduct = InvProduct::find($order->product_id);
            // Maliyet: Hammadde maliyeti / Üretilen miktar
            $unitCost = (float)$order->quantity > 0 ? ($totalCost / (float)$order->quantity) : 0;
            
            $stockService->recordIn($finishedProduct->id, $warehouse->id, $order->order_date->toDateString(), (float)$order->quantity, $unitCost, 'MFG_RECEIPT', $order->id, "Üretim Girişi: {$order->order_no}");

            // 3. Siparişi güncelle
            $order->update(['status' => 'completed', 'total_cost' => $totalCost]);

            return $order->fresh();
        });
    }

    private function nextOrderNo(string $tenantId): string {
        $year = date('Y'); $prefix = "PRD-{$year}-";
        $last = MfgProductionOrder::where('tenant_id', $tenantId)->where('order_no', 'like', $prefix . '%')->orderByDesc('order_no')->value('order_no');
        $seq = 1;
        if ($last) { $parts = explode('-', $last); $seq = (int) end($parts) + 1; }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
