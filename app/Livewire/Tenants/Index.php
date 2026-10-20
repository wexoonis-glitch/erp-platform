<?php

namespace App\Livewire\Tenants;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Index extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public bool $showCreate = false;

    #[Validate('required|string|max:50|regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/')]
    public string $code = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|in:shared,schema,dedicated')]
    public string $tier = 'shared';

    // Edit state
    public ?string $editingId = null;
    public string $editName = '';
    public string $editTier = 'shared';
    public string $editConfigJson = '{}';

    public function mount()
    {
        if (!Auth::user()->isSuperadmin()) {
            abort(403, 'Yalnızca superadmin tenant yönetebilir.');
        }
    }

    public function create()
    {
        $this->validate();

        $tenant = TenantContext::runAs(self::NULL_TENANT, function () {
            return Tenant::create([
                'id'     => (string) Str::uuid(),
                'code'   => $this->code,
                'name'   => $this->name,
                'tier'   => $this->tier,
                'status' => 'active',
                'config' => [],
            ]);
        }, superadmin: true);

        TenantContext::runAs($tenant->id, function () use ($tenant) {
            \App\Models\TenantModule::create([
                'tenant_id'    => $tenant->id,
                'module_id'    => 'PLT',
                'version'      => '1.0.0',
                'status'       => 'active',
                'config'       => [],
                'installed_at' => now(),
            ]);
        });

        $this->reset(['code', 'name', 'tier', 'showCreate']);
    }

    public function toggleStatus(string $id)
    {
        $tenant = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::findOrFail($id), superadmin: true);
        if ($tenant->status === 'archived') {
            return;
        }
        $newStatus = $tenant->status === 'active' ? 'suspended' : 'active';

        TenantContext::runAs(self::NULL_TENANT, function () use ($tenant, $newStatus) {
            $tenant->status = $newStatus;
            $tenant->save();
        }, superadmin: true);
    }

    public function openEdit(string $id)
    {
        $tenant = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::findOrFail($id), superadmin: true);
        $this->editingId = $tenant->id;
        $this->editName = $tenant->name;
        $this->editTier = $tenant->tier;
        $this->editConfigJson = json_encode($tenant->config ?? [], JSON_PRETTY_PRINT);
        $this->resetErrorBag();
    }

    public function cancelEdit()
    {
        $this->editingId = null;
        $this->editName = '';
        $this->editTier = 'shared';
        $this->editConfigJson = '{}';
        $this->resetErrorBag();
    }

    public function saveEdit()
    {
        $this->validate([
            'editName'       => 'required|string|max:255',
            'editTier'       => 'required|in:shared,schema,dedicated',
            'editConfigJson' => 'nullable|string',
        ]);

        $config = [];
        if (!empty(trim($this->editConfigJson))) {
            $decoded = json_decode($this->editConfigJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('editConfigJson', 'Geçersiz JSON: ' . json_last_error_msg());
                return;
            }
            if (!is_array($decoded)) {
                $this->addError('editConfigJson', 'JSON bir obje olmalı (ör. {"currency":"TRY"})');
                return;
            }
            $config = $decoded;
        }

        TenantContext::runAs(self::NULL_TENANT, function () use ($config) {
            $t = Tenant::findOrFail($this->editingId);
            $t->name = $this->editName;
            $t->tier = $this->editTier;
            $t->config = $config;
            $t->save();
        }, superadmin: true);

        $this->cancelEdit();
    }

    public function archive(string $id)
    {
        TenantContext::runAs(self::NULL_TENANT, function () use ($id) {
            $t = Tenant::findOrFail($id);
            $t->status = 'archived';
            $t->save();
        }, superadmin: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $tenants = TenantContext::runAs(self::NULL_TENANT, function () {
            return Tenant::orderBy('code')->get()->toArray();
        }, superadmin: true);

        return view('livewire.tenants.index', [
            'title'   => 'Tenants',
            'tenants' => $tenants,
        ]);
    }
}
