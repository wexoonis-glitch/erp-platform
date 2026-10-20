<?php

namespace App\Registry;

use App\Models\FunctionRegistry;
use App\Models\TenantModule;
use App\Registry\Exceptions\FunctionDeprecatedException;
use App\Registry\Exceptions\FunctionNotFoundException;
use App\Registry\Exceptions\FunctionNotInstalledException;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Cache;
use LogicException;

final class FunctionRegistryService
{
    /**
     * Katalogdan fonksiyon getir. Tenant kontrolü YAPMAZ.
     * Global kullanım içindir (admin paneli, dokümantasyon vs.).
     */
    public function get(string $functionId): FunctionSpec
    {
        $cacheKey = "fn:spec:{$functionId}";

        $spec = Cache::remember($cacheKey, 3600, function () use ($functionId) {
            $row = FunctionRegistry::find($functionId);
            return $row ? FunctionSpec::fromModel($row) : null;
        });

        if (!$spec instanceof FunctionSpec) {
            throw new FunctionNotFoundException($functionId);
        }

        return $spec;
    }

    /**
     * Belirli bir tenant'ta fonksiyonun çağrılabilir olup olmadığını doğrular.
     * - Fonksiyon katalogda var mı?
     * - Modülü tenant'ta kurulu mu (status=active)?
     * - Fonksiyon durumu active mi (deprecated/retired değil)?
     *
     * Herhangi biri tutmazsa exception fırlatır.
     */
    public function assertEnabledForTenant(string $functionId, ?string $tenantId = null): FunctionSpec
    {
        $tenantId ??= TenantContext::id();

        if ($tenantId === null) {
            throw new LogicException(
                "assertEnabledForTenant: tenant bağlamı yok ve açıkça tenant_id verilmedi."
            );
        }

        // 1) Katalogda var mı?
        $spec = $this->get($functionId);

        // 2) Fonksiyon durumu uygun mu?
        if ($spec->status === 'retired') {
            throw new FunctionNotFoundException($functionId);
        }
        if ($spec->status === 'deprecated') {
            throw new FunctionDeprecatedException($functionId);
        }

        // 3) Modülü bu tenant'ta kurulu mu?
        if (!$this->isModuleEnabled($spec->moduleId(), $tenantId)) {
            throw new FunctionNotInstalledException(
                $functionId,
                $spec->moduleId(),
                $tenantId
            );
        }

        return $spec;
    }

    /**
     * Belirli bir tenant'ta modülün aktif olup olmadığını söyler.
     * RLS aktif olduğu için sorgu mutlaka TenantContext::runAs içinde yapılmalı.
     * Tenant modül listesi 60 sn cache'lenir; install/uninstall sonrası invalidate edilmeli.
     */
    public function isModuleEnabled(string $moduleId, ?string $tenantId = null): bool
    {
        $tenantId ??= TenantContext::id();
        if ($tenantId === null) {
            return false;
        }

        $cacheKey = "tenant:{$tenantId}:modules";

        $modules = Cache::remember($cacheKey, 60, function () use ($tenantId) {
            return TenantContext::runAs($tenantId, function () use ($tenantId) {
                return TenantModule::where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->pluck('module_id')
                    ->all();
            });
        });

        return in_array($moduleId, $modules, true);
    }

    /**
     * Modül cache'ini temizle (install/uninstall sonrası çağrılmalı).
     */
    public function invalidateTenantModules(string $tenantId): void
    {
        Cache::forget("tenant:{$tenantId}:modules");
    }

    /**
     * Belirli bir domain'in tüm fonksiyonlarını getir.
     */
    public function forDomain(string $domain, string $status = 'active'): array
    {
        return FunctionRegistry::where('domain', $domain)
            ->where('status', $status)
            ->orderBy('id')
            ->get()
            ->map(fn($row) => FunctionSpec::fromModel($row))
            ->all();
    }
}
