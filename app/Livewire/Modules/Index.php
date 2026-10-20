<?php

namespace App\Livewire\Modules;

use App\Models\FunctionRegistry;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Registry\FunctionRegistryService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';

    public function mount()
    {
        $user = Auth::user();
        if ($user->isSuperadmin()) {
            $first = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::orderBy('code')->first(),
                superadmin: true
            );
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $user->tenant_id ?? '';
        }
    }

    public function install(string $moduleId)
    {
        if (!Auth::check()) {
            $this->addError('general', 'Oturum sona ermiş. Lütfen tekrar giriş yapın.');
            return;
        }

        $actor = Auth::user();
        if (!$actor->isSuperadmin()) {
            $this->addError('general', 'Yalnızca superadmin modül kurabilir.');
            return;
        }

        $tenantId = $this->selectedTenantId;
        if (empty($tenantId)) {
            $this->addError('general', 'Tenant seçmelisiniz.');
            return;
        }

        // Zaten kurulu mu?
        $exists = TenantContext::runAs($tenantId, function () use ($tenantId, $moduleId) {
            return TenantModule::where('tenant_id', $tenantId)
                ->where('module_id', $moduleId)
                ->exists();
        });

        if ($exists) {
            $this->addError('general', "Modül zaten kurulu: {$moduleId}");
            return;
        }

        // Katalogda var mı?
        $functionCount = FunctionRegistry::where('domain', $moduleId)
            ->where('status', 'active')
            ->count();

        if ($functionCount === 0) {
            $this->addError('general', "Modül katalogda yok: {$moduleId}");
            return;
        }

        // Kur
        TenantContext::runAs($tenantId, function () use ($tenantId, $moduleId) {
            TenantModule::create([
                'tenant_id'    => $tenantId,
                'module_id'    => $moduleId,
                'version'      => '1.0.0',
                'status'       => 'active',
                'config'       => [],
                'installed_at' => now(),
            ]);
        });

        // Cache invalidate
        app(FunctionRegistryService::class)->invalidateTenantModules($tenantId);

        session()->flash('message', "Modül kuruldu: {$moduleId}");
    }

    public function uninstall(string $moduleId)
    {
        if (!Auth::check()) {
            $this->addError('general', 'Oturum sona ermiş. Lütfen tekrar giriş yapın.');
            return;
        }

        $actor = Auth::user();
        if (!$actor->isSuperadmin()) {
            $this->addError('general', 'Yalnızca superadmin modül kaldırabilir.');
            return;
        }

        if ($moduleId === 'PLT') {
            $this->addError('general', 'PLT modülü kaldırılamaz.');
            return;
        }

        $tenantId = $this->selectedTenantId;
        if (empty($tenantId)) {
            $this->addError('general', 'Tenant seçmelisiniz.');
            return;
        }

        TenantContext::runAs($tenantId, function () use ($tenantId, $moduleId) {
            TenantModule::where('tenant_id', $tenantId)
                ->where('module_id', $moduleId)
                ->delete();
        });

        app(FunctionRegistryService::class)->invalidateTenantModules($tenantId);

        session()->flash('message', "Modül kaldırıldı: {$moduleId}");
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();

        $catalog = FunctionRegistry::select('domain')
            ->selectRaw('COUNT(*) as function_count')
            ->where('status', 'active')
            ->groupBy('domain')
            ->orderBy('domain')
            ->get()
            ->map(fn($r) => ['module_id' => $r->domain, 'function_count' => (int) $r->function_count])
            ->toArray();

        $installed = [];
        if ($this->selectedTenantId) {
            $tenantId = $this->selectedTenantId;
            $installed = TenantContext::runAs(
                $tenantId,
                fn() => TenantModule::where('tenant_id', $tenantId)
                    ->get(['module_id', 'version', 'status', 'installed_at'])
                    ->toArray()
            );
        }

        $installedCodes = array_column($installed, 'module_id');

        $tenants = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::orderBy('code')->get(['id', 'code', 'name'])->toArray(),
                superadmin: true
            );
        }

        return view('livewire.modules.index', [
            'title'          => 'Modüller',
            'isSuper'        => $isSuper,
            'catalog'        => $catalog,
            'installed'      => $installed,
            'installedCodes' => $installedCodes,
            'tenants'        => $tenants,
        ]);
    }
}
