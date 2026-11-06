<?php
namespace App\Services\Log;
use App\Models\LogCarrier;
use App\Models\LogShipment;
use App\Models\LogShipmentLine;
use App\Models\OtcSalesOrder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ShippingService {
    public function createCarrier(array $data): LogCarrier {
        $tenantId = TenantContext::id();
        if (LogCarrier::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Bu taşıyıcı kodu zaten kullanımda.']);
        }
        return LogCarrier::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'code' => $data['code'], 'name' => $data['name'],
            'tracking_url' => $data['tracking_url'] ?? null, 'status' => 'active',
        ]);
    }

    public function createShipment(array $data): LogShipment {
        $tenantId = TenantContext::id();
        $order = OtcSalesOrder::findOrFail($data['sales_order_id']);
        
        if (!in_array($order->status, ['invoiced', 'approved'])) {
            throw ValidationException::withMessages(['sales_order_id' => 'Sadece onaylanmış veya faturalanmış siparişler sevk edilebilir.']);
        }

        return DB::transaction(function () use ($data, $tenantId, $order) {
            $shipment = LogShipment::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'sales_order_id' => $order->id, 'carrier_id' => $data['carrier_id'],
                'shipment_no' => $this->nextShipmentNo($tenantId),
                'shipment_date' => $data['shipment_date'],
                'tracking_no' => $data['tracking_no'] ?? null,
                'status' => 'shipped', 'notes' => $data['notes'] ?? null,
            ]);

            foreach ($order->lines as $line) {
                LogShipmentLine::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                    'shipment_id' => $shipment->id, 'product_id' => $line->product_id,
                    'quantity' => $line->quantity,
                ]);
            }

            return $shipment->fresh();
        });
    }

    private function nextShipmentNo(string $tenantId): string {
        $year = date('Y'); $prefix = "SHP-{$year}-";
        $last = LogShipment::where('tenant_id', $tenantId)->where('shipment_no', 'like', $prefix . '%')->orderByDesc('shipment_no')->value('shipment_no');
        $seq = 1;
        if ($last) { $parts = explode('-', $last); $seq = (int) end($parts) + 1; }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
