<?php

namespace App\Services\Platform;

use App\Models\FunctionRegistry;
use App\Models\TenantModule;
use App\Registry\FunctionRegistryService;
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;
use LogicException;

final class ModuleService
{
    public function __construct(private readonly FunctionRegistryService $registry) {}

    /**
     * Katalogdaki tüm modülleri listele (function_registry'den distinct domain).
     */
    public function catalog(): array
    {
        return FunctionRegistry::select('domain')
            ->selectRaw('COUNT(*) as function_count')
            ->where('status', 'active')
            ->groupBy('domain')
            ->orderBy('domain')
            ->get()
            ->map(fn($r) => [
                'module_id'      => $r->domain,
                'function_count' => (int) $r->function_count,
            ])
            ->all();
    }

    /**
     * Bir tenant'ın kurduğu modülleri listele.
     */
    public function installed(string $tenantId): array
    {
        return TenantContext::runAs(
            $tenantId,
            fn() => TenantModule::where('tenant_id', $tenantId)
                ->orderBy('module_id')
                ->get(['module_id', 'version', 'status', 'installed_at'])
                ->toArray()
        );
    }

    /**
     * Bir modülün fonksiyonlarını listele (katalogdan).
     */
    public function moduleFunctions(string $moduleId, string $status = 'active'): array
    {
        return FunctionRegistry::where('domain', $moduleId)
            ->where('status', $status)
            ->orderBy('id')
            ->get(['id', 'name', 'type', 'subprocess', 'version', 'status'])
            ->toArray();
    }

    /**
     * Modül kur.
     */
    public function install(string $tenantId, string $moduleId): TenantModule
    {
        // Zaten kurulu mu?
        $exists = TenantContext::runAs(
            $tenantId,
            fn() => TenantModule::where('tenant_id', $tenantId)
                ->where('module_id', $moduleId)
                ->exists()
        );

        if ($exists) {
            throw ValidationException::withMessages([
                'module_id' => "Modül zaten kurulu: {$moduleId}",
            ]);
        }

        // Katalogda var mı?
        $count = FunctionRegistry::where('domain', $moduleId)
            ->where('status', 'active')
            ->count();

        if ($count === 0) {
            throw ValidationException::withMessages([
                'module_id' => "Modül katalogda bulunamadı: {$moduleId}",
            ]);
        }

        $tm = TenantContext::runAs(
            $tenantId,
            fn() => TenantModule::create([
                'tenant_id'    => $tenantId,
                'module_id'    => $moduleId,
                'version'      => '1.0.0',
                'status'       => 'active',
                'config'       => [],
                'installed_at' => now(),
            ])
        );

        $this->registry->invalidateTenantModules($tenantId);

        return $tm;
    }

    /**
     * Modül kaldır.
     */
    public function uninstall(string $tenantId, string $moduleId): void
    {
        if ($moduleId === 'PLT') {
            throw new LogicException('PLT (Platform) modülü kaldırılamaz.');
        }

        TenantContext::runAs(
            $tenantId,
            fn() => TenantModule::where('tenant_id', $tenantId)
                ->where('module_id', $moduleId)
                ->delete()
        );

        $this->registry->invalidateTenantModules($tenantId);
    }
}
