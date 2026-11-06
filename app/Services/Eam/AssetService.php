<?php
namespace App\Services\Eam;
use App\Models\EamAsset;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AssetService {
    public function create(array $data): EamAsset {
        $tenantId = TenantContext::id();
        if (EamAsset::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Bu varlık kodu zaten kullanımda.']);
        }
        return EamAsset::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'code' => $data['code'], 'name' => $data['name'], 'category' => $data['category'],
            'serial_no' => $data['serial_no'] ?? null, 'purchase_date' => $data['purchase_date'] ?? null,
            'purchase_cost' => $data['purchase_cost'] ?? 0, 'status' => $data['status'] ?? 'active', 'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update(string $id, array $data): EamAsset {
        $asset = EamAsset::findOrFail($id);
        if (isset($data['code']) && $data['code'] !== $asset->code) {
            if (EamAsset::where('code', $data['code'])->where('id', '!=', $id)->exists()) {
                throw ValidationException::withMessages(['code' => 'Bu varlık kodu zaten kullanımda.']);
            }
        }
        $asset->update($data);
        return $asset->fresh();
    }

    public function delete(string $id): void {
        $asset = EamAsset::findOrFail($id);
        if ($asset->maintenanceOrders()->exists()) {
            throw ValidationException::withMessages(['asset' => 'Bu varlığın bakım kayıtları var, silinemez.']);
        }
        $asset->delete();
    }
}
