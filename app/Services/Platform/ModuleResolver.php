<?php
namespace App\Services\Platform;

use App\Models\SysModule;
use App\Models\SysModulePolicy;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Cache;

/**
 * Module Resolver — Policy-Based, N-Katmanlı, Koşullu
 *
 * Algoritma:
 * 1. Tüm aktif policy'leri priority DESC sırala
 * 2. Her policy için matches() kontrolü (scope + conditions)
 * 3. Eşleşen policy uygulanır (mode: override|merge|patch)
 * 4. Sonuç: aktif modül listesi
 *
 * Hiçbir sabit katman yok. Katman = policy satırı.
 * Yarın "group" scope eklenirse → yeni policy satırı, resolver değişmez.
 */
final class ModuleResolver
{
    /** @var array<string, bool> */
    private array $activeModuleKeys = [];

    public function __construct()
    {
        $this->activeModuleKeys = SysModule::query()
            ->where('status', 'active')
            ->pluck('key')
            ->flip()
            ->map(fn() => true)
            ->all();
    }

    /**
     * Aktör için geçerli modül listesi.
     *
     * @param  int|null  $userId   Null = sadece tenant context
     * @return string[]
     */
    public function resolve(?int $userId = null): array
    {
        $tenantId = TenantContext::id();
        $cacheKey = 'module_resolver:' . ($tenantId ?? 'super') . ':' . ($userId ?? 'anon');

        return Cache::remember($cacheKey, 60, function () use ($tenantId, $userId) {
            return $this->resolveUncached($tenantId, $userId);
        });
    }

    private function resolveUncached(?string $tenantId, ?int $userId): array
    {
        $policies = SysModulePolicy::query()
            ->where('is_active', true)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id');
                if ($tenantId) $q->orWhere('tenant_id', $tenantId);
            })
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->get();

        $modules = [];
        $applied = false;

        foreach ($policies as $policy) {
            if (!$this->matches($policy, $tenantId, $userId)) continue;

            $modules = $this->applyMode($modules, $policy);
            $applied = true;
        }

        // Hiç policy eşleşmediyse → boş (kullanıcı modül görmez)
        // Ama default policy her zaman '*' ile ilk katmanda → en azından o çalışır

        // Wildcard çöz
        if (in_array('*', $modules, true)) {
            $modules = array_keys($this->activeModuleKeys);
        }

        // Registry'de aktif olmayanları çıkar
        $modules = array_values(array_filter(
            $modules,
            fn($m) => $m !== '*' && isset($this->activeModuleKeys[$m])
        ));

        return array_values(array_unique($modules));
    }

    /**
     * Policy bu actor için geçerli mi?
     */
    private function matches(SysModulePolicy $policy, ?string $tenantId, ?int $userId): bool
    {
        // Scope kontrolü
        $scopeOk = match ($policy->scope_type) {
            'platform'  => true,
            'tenant'    => $tenantId !== null,
            'structure' => true,   // Belge 05 kurulunca structure_id kontrolü eklenir
            'user'      => $userId !== null && $policy->scope_id === (string) $userId,
            'role'      => $this->userHasRole($userId, $policy->scope_id),
            'group'     => false,  // Grup sistemi henüz yok → eşleşmez
            'runtime'   => true,
            default     => false,  // custom-* → ileride
        };

        if (!$scopeOk) return false;

        // Conditions kontrolü
        return $this->conditionsMet($policy->conditions);
    }

    /**
     * Mode'a göre modül listesine uygula.
     */
    private function applyMode(array $current, SysModulePolicy $policy): array
    {
        $new = is_array($policy->modules) ? $policy->modules : [];

        return match ($policy->mode) {
            SysModulePolicy::MODE_OVERRIDE => $new,
            SysModulePolicy::MODE_MERGE    => array_values(array_unique(array_merge($current, $new))),
            SysModulePolicy::MODE_PATCH    => $this->applyPatch($current, $new),
            default                        => $current,
        };
    }

    /**
     * Patch modu: "!fin" = çıkar, "+pulse" = ekle, "*" = hepsi.
     */
    private function applyPatch(array $current, array $patch): array
    {
        $result = $current;

        foreach ($patch as $item) {
            if ($item === '*') {
                $result = array_keys($this->activeModuleKeys);
            } elseif (str_starts_with($item, '!')) {
                $result = array_values(array_diff($result, [substr($item, 1)]));
            } elseif (str_starts_with($item, '+')) {
                $result[] = substr($item, 1);
            } else {
                $result[] = $item;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Conditions met?
     *
     * Örnek: {"plan": "pro", "region": "tr"} → tenant plan pro ve region tr ise geçer.
     * Desteklenen anahtarlar runtime'da genişletilebilir.
     */
    private function conditionsMet(?array $conditions): bool
    {
        if (empty($conditions)) return true;

        foreach ($conditions as $key => $expected) {
            $actual = $this->resolveConditionValue($key);

            if (is_array($expected)) {
                // expected array ise → in_array
                if (!in_array($actual, $expected, true)) return false;
            } else {
                if ($actual !== $expected) return false;
            }
        }

        return true;
    }

    /**
     * Condition değerini runtime'da çöz.
     *
     * Yeni condition eklemek = bu switch'e bir case ekle.
     */
    private function resolveConditionValue(string $key): mixed
    {
        $tenantId = TenantContext::id();

        return match ($key) {
            'plan'   => $this->getTenantPlan($tenantId),
            'region' => $this->getTenantRegion($tenantId),
            'status' => 'active',
            default  => null,
        };
    }

    private function getTenantPlan(?string $tenantId): ?string
    {
        if (!$tenantId) return null;
        try {
            return \App\Models\Tenant::find($tenantId)?->plan ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getTenantRegion(?string $tenantId): ?string
    {
        if (!$tenantId) return null;
        try {
            return \App\Models\Tenant::find($tenantId)?->region ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function userHasRole(?int $userId, ?string $roleKey): bool
    {
        if (!$userId || !$roleKey) return false;
        try {
            $user = \App\Models\User::find($userId);
            return $user && $user->role === $roleKey;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Cache temizle — policy değiştiğinde çağır.
     */
    public static function flush(?string $tenantId = null): void
    {
        if ($tenantId) {
            Cache::forget("module_resolver:{$tenantId}:anon");
            // tüm user cache'leri de temizlenmeli — basit: prefix ile
            return;
        }
        // Global flush — tüm tenant için
        Cache::flush();
    }
}
