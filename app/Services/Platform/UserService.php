<?php

namespace App\Services\Platform;

use App\Events\EventBus;
use App\Extensions\ExtensionDispatcher;
use App\Models\User;
use App\Registry\FunctionRegistryService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class UserService
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public function __construct(
        private readonly ExtensionDispatcher $ext,
        private readonly EventBus $bus,
        private readonly FunctionRegistryService $registry,
    ) {}

    /**
     * PLT-IAM-0022 — Kullanıcı oluştur.
     * - Superadmin: herhangi bir tenant'a, herhangi bir rolde.
     * - Tenant admin: yalnızca kendi tenant'ına, yalnızca 'user' veya 'manager' rolünde.
     */
    public function create(array $data): User
    {
        $actor = TenantContext::isSuperadmin();
        $actorTenantId = TenantContext::id();

        // Tenant belirleme
        if ($actor) {
            if (empty($data['tenant_id'])) {
                throw ValidationException::withMessages(['tenant_id' => 'Superadmin oluştururken tenant_id zorunludur.']);
            }
            $targetTenantId = $data['tenant_id'];
        } else {
            $targetTenantId = $actorTenantId;
        }

        // Modül kontrolü (kullanıcı oluşturma IAM = PLT domaininde)
        $this->registry->assertEnabledForTenant('PLT-IAM-0022', $targetTenantId);

        // Rol kontrolü
        $role = $data['role'] ?? 'user';
        if (!$actor && !in_array($role, ['user', 'manager'], true)) {
            throw new LogicException('Tenant admin yalnızca user veya manager rolünde kullanıcı oluşturabilir.');
        }

        $payload = $this->ext->before('PLT-IAM-0022', $data);

        // Email benzersizliği (o tenant içinde)
        $exists = TenantContext::runAs(
            $targetTenantId,
            fn() => User::where('email', $payload['email'])->exists()
        );
        if ($exists) {
            throw ValidationException::withMessages(['email' => 'Bu e-posta zaten kullanımda.']);
        }

        $user = DB::transaction(function () use ($payload, $targetTenantId, $role) {
            return TenantContext::runAs($targetTenantId, function () use ($payload, $targetTenantId, $role) {
                return User::create([
                    'name'      => $payload['name'],
                    'email'     => $payload['email'],
                    'password'  => $payload['password'],
                    'tenant_id' => $targetTenantId,
                    'role'      => $role,
                    'status'    => 'active',
                ]);
            });
        });

        $this->bus->publish('user.created', [
            'id'        => $user->id,
            'email'     => $user->email,
            'role'      => $user->role,
            'tenant_id' => $user->tenant_id,
        ], $targetTenantId);

        $this->ext->after('PLT-IAM-0022', $payload, $user->toArray());

        return $user;
    }

    /**
     * Kullanıcı listele. RLS otomatik olarak yalnızca kendi tenant'ını gösterir.
     */
    public function listAll(): array
    {
        return User::orderBy('id')->get(['id', 'name', 'email', 'role', 'status', 'tenant_id'])->toArray();
    }

    public function show(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Kullanıcı güncelle.
     * - Superadmin: her şeyi.
     * - Tenant admin: kendi tenant'ındaki user/manager için name, password, status; rolü yalnızca user↔manager arasında değiştirebilir.
     */
    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $actor = TenantContext::isSuperadmin();
        $actorTenantId = TenantContext::id();

        if (!$actor && $user->tenant_id !== $actorTenantId) {
            throw new LogicException('Başka bir tenant\'ın kullanıcısını güncelleyemezsiniz.');
        }

        // Rol değişikliği kontrolü
        if (isset($data['role'])) {
            if (!$actor && !in_array($data['role'], ['user', 'manager'], true)) {
                throw new LogicException('Yalnızca user/manager rolü atayabilirsiniz.');
            }
            if (!$actor && in_array($user->role, ['admin', 'superadmin'], true)) {
                throw new LogicException('Admin/superadmin rolündeki kullanıcının rolünü değiştiremezsiniz.');
            }
        }

        $user->fill(array_intersect_key($data, array_flip(['name', 'email', 'password', 'role', 'status'])));
        $user->save();

        // Durum değiştiyse token'ları iptal et (güvenlik)
        if (isset($data['status']) && $data['status'] !== 'active') {
            $user->tokens()->delete();
        }

        $this->bus->publish('user.updated', [
            'id'     => $user->id,
            'fields' => array_keys($data),
        ], $user->tenant_id);

        return $user->fresh();
    }

    /**
     * Kullanıcıyı devre dışı bırak (soft delete).
     */
    public function delete(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->isSuperadmin()) {
            throw new LogicException('Superadmin silinemez.');
        }

        $user->status = 'disabled';
        $user->save();
        $user->tokens()->delete();

        $this->bus->publish('user.disabled', ['id' => $user->id], $user->tenant_id);
    }
}
