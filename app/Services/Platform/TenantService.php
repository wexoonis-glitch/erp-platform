<?php

namespace App\Services\Platform;

use App\Events\EventBus;
use App\Extensions\ExtensionDispatcher;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Registry\FunctionRegistryService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

final class TenantService
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public function __construct(
        private readonly ExtensionDispatcher $ext,
        private readonly EventBus $bus,
        private readonly FunctionRegistryService $registry,
    ) {}

    /**
     * PLT-TEN-0001 — Yeni tenant oluştur.
     * Yalnızca superadmin.
     */
    public function create(array $data): Tenant
    {
        if (!TenantContext::isSuperadmin()) {
            throw new LogicException('PLT-TEN-0001: Yalnızca superadmin tenant oluşturabilir.');
        }

        $payload = $this->ext->before('PLT-TEN-0001', $data);

        foreach (['code', 'name'] as $field) {
            if (empty($payload[$field])) {
                throw ValidationException::withMessages([$field => "Alan zorunludur: {$field}"]);
            }
        }

        $exists = TenantContext::runAs(
            self::NULL_TENANT,
            fn() => Tenant::where('code', $payload['code'])->exists(),
            superadmin: true
        );
        if ($exists) {
            throw ValidationException::withMessages(['code' => 'Bu tenant kodu zaten kullanımda.']);
        }

        $tenant = DB::transaction(function () use ($payload) {
            $tenant = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::create([
                    'id'     => (string) Str::uuid(),
                    'code'   => $payload['code'],
                    'name'   => $payload['name'],
                    'tier'   => $payload['tier'] ?? 'shared',
                    'status' => 'active',
                    'config' => $payload['config'] ?? [],
                ]),
                superadmin: true
            );

            TenantContext::runAs($tenant->id, function () use ($tenant) {
                TenantModule::create([
                    'tenant_id'    => $tenant->id,
                    'module_id'    => 'PLT',
                    'version'      => '1.0.0',
                    'status'       => 'active',
                    'config'       => [],
                    'installed_at' => now(),
                ]);
            });

            return $tenant;
        });

        $this->bus->publish('tenant.created', [
            'id'   => $tenant->id,
            'code' => $tenant->code,
            'name' => $tenant->name,
            'tier' => $tenant->tier,
        ], $tenant->id);

        $this->ext->after('PLT-TEN-0001', $payload, $tenant->toArray());

        return $tenant;
    }

    /**
     * PLT-TEN-0002 — Tenant güncelleme.
     * - Superadmin: herhangi bir tenant'ın her alanını güncelleyebilir.
     * - Tenant admin: yalnızca kendi tenant'ının name ve config'ini güncelleyebilir.
     */
    public function update(string $tenantId, array $data): Tenant
    {
        $actorTenantId = TenantContext::id();
        $isSuper       = TenantContext::isSuperadmin();

        if (!$isSuper && $actorTenantId !== $tenantId) {
            throw new LogicException('PLT-TEN-0002: Başka bir tenant\'ı güncelleyemezsiniz.');
        }

        // Modül kontrolü (superadmin için tenant'ın PLT'si kurulu olmalı)
        $this->registry->assertEnabledForTenant('PLT-TEN-0002', $tenantId);

        $payload = $this->ext->before('PLT-TEN-0002', $data);

        // Tenant admin yalnızca name ve config değiştirebilir
        if (!$isSuper) {
            $allowed = ['name', 'config'];
            $payload = array_intersect_key($payload, array_flip($allowed));
        }

        $tenant = TenantContext::runAs(
            self::NULL_TENANT,
            fn() => Tenant::findOrFail($tenantId),
            superadmin: true
        );

        $before = $tenant->toArray();

        TenantContext::runAs(
            self::NULL_TENANT,
            function () use ($tenant, $payload) {
                $tenant->fill($payload);
                $tenant->save();
            },
            superadmin: true
        );

        $this->bus->publish('tenant.updated', [
            'id'     => $tenant->id,
            'before' => $before,
            'after'  => $tenant->fresh()->toArray(),
        ], $tenant->id);

        $this->ext->after('PLT-TEN-0002', $payload, $tenant->fresh()->toArray());

        return $tenant->fresh();
    }

    /**
     * PLT-TEN-0003 — Tenant askıya alma.
     * Yalnızca superadmin.
     */
    public function suspend(string $tenantId): Tenant
    {
        return $this->setStatus($tenantId, 'suspended');
    }

    /**
     * PLT-TEN-0003 — Tenant aktive etme.
     * Yalnızca superadmin.
     */
    public function activate(string $tenantId): Tenant
    {
        return $this->setStatus($tenantId, 'active');
    }



    /**
     * PLT-TEN-0003 — Tenant arşivleme (soft delete).
     * Yalnızca superadmin.
     */
    public function archive(string $tenantId): Tenant
    {
        return $this->setStatus($tenantId, 'archived');
    }

    private function setStatus(string $tenantId, string $status): Tenant
    {
        if (!TenantContext::isSuperadmin()) {
            throw new LogicException('PLT-TEN-0003: Yalnızca superadmin tenant durumunu değiştirebilir.');
        }

        $this->registry->assertEnabledForTenant('PLT-TEN-0003', $tenantId);

        $tenant = TenantContext::runAs(
            self::NULL_TENANT,
            fn() => Tenant::findOrFail($tenantId),
            superadmin: true
        );

        $oldStatus = $tenant->status;
        TenantContext::runAs(
            self::NULL_TENANT,
            function () use ($tenant, $status) {
                $tenant->status = $status;
                $tenant->save();
            },
            superadmin: true
        );

        $this->bus->publish('tenant.status_changed', [
            'id'         => $tenant->id,
            'old_status' => $oldStatus,
            'new_status' => $status,
        ], $tenant->id);

        return $tenant->fresh();
    }

    /**
     * Superadmin için tenant listesi.
     */
    public function listAll(): array
    {
        if (!TenantContext::isSuperadmin()) {
            throw new LogicException('Tenant listesi yalnızca superadmin tarafından görülebilir.');
        }

        return TenantContext::runAs(
            self::NULL_TENANT,
            fn() => Tenant::orderBy('code')->get()->toArray(),
            superadmin: true
        );
    }

    public function show(string $tenantId): Tenant
    {
        $isSuper       = TenantContext::isSuperadmin();
        $actorTenantId = TenantContext::id();

        if (!$isSuper && $actorTenantId !== $tenantId) {
            throw new LogicException('Başka bir tenant\'ı göremezsiniz.');
        }

        return TenantContext::runAs(
            self::NULL_TENANT,
            fn() => Tenant::findOrFail($tenantId),
            superadmin: true
        );
    }
}
