<?php
namespace App\Livewire\Settings;
use App\Models\SysCompanyProfile;
use App\Models\Tenant;
use App\Services\Platform\CompanyProfileService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class CompanyProfile extends Component {
    use WithFileUploads;

    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';
    public bool $isSuper = false;
    public bool $saved = false;

    public string $legal_name = '', $display_name = '', $tax_no = '', $tax_office = '';
    public string $address = '', $city = '', $country = 'Türkiye', $postal_code = '';
    public string $phone = '', $email = '', $website = '';
    public string $default_currency = 'TRY';
    public int $fiscal_year_start_month = 1;
    public string $invoice_prefix = 'INV', $purchase_invoice_prefix = 'AP';
    public string $sales_order_prefix = 'SO', $purchase_order_prefix = 'PO';
    public bool $einvoice_registered = false;
    public string $einvoice_integrator = '';
    public string $notes = '';

    public $logo = null;
    public ?string $currentLogoUrl = null;

    public function mount() {
        $actor = Auth::user();
        $this->isSuper = $actor->isSuperadmin();

        if ($this->isSuper) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }

        $this->load();
    }

    private function effectiveTenantId(): ?string {
        if ($this->isSuper) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function updatedSelectedTenantId() { $this->load(); }

    public function load() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        TenantContext::runAs($tid, function () {
            $profile = app(CompanyProfileService::class)->getOrCreate();

            $this->legal_name = $profile->legal_name ?? '';
            $this->display_name = $profile->display_name ?? '';
            $this->tax_no = $profile->tax_no ?? '';
            $this->tax_office = $profile->tax_office ?? '';
            $this->address = $profile->address ?? '';
            $this->city = $profile->city ?? '';
            $this->country = $profile->country ?? 'Türkiye';
            $this->postal_code = $profile->postal_code ?? '';
            $this->phone = $profile->phone ?? '';
            $this->email = $profile->email ?? '';
            $this->website = $profile->website ?? '';
            $this->default_currency = $profile->default_currency ?? 'TRY';
            $this->fiscal_year_start_month = (int) ($profile->fiscal_year_start_month ?? 1);
            $this->invoice_prefix = $profile->invoice_prefix ?? 'INV';
            $this->purchase_invoice_prefix = $profile->purchase_invoice_prefix ?? 'AP';
            $this->sales_order_prefix = $profile->sales_order_prefix ?? 'SO';
            $this->purchase_order_prefix = $profile->purchase_order_prefix ?? 'PO';
            $this->einvoice_registered = (bool) ($profile->einvoice_registered ?? false);
            $this->einvoice_integrator = $profile->einvoice_integrator ?? '';
            $this->notes = $profile->notes ?? '';

            $this->currentLogoUrl = $profile->logo_path ? route('company.logo') . '?v=' . time() : null;
        });
    }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        $this->validate([
            'legal_name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'fiscal_year_start_month' => 'required|integer|between:1,12',
            'default_currency' => 'required|size:3',
            'logo' => 'nullable|image|max:2048', // 2MB
        ]);

        try {
            TenantContext::runAs($tid, function () {
                app(CompanyProfileService::class)->update([
                    'legal_name' => $this->legal_name,
                    'display_name' => $this->display_name,
                    'tax_no' => $this->tax_no ?: null,
                    'tax_office' => $this->tax_office ?: null,
                    'address' => $this->address ?: null,
                    'city' => $this->city ?: null,
                    'country' => $this->country ?: 'Türkiye',
                    'postal_code' => $this->postal_code ?: null,
                    'phone' => $this->phone ?: null,
                    'email' => $this->email ?: null,
                    'website' => $this->website ?: null,
                    'default_currency' => $this->default_currency,
                    'fiscal_year_start_month' => (int) $this->fiscal_year_start_month,
                    'invoice_prefix' => $this->invoice_prefix,
                    'purchase_invoice_prefix' => $this->purchase_invoice_prefix,
                    'sales_order_prefix' => $this->sales_order_prefix,
                    'purchase_order_prefix' => $this->purchase_order_prefix,
                    'einvoice_registered' => $this->einvoice_registered,
                    'einvoice_integrator' => $this->einvoice_integrator ?: null,
                    'notes' => $this->notes ?: null,
                ], $this->logo);
            });
            $this->logo = null;
            $this->saved = true;
            $this->load();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function removeLogo() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () {
            $profile = app(CompanyProfileService::class)->getForTenant();
            if ($profile) {
                app(CompanyProfileService::class)->update(['logo_path' => null]);
            }
        });
        $this->currentLogoUrl = null;
    }

    #[Layout('layouts.app')]
    public function render() {
        $tenants = [];
        if ($this->isSuper) {
            $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        }
        return view('livewire.settings.company-profile', compact('tenants'))->layout('layouts.app', ['title' => 'Şirket Profili']);
    }
}
