<?php
namespace App\Services\Platform;
use App\Models\SysCompanyProfile;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CompanyProfileService {
    public function getForTenant(): ?SysCompanyProfile {
        $tenantId = TenantContext::id();
        if (!$tenantId) return null;
        return SysCompanyProfile::where('tenant_id', $tenantId)->first();
    }

    public function getOrCreate(): SysCompanyProfile {
        $profile = $this->getForTenant();
        if ($profile) return $profile;

        $tenantId = TenantContext::id();
        return SysCompanyProfile::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'legal_name' => 'Tanımsız Şirket',
            'display_name' => 'Tanımsız Şirket',
            'country' => 'Türkiye',
            'default_currency' => 'TRY',
            'fiscal_year_start_month' => 1,
            'invoice_prefix' => 'INV',
            'purchase_invoice_prefix' => 'AP',
            'sales_order_prefix' => 'SO',
            'purchase_order_prefix' => 'PO',
            'einvoice_registered' => false,
        ]);
    }

    public function update(array $data, ?UploadedFile $logo = null): SysCompanyProfile {
        $profile = $this->getOrCreate();

        if ($logo) {
            // Eski logoyu sil
            if ($profile->logo_path && Storage::disk('local')->exists($profile->logo_path)) {
                Storage::disk('local')->delete($profile->logo_path);
            }
            $path = $logo->storeAs(
                "company/{$profile->tenant_id}",
                'logo.' . $logo->getClientOriginalExtension(),
                'local'
            );
            $data['logo_path'] = $path;
        }

        $profile->fill($data);
        $profile->save();

        return $profile->fresh();
    }
}
