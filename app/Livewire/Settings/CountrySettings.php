<?php
namespace App\Livewire\Settings;

use App\CountryPacks\CountryPackManager;
use App\CountryPacks\CountryPackRegistry;
use App\Models\SysCountry;
use App\Models\SysCountryPack;
use App\Models\SysTaxRegime;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class CountrySettings extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public string $packCode = '';
    public string $message = '';
    public string $messageType = 'success';

    // Yeni ülke ekleme
    public bool $showCountryForm = false;
    public string $new_code = '', $new_name = '', $new_currency = 'USD', $new_locale = 'en', $new_timezone = 'UTC', $new_tax_label = 'Tax';

    // Vergi formu
    public bool $showTaxForm = false;
    public ?string $editingTaxId = null;
    public string $tax_code = '', $tax_name = '', $tax_rate = '0', $tax_region = '';
    public bool $tax_is_inclusive = false, $tax_is_default = false;
    public string $tax_applies_to = 'both';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->loadPack();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function updatedSelectedTenantId() { $this->loadPack(); }

    private function loadPack() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () {
            $row = SysCountryPack::where('tenant_id', $this->selectedTenantId)->where('is_active', true)->first();
            $this->packCode = $row?->pack_code ?? '';
        });
    }

    public function activatePack(string $code) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            TenantContext::runAs($tid, fn() => app(CountryPackManager::class)->activate($tid, $code, true));
            $this->messageType = 'success';
            $this->message = "Country pack aktive edildi: {$code}";
            $this->loadPack();
        } catch (ValidationException $e) {
            $this->messageType = 'error';
            $this->message = collect($e->errors())->flatten()->first() ?? 'Hata.';
        }
    }

    // ============ YENİ ÜLKE EKLEME ============

    public function openCountryForm() {
        $this->reset(['new_code', 'new_name', 'new_tax_label']);
        $this->new_currency = 'USD';
        $this->new_locale = 'en';
        $this->new_timezone = 'UTC';
        $this->showCountryForm = true;
        $this->resetErrorBag();
    }

    public function cancelCountryForm() { $this->showCountryForm = false; $this->resetErrorBag(); }

    public function saveCountry() {
        $this->validate([
            'new_code' => 'required|string|size:2|regex:/^[A-Za-z]{2}$/',
            'new_name' => 'required|string|max:100',
            'new_currency' => 'required|string|size:3',
            'new_locale' => 'required|string|max:10',
            'new_timezone' => 'required|string|max:100',
            'new_tax_label' => 'required|string|max:50',
        ], [], [
            'new_code' => 'Ülke Kodu',
            'new_name' => 'Ülke Adı',
        ]);

        $code = strtoupper($this->new_code);
        if (SysCountry::find($code)) {
            $this->addError('new_code', "Bu ülke kodu zaten var: {$code}");
            return;
        }

        SysCountry::create([
            'code' => $code,
            'name' => $this->new_name,
            'default_currency' => strtoupper($this->new_currency),
            'default_locale' => strtolower($this->new_locale),
            'default_timezone' => $this->new_timezone,
            'tax_label' => $this->new_tax_label,
            'date_format' => 'Y-m-d',
            'number_format' => '1,234.56',
            'is_active' => true,
        ]);

        $this->messageType = 'success';
        $this->message = "Ülke eklendi: {$code} — {$this->new_name}. Artık bu ülke için pack aktive edebilirsiniz.";
        $this->cancelCountryForm();
    }

    public function deleteCountry(string $code) {
        $code = strtoupper($code);

        // Aktif tenant pack'i bu ülkeye bağlıysa silmeyi engelle
        $inUse = SysCountryPack::where('pack_code', $code)->where('is_active', true)->exists();
        if ($inUse) {
            $this->messageType = 'error';
            $this->message = "Bu ülke bir tenant tarafından kullanılıyor, silinemez: {$code}";
            return;
        }

        SysCountry::where('code', $code)->delete();
        $this->messageType = 'success';
        $this->message = "Ülke silindi: {$code}";
    }

    // ============ VERGİ ============

    public function openCreateTax() {
        $this->editingTaxId = null;
        $this->reset(['tax_code', 'tax_name', 'tax_region']);
        $this->tax_rate = '0';
        $this->tax_is_inclusive = false;
        $this->tax_is_default = false;
        $this->tax_applies_to = 'both';
        $this->showTaxForm = true;
    }

    public function openEditTax(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () use ($id) {
            $t = SysTaxRegime::findOrFail($id);
            $this->editingTaxId = $t->id;
            $this->tax_code = $t->code;
            $this->tax_name = $t->name;
            $this->tax_rate = (string) $t->rate;
            $this->tax_region = $t->region ?? '';
            $this->tax_is_inclusive = $t->is_inclusive;
            $this->tax_is_default = $t->is_default;
            $this->tax_applies_to = $t->applies_to;
        });
        $this->showTaxForm = true;
    }

    public function cancelTax() { $this->showTaxForm = false; $this->resetErrorBag(); }

    public function saveTax() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate([
            'tax_code' => 'required|string|max:50',
            'tax_name' => 'required|string|max:100',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'tax_applies_to' => 'required|in:goods,services,both',
        ]);

        try {
            TenantContext::runAs($tid, function () use ($tid) {
                $tenant = Tenant::find($tid);
                $pack = app(CountryPackManager::class)->forTenant($tenant);
                $country = $pack?->code() ?? 'TR';

                if ($this->tax_is_default) {
                    SysTaxRegime::where('tenant_id', $tid)->update(['is_default' => false]);
                }

                $data = [
                    'name' => $this->tax_name,
                    'rate' => (float) $this->tax_rate,
                    'country' => $country,
                    'region' => $this->tax_region ?: null,
                    'is_inclusive' => $this->tax_is_inclusive,
                    'applies_to' => $this->tax_applies_to,
                    'is_default' => $this->tax_is_default,
                    'status' => 'active',
                ];

                if ($this->editingTaxId) {
                    SysTaxRegime::findOrFail($this->editingTaxId)->update($data + ['code' => $this->tax_code]);
                } else {
                    SysTaxRegime::create($data + [
                        'id' => (string) Str::uuid(),
                        'tenant_id' => $tid,
                        'code' => $this->tax_code,
                    ]);
                }
            });
            $this->cancelTax();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function deleteTax(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, fn() => SysTaxRegime::findOrFail($id)->delete());
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $registry = app(CountryPackRegistry::class);

        // Tüm pack'ler (kod + generic)
        $allPacks = $registry->all();
        $packList = [];
        foreach ($allPacks as $code => $p) {
            $packList[] = [
                'code' => $p->code(),
                'name' => $p->name(),
                'tax_label' => $p->taxLabel(),
                'currency' => $p->defaultCurrency(),
                'locale' => $p->defaultLocale(),
                'supports_einvoice' => $p->supportsEInvoiceXml(),
                'is_advanced' => $registry->isAdvanced($p->code()),
            ];
        }

        $taxRegimes = [];
        $activePack = null;
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$taxRegimes, &$activePack, $tid) {
                $taxRegimes = SysTaxRegime::orderByDesc('is_default')->orderBy('code')->get()->map(fn($t) => [
                    'id' => $t->id, 'code' => $t->code, 'name' => $t->name,
                    'rate' => (float) $t->rate, 'country' => $t->country, 'region' => $t->region,
                    'is_default' => $t->is_default, 'is_inclusive' => $t->is_inclusive,
                    'applies_to' => $t->applies_to, 'status' => $t->status,
                ])->toArray();

                $activePack = SysCountryPack::where('tenant_id', $tid)->where('is_active', true)->value('pack_code');
            });
        }

        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);

        return view('livewire.settings.country-settings', compact('packList', 'taxRegimes', 'activePack', 'tenants', 'isSuper'))->layout('layouts.app', ['title' => 'Ülke & Vergi Ayarları']);
    }
}
