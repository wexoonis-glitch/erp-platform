<?php
namespace App\CountryPacks;

use App\Models\SysCountryPack;
use App\Models\SysTaxRegime;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CountryPackManager
{
    public function __construct(private readonly CountryPackRegistry $registry) {}

    /**
     * Tenant için aktif paketi döner. Pack kaydı yoksa ülkesinden çıkarır.
     */
    public function forTenant(Tenant $tenant): ?AbstractCountryPack
    {
        $packRow = SysCountryPack::where('tenant_id', $tenant->id)->where('is_active', true)->first();
        $code = $packRow?->pack_code ?? $tenant->country ?? 'TR';
        return $this->registry->get($code);
    }

    /**
     * Tenant'ı bir country pack'e bağlar; aktif vergi rejimlerini seed eder.
     */
    public function activate(string $tenantId, string $packCode, bool $force = false): SysCountryPack
    {
        $pack = $this->registry->get($packCode);
        if (!$pack) {
            throw ValidationException::withMessages(['pack_code' => "Bilinmeyen country pack: {$packCode}"]);
        }

        return \DB::transaction(function () use ($tenantId, $pack, $force) {
            $existing = SysCountryPack::where('tenant_id', $tenantId)->where('pack_code', $pack->code())->first();
            if ($existing && !$force) return $existing;

            $row = SysCountryPack::updateOrCreate(
                ['tenant_id' => $tenantId, 'pack_code' => $pack->code()],
                [
                    'id' => $existing?->id ?? (string) Str::uuid(),
                    'version' => '1.0.0',
                    'is_active' => true,
                    'settings' => [],
                ]
            );

            // Diğer pack'leri pasifleştir
            SysCountryPack::where('tenant_id', $tenantId)
                ->where('pack_code', '!=', $pack->code())
                ->update(['is_active' => false]);

            // Varsayılan vergi rejimlerini ekle (varsa atla)
            foreach ($pack->defaultTaxRegimes() as $tr) {
                SysTaxRegime::firstOrCreate(
                    ['tenant_id' => $tenantId, 'code' => $tr['code']],
                    [
                        'id' => (string) Str::uuid(),
                        'name' => $tr['name'],
                        'rate' => $tr['rate'],
                        'country' => $pack->code(),
                        'region' => $tr['region'] ?? null,
                        'is_inclusive' => false,
                        'applies_to' => 'both',
                        'is_default' => (bool) ($tr['is_default'] ?? false),
                        'status' => 'active',
                    ]
                );
            }

            // Tenant ve company_profile'ı senkronize et
            Tenant::where('id', $tenantId)->update([
                'country' => $pack->code(),
                'locale' => $pack->defaultLocale(),
                'currency' => $pack->defaultCurrency(),
                'timezone' => $pack->defaultTimezone(),
            ]);

            \App\Models\SysCompanyProfile::where('tenant_id', $tenantId)->update([
                'country' => $pack->code(),
                'locale' => $pack->defaultLocale(),
                'tax_label' => $pack->taxLabel(),
                'invoice_label' => $pack->invoiceLabel(),
            ]);

            return $row->fresh();
        });
    }

    public function deactivate(string $tenantId, string $packCode): void
    {
        SysCountryPack::where('tenant_id', $tenantId)->where('pack_code', $packCode)->update(['is_active' => false]);
    }
}
