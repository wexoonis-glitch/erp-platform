<?php
namespace App\Services\Fin;

use App\Models\InvProduct;
use App\Models\SysTaxRegime;
use App\Support\TenantContext;

final class TaxResolverService
{
    /**
     * Bir ürün için hangi vergi rejimini uygulayacağını çözer.
     * Sıra:
     *   1. Ürünün kendi tax_regime_id'si (ülkeye uygun olan)
     *   2. Tenant'ın is_default tax_regime'i (ülke pack'inden)
     *   3. Ürünün legacy vat_rate'i (decimal fallback)
     *   4. 0
     *
     * @return array{regime_id: ?string, rate: float, label: ?string, is_inclusive: bool}
     */
    public function resolveForProduct(?string $productId): array
    {
        $tenantId = TenantContext::id();

        // 1) Ürünün kendi vergi rejimi
        if ($productId) {
            $product = InvProduct::find($productId);
            if ($product && $product->tax_regime_id) {
                $regime = SysTaxRegime::find($product->tax_regime_id);
                if ($regime && $regime->status === 'active') {
                    return [
                        'regime_id'    => $regime->id,
                        'rate'         => (float) $regime->rate,
                        'label'        => $regime->name,
                        'is_inclusive' => (bool) $regime->is_inclusive,
                    ];
                }
            }
        }

        // 2) Tenant'ın default vergisi
        if ($tenantId) {
            $default = SysTaxRegime::where('tenant_id', $tenantId)
                ->where('is_default', true)
                ->where('status', 'active')
                ->first();

            if ($default) {
                // Eğer ürünün legacy vat_rate'i sıfır değilse ve default 0 ise ürünü tercih et
                if ($productId) {
                    $product = InvProduct::find($productId);
                    if ($product && (float) $product->vat_rate > 0 && (float) $default->rate === 0.0) {
                        return [
                            'regime_id'    => null,
                            'rate'         => (float) $product->vat_rate,
                            'label'        => null,
                            'is_inclusive' => false,
                        ];
                    }
                }
                return [
                    'regime_id'    => $default->id,
                    'rate'         => (float) $default->rate,
                    'label'        => $default->name,
                    'is_inclusive' => (bool) $default->is_inclusive,
                ];
            }
        }

        // 3) Ürünün legacy vat_rate'i
        if ($productId) {
            $product = InvProduct::find($productId);
            if ($product) {
                return [
                    'regime_id'    => null,
                    'rate'         => (float) $product->vat_rate,
                    'label'        => null,
                    'is_inclusive' => false,
                ];
            }
        }

        return ['regime_id' => null, 'rate' => 0.0, 'label' => null, 'is_inclusive' => false];
    }

    /**
     * Aktif tenant'ın ülke pack'ine uygun vergi rejimlerini listeler.
     */
    public function availableForTenant(): array
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) return [];

        return SysTaxRegime::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get()
            ->map(fn($r) => [
                'id'    => $r->id,
                'code'  => $r->code,
                'name'  => $r->name,
                'rate'  => (float) $r->rate,
                'region'=> $r->region,
                'label' => $r->name . ' (%' . rtrim(rtrim(number_format((float) $r->rate, 4, '.', ''), '0'), '.') . ')',
            ])
            ->toArray();
    }
}
