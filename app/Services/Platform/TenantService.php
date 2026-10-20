<?php

namespace App\Services\Platform;

use App\Events\EventBus;
use App\Extensions\ExtensionDispatcher;
use App\Models\Tenant;
use App\Models\TenantModule;
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
    ) {}

    /**
     * PLT-TEN-0001 — Yeni tenant oluştur.
     *
     * Not: Tenant oluşturma "platform-level" bir işlemdir. Henüz tenant yokken
     * çağrıldığı için sadece superadmin tarafından çağrılabilir.
     * assertEnabledForTenant çağrısı bu fonksiyon için uygulanmaz; ancak
     * superadmin kontrolü onun yerini alır.
     */
    public function create(array $data): Tenant
    {
        // 1) Superadmin kontrolü
        if (!TenantContext::isSuperadmin()) {
            throw new LogicException(
                'PLT-TEN-0001: Tenant oluşturma yalnızca superadmin tarafından çağrılabilir.'
            );
        }

        // 2) before hook'ları
        $payload = $this->ext->before('PLT-TEN-0001', $data);

        // 3) Zorunlu alanlar
        foreach (['code', 'name'] as $field) {
            if (empty($payload[$field])) {
                throw ValidationException::withMessages([
                    $field => "Alan zorunludur: {$field}",
                ]);
            }
        }

        // 4) Kod benzersiz mi (superadmin bağlamında kontrol)
        $exists = TenantContext::runAs(
            self::NULL_TENANT,
            fn() => Tenant::where('code', $payload['code'])->exists(),
            superadmin: true
        );
        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Bu tenant kodu zaten kullanımda.',
            ]);
        }

        // 5) Tenant oluştur (transaction: tenant + modül birlikte)
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

            // Yeni tenant'a PLT modülünü otomatik kur
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

        // 6) Event publish
        $this->bus->publish('tenant.created', [
            'id'   => $tenant->id,
            'code' => $tenant->code,
            'name' => $tenant->name,
            'tier' => $tenant->tier,
        ], $tenant->id);

        // 7) after hook'ları
        $this->ext->after('PLT-TEN-0001', $payload, $tenant->toArray());

        return $tenant;
    }
}
