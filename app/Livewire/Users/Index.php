<?php
namespace App\Livewire\Users;

use App\Models\SysRole;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Index extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public bool $showCreate = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('required|string')]
    public string $role = 'user';

    public string $tenant_id = '';

    public ?string $editingId = null;
    public string $editName = '';
    public string $editEmail = '';
    public string $editPassword = '';
    public string $editRole = 'user';
    public string $editStatus = 'active';

    public function mount() {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'], true)) abort(403);
        if (!$user->isSuperadmin()) {
            $this->tenant_id = $user->tenant_id ?? '';
        }
    }

    public function create() {
        $this->validate();
        $actor = Auth::user();
        $actorIsSuper = $actor->isSuperadmin();
        $targetTenantId = $actorIsSuper ? $this->tenant_id : $actor->tenant_id;
        if (empty($targetTenantId)) { $this->addError('tenant_id', 'Tenant seçmelisiniz.'); return; }

        $roleExists = TenantContext::runAs($targetTenantId, fn() => SysRole::where('code', $this->role)->where('is_active', true)->exists());
        if (!$roleExists) { $this->addError('role', 'Geçersiz rol.'); return; }

        $exists = TenantContext::runAs($targetTenantId, fn() => User::where('email', $this->email)->exists());
        if ($exists) { $this->addError('email', 'Bu e-posta zaten kullanımda.'); return; }

        TenantContext::runAs($targetTenantId, function () use ($targetTenantId) {
            User::create([
                'name' => $this->name, 'email' => $this->email,
                'password' => $this->password, 'tenant_id' => $targetTenantId,
                'role' => $this->role, 'status' => 'active',
            ]);
        });

        $this->reset(['name', 'email', 'password', 'role', 'showCreate']);
        if ($actorIsSuper) $this->reset('tenant_id');
        else $this->tenant_id = $actor->tenant_id;
    }

    public function toggleStatus(string $id) {
        $user = User::findOrFail($id);
        if ($user->isSuperadmin()) return;
        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();
    }

    public function openEdit(string $id) {
        $user = User::findOrFail($id);
        $this->editingId = (string) $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editPassword = '';
        $this->editRole = $user->role;
        $this->editStatus = $user->status;
        $this->resetErrorBag();
    }

    public function cancelEdit() {
        $this->reset(['editingId', 'editName', 'editEmail', 'editPassword', 'editRole', 'editStatus']);
        $this->resetErrorBag();
    }

    public function saveEdit() {
        $actor = Auth::user();
        $actorIsSuper = $actor->isSuperadmin();
        $rules = [
            'editName' => 'required|string|max:255',
            'editEmail' => 'required|email|max:255',
            'editStatus' => 'required|in:active,suspended,disabled',
            'editRole' => 'required|string',
        ];
        if (!empty($this->editPassword)) $rules['editPassword'] = 'string|min:8';
        $this->validate($rules);

        $user = User::findOrFail($this->editingId);

        if (!$actorIsSuper && $user->isSuperadmin()) {
            $this->addError('editRole', 'Bu kullanıcıyı düzenleme yetkiniz yok.'); return;
        }

        $roleExists = TenantContext::runAs($user->tenant_id, fn() => SysRole::where('code', $this->editRole)->where('is_active', true)->exists());
        if (!$roleExists) { $this->addError('editRole', 'Geçersiz rol.'); return; }

        if ($this->editEmail !== $user->email) {
            $exists = TenantContext::runAs($user->tenant_id, fn() => User::where('email', $this->editEmail)->where('id', '!=', $user->id)->exists());
            if ($exists) { $this->addError('editEmail', 'Bu e-posta zaten kullanımda.'); return; }
        }

        $user->name = $this->editName;
        $user->email = $this->editEmail;
        $user->role = $this->editRole;
        $user->status = $this->editStatus;
        if (!empty($this->editPassword)) $user->password = $this->editPassword;
        $user->save();

        if ($this->editStatus !== 'active') $user->tokens()->delete();
        $this->cancelEdit();
    }

    public function delete(string $id) {
        $user = User::findOrFail($id);
        if ($user->isSuperadmin()) return;
        $user->status = 'disabled';
        $user->save();
    }

    #[Layout('layouts.app')]
    public function render() {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();
        $users = User::orderBy('id')->get(['id', 'name', 'email', 'role', 'status', 'tenant_id'])->toArray();

        $tenants = [];
        $roles = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
            if ($this->tenant_id) {
                $roles = TenantContext::runAs($this->tenant_id, fn() => SysRole::where('is_active', true)->orderBy('name')->get(['code', 'name'])->toArray());
            }
        } else {
            $roles = TenantContext::runAs($actor->tenant_id, fn() => SysRole::where('is_active', true)->orderBy('name')->get(['code', 'name'])->toArray());
        }

        return view('livewire.users.index', [
            'isSuper' => $isSuper,
            'users'   => $users,
            'tenants' => $tenants,
            'roles'   => $roles,
        ])->layout('layouts.app', ['title' => 'Kullanıcılar']);
    }
}
