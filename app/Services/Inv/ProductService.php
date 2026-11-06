<?php
namespace App\Services\Inv;

use App\Models\InvProduct;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductService
{
    public function create(array $data): InvProduct
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        if (InvProduct::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Kod zaten kullanımda.']);
        }

        return InvProduct::create([
            'id'                   => (string) Str::uuid(),
            'tenant_id'            => $tenantId,
            'code'                 => $data['code'],
            'name'                 => $data['name'],
            'unit'                 => $data['unit'] ?? 'Adet',
            'track_stock'          => (bool) ($data['track_stock'] ?? true),
            'sales_price'          => $data['sales_price'] ?? 0,
            'purchase_price'       => $data['purchase_price'] ?? 0,
            'replacement_cost'     => $data['replacement_cost'] ?? 0,
            'target_margin_pct'    => $data['target_margin_pct'] ?? 30,
            'min_margin_pct'       => $data['min_margin_pct'] ?? 10,
            'overhead_per_unit'    => $data['overhead_per_unit'] ?? 0,
            'vat_rate'             => $data['vat_rate'] ?? 20,
            'tax_regime_id'        => $data['tax_regime_id'] ?? null,
            'cost_method'          => $data['cost_method'] ?? 'standard',
            'revenue_account_id'   => $data['revenue_account_id'],
            'inventory_account_id' => $data['inventory_account_id'] ?? null,
            'cogs_account_id'      => $data['cogs_account_id'] ?? null,
            'status'               => $data['status'] ?? 'active',
            'custom_fields'        => $data['custom_fields'] ?? [],
        ]);
    }
}
