<?php
namespace App\Services\Mfg;
use App\Models\OtcSalesOrder;
use App\Services\Inv\StockService;
use App\Support\TenantContext;

final class MrpService {
    public function calculateRequirements(): array {
        $salesOrders = OtcSalesOrder::where('status', 'approved')
            ->with(['lines.product.bom.lines.component'])
            ->get();
            
        $requirements = [];
        
        foreach ($salesOrders as $so) {
            foreach ($so->lines as $line) {
                $product = $line->product;
                $qty = (float) $line->quantity;
                
                // Eğer bu ürünün bir reçetesi varsa (Mamul ise)
                if ($product && $product->bom) {
                    foreach ($product->bom->lines as $bomLine) {
                        $component = $bomLine->component;
                        if (!$component) continue;
                        
                        $reqQty = (float) $bomLine->quantity * $qty;
                        
                        if (!isset($requirements[$component->id])) {
                            $requirements[$component->id] = [
                                'id' => $component->id,
                                'code' => $component->code,
                                'name' => $component->name,
                                'unit' => $component->unit,
                                'required' => 0,
                                'available' => app(StockService::class)->getBalance($component->id),
                            ];
                        }
                        $requirements[$component->id]['required'] += $reqQty;
                    }
                }
            }
        }
        
        // Eksik miktarları hesapla
        foreach ($requirements as &$req) {
            $req['net_required'] = max(0, $req['required'] - $req['available']);
        }
        
        return array_values($requirements);
    }
}
