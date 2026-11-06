<?php
namespace App\Livewire\Settings;

use App\Models\SysRole;
use App\Models\SysRoleModule;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Roles extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';
    public string $message = '';
    public string $messageType = 'success';

    public bool $showForm = false;
    public ?string $editingId = null;

    public string $code = '', $name = '', $description = '';
    public bool $is_active = true;
    public array $selectedModules = [];

    // Tüm modül kodları + etiketleri (modules tablosundan function_registry.domain de olabilir)
    public array $allModules = [
        'PLT' => 'Platform (PLT)',
        'FIN' => 'Finans (FIN)',
        'INV' => 'Envanter (INV)',
        'PRC' => 'Satın Alma (PRC)',
        'OTC' => 'Satış (OTC)',
        'MFG' => 'Üretim (MFG)',
        'HRM' => 'İnsan Kaynakları (HRM)',
        'CRM' => 'CRM',
        'PPM' => 'Projeler (PPM)',
        'EAM' => 'Varlık Yönetimi (EAM)',
        'QM'  => 'Kalite (QM)',
        'LOG' => 'Lojistik (LOG)',
    ];

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate() {
        $this->resetForm();
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function openEdit(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () use ($id) {
            $r = SysRole::findOrFail($id);
            $this->editingId = $r->id;
            $this->code = $r->code;
            $this->name = $r->name;
            $this->description = $r->description ?? '';
            $this->is_active = $r->is_active;
            $this->selectedModules = $r->moduleCodes();
        });
        $this->showForm = true;
    }

    public function cancel() { $this->resetForm(); $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        $this->validate([
            'code' => 'required|string|max:50|regex:/^[a-z][a-z0-9_]*$/',
            'name' => 'required|string|max:255',
            'selectedModules' => 'array',
        ], [], ['code' => 'Kod', 'name' => 'Ad']);

        try {
            TenantContext::runAs($tid, function () use ($tid) {
                if ($this->editingId) {
                    $role = SysRole::findOrFail($this->editingId);
                    if ($role->is_system && $role->code !== $this->code) {
                        throw ValidationException::withMessages(['code' => 'Sistem rolünün kodu değiştirilemez.']);
                    }
                    $dup = SysRole::where('code', $this->code)->where('id', '!=', $role->id)->exists();
                    if ($dup) throw ValidationException::withMessages(['code' => 'Bu kod zaten kullanılıyor.']);

                    $role->update([
                        'name' => $this->name,
                        'description' => $this->description ?: null,
                        'is_active' => $this->is_active,
                    ]);
                } else {
                    if (SysRole::where('code', $this->code)->exists()) {
                        throw ValidationException::withMessages(['code' => 'Bu kod zaten kullanılıyor.']);
                    }
                    $role = SysRole::create([
                        'id' => (string) Str::uuid(),
                        'tenant_id' => $tid,
                        'code' => $this->code,
                        'name' => $this->name,
                        'description' => $this->description ?: null,
                        'is_system' => false,
                        'is_active' => $this->is_active,
                    ]);
                }

                // Modülleri senkronize et
                SysRoleModule::where('role_id', $role->id)->delete();
                foreach ($this->selectedModules as $m) {
                    if (!isset($this->allModules[$m])) continue;
                    SysRoleModule::create([
                        'id' => (string) Str::uuid(),
                        'tenant_id' => $tid,
                        'role_id' => $role->id,
                        'module_id' => $m,
                    ]);
                }
            });

            $this->messageType = 'success';
            $this->message = $this->editingId ? 'Rol güncellendi.' : 'Rol oluşturuldu.';
            $this->cancel();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            TenantContext::runAs($tid, function () use ($id) {
                $role = SysRole::findOrFail($id);
                if ($role->is_system) throw ValidationException::withMessages(['general' => 'Sistem rolleri silinemez.']);
                $inUse = User::where('role', $role->code)->exists();
                if ($inUse) throw ValidationException::withMessages(['general' => 'Bu rolü kullanan kullanıcılar var.']);
                $role->delete();
            });
            $this->messageType = 'success';
            $this->message = 'Rol silindi.';
        } catch (ValidationException $e) {
            $this->messageType = 'error';
            $this->message = collect($e->errors())->flatten()->first();
        }
    }

    private function resetForm() {
        $this->reset(['editingId', 'code', 'name', 'description', 'selectedModules']);
        $this->is_active = true;
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $roles = [];
        $userCounts = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$roles, &$userCounts) {
                $roles = SysRole::with('modules')->orderByDesc('is_system')->orderBy('name')->get()->map(fn($r) => [
                    'id' => $r->id,
                    'code' => $r->code,
                    'name' => $r->name,
                    'description' => $r->description,
                    'is_system' => $r->is_system,
                    'is_active' => $r->is_active,
                    'modules' => $r->moduleCodes(),
                ])->toArray();

                $counts = User::selectRaw('role, count(*) as c')->groupBy('role')->pluck('c', 'role')->toArray();
                $userCounts = $counts;
            });
        }

        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);

        return view('livewire.settings.roles', compact('roles', 'tenants', 'isSuper', 'userCounts'))->layout('layouts.app', ['title' => 'Roller & Yetkiler']);
    }
}
