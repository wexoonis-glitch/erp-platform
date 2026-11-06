<?php
namespace App\Services\Eam;
use App\Models\EamMaintenanceOrder;
use App\Models\EamAsset;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MaintenanceService {
    public function create(array $data): EamMaintenanceOrder {
        $tenantId = TenantContext::id();
        return EamMaintenanceOrder::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'asset_id' => $data['asset_id'], 'order_no' => $this->nextOrderNo($tenantId),
            'type' => $data['type'], 'scheduled_date' => $data['scheduled_date'],
            'status' => 'pending', 'description' => $data['description'] ?? null,
        ]);
    }

    public function complete(string $id, float $cost): void {
        $order = EamMaintenanceOrder::findOrFail($id);
        if ($order->status === 'completed') throw ValidationException::withMessages(['status' => 'Bu iş emri zaten tamamlanmış.']);

        $order->update(['status' => 'completed', 'completed_date' => now()->toDateString(), 'cost' => $cost]);
        
        // Varlığın durumunu güncelle (eğer arıza idiyse active'e dön)
        $asset = EamAsset::find($order->asset_id);
        if ($asset && $asset->status === 'broken') {
            $asset->update(['status' => 'active']);
        }
    }

    private function nextOrderNo(string $tenantId): string {
        $year = date('Y'); $prefix = "MNT-{$year}-";
        $last = EamMaintenanceOrder::where('tenant_id', $tenantId)->where('order_no', 'like', $prefix . '%')->orderByDesc('order_no')->value('order_no');
        $seq = 1;
        if ($last) { $parts = explode('-', $last); $seq = (int) end($parts) + 1; }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
