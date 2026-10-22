<?php

namespace App\Livewire\Plugins;

use App\Models\Plugin;
use App\Models\Tenant;
use App\Plugins\PluginManager;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';

    public function mount()
    {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::orderBy('code')->first(),
                superadmin: true
            );
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
    }

    public function install(string $pluginId)
    {
        $actor = Auth::user();
        if ($actor->isSuperadmin() && empty($this->selectedTenantId)) {
            $this->addError('general', 'Tenant seçmelisiniz.');
            return;
        }
        $tenantId = $actor->isSuperadmin() ? $this->selectedTenantId : $actor->tenant_id;

        try {
            /** @var PluginManager $manager */
            $manager = app(PluginManager::class);
            $manifests = $manager->discoverFromDisk();

            if (!isset($manifests[$pluginId])) {
                $this->addError('general', "Manifest bulunamadı: {$pluginId}");
                return;
            }

            $manager->install($tenantId, $manifests[$pluginId]);
            session()->flash('message', "Eklenti kuruldu: {$pluginId}");
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function enable(string $pluginId)
    {
        $actor = Auth::user();
        $tenantId = $actor->isSuperadmin() ? $this->selectedTenantId : $actor->tenant_id;
        if (empty($tenantId)) {
            $this->addError('general', 'Tenant seçmelisiniz.');
            return;
        }

        app(PluginManager::class)->enable($tenantId, $pluginId);
        session()->flash('message', "Eklenti aktif: {$pluginId}");
    }

    public function disable(string $pluginId)
    {
        $actor = Auth::user();
        $tenantId = $actor->isSuperadmin() ? $this->selectedTenantId : $actor->tenant_id;
        if (empty($tenantId)) {
            $this->addError('general', 'Tenant seçmelisiniz.');
            return;
        }

        app(PluginManager::class)->disable($tenantId, $pluginId);
        session()->flash('message', "Eklenti devre dışı: {$pluginId}");
    }

    public function uninstall(string $pluginId)
    {
        $actor = Auth::user();
        $tenantId = $actor->isSuperadmin() ? $this->selectedTenantId : $actor->tenant_id;
        if (empty($tenantId)) {
            $this->addError('general', 'Tenant seçmelisiniz.');
            return;
        }

        app(PluginManager::class)->uninstall($tenantId, $pluginId);
        session()->flash('message', "Eklenti kaldırıldı: {$pluginId}");
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();

        /** @var PluginManager $manager */
        $manager = app(PluginManager::class);
        $available = $manager->discoverFromDisk();

        // Kurulu eklentiler (RLS-aware)
        $installed = Plugin::query()
            ->when($this->selectedTenantId, fn($q) => $q->where('tenant_id', $this->selectedTenantId))
            ->orderBy('plugin_id')
            ->get(['id', 'tenant_id', 'plugin_id', 'name', 'version', 'vendor', 'status', 'installed_at'])
            ->keyBy('plugin_id')
            ->toArray();

        $tenants = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::orderBy('code')->get(['id', 'code', 'name'])->toArray(),
                superadmin: true
            );
        }

        return view('livewire.plugins.index', [
            'title'     => 'Plugins',
            'isSuper'   => $isSuper,
            'available' => $available,
            'installed' => $installed,
            'tenants'   => $tenants,
        ]);
    }
}
