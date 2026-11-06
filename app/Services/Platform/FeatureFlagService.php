<?php
namespace App\Services\Platform;

use App\Models\SysFeatureDefault;
use App\Models\SysTenantFeature;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;

final class FeatureFlagService
{
    /** Request-scoped cache */
    private ?array $registry       = null;
    private array  $tenantCache    = [];  // feature_key => SysTenantFeature|null
    private array  $globalCache    = [];  // feature_key => SysFeatureDefault|null
    private array  $resolvedCache  = [];  // feature_key => bool

    public function registry(): array
    {
        return $this->registry ??= config('features', []);
    }

    /** Registry'de tanımlı mı? */
    public function exists(string $key): bool
    {
        return isset($this->registry()[$key]);
    }

    /**
     * Flag aktif mi? (bağımlılık zinciri ile)
     */
    public function enabled(string $key): bool
    {
        if (array_key_exists($key, $this->resolvedCache)) {
            return $this->resolvedCache[$key];
        }

        $def = $this->registry()[$key] ?? null;
        if (!$def) {
            \Log::warning("Feature flag tanımsız: {$key}");
            return $this->resolvedCache[$key] = false;
        }

        // Bağımlılık zinciri — parent'lardan biri kapalıysa false
        foreach (($def['requires'] ?? []) as $parent) {
            if (!$this->enabled($parent)) {
                return $this->resolvedCache[$key] = false;
            }
        }

        // Öncelik: tenant override → global default → registry default
        $override = $this->tenantOverride($key);
        if ($override !== null) {
            return $this->resolvedCache[$key] = $override;
        }

        $global = $this->globalDefault($key);
        if ($global !== null) {
            return $this->resolvedCache[$key] = $global;
        }

        return $this->resolvedCache[$key] = (bool) ($def['default'] ?? false);
    }

    /** Config değeri (int/text/bool) */
    public function value(string $key): mixed
    {
        $def = $this->registry()[$key] ?? null;
        if (!$def) return null;

        $tenantRow = $this->tenantRow($key);
        if ($tenantRow && $tenantRow->config !== null) {
            return $tenantRow->config;
        }

        $globalRow = $this->globalRow($key);
        if ($globalRow && $globalRow->config !== null) {
            return $globalRow->config;
        }

        return $def['default'] ?? null;
    }

    /**
     * Tenant admin bir flag'i kaydeder.
     */
    public function set(string $key, bool $enabled, mixed $config = null): void
    {
        if (!$this->exists($key)) {
            throw new \InvalidArgumentException("Tanımsız flag: {$key}");
        }

        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            throw new \RuntimeException('Tenant context yok.');
        }

        SysTenantFeature::updateOrCreate(
            ['tenant_id' => $tenantId, 'feature_key' => $key],
            [
                'enabled'    => $enabled,
                'config'     => $config,
                'updated_by' => Auth::id(),
            ]
        );

        // Cache invalidate
        unset($this->tenantCache[$key], $this->resolvedCache[$key]);
    }

    /**
     * Tenant için çözümlenmiş tüm flag'ler — admin UI bunu kullanır.
     */
    public function resolvedAll(): array
    {
        $result = [];
        foreach ($this->registry() as $key => $def) {
            $result[$key] = array_merge($def, [
                'key'                => $key,
                'enabled'            => $this->enabled($key),
                'value'              => $this->value($key),
                'disabled_by_parent' => $this->disabledByParent($key),
                'has_override'       => $this->tenantOverride($key) !== null,
            ]);
        }
        return $result;
    }

    // ═══════════════════════════════════════════════════════
    // INTERNALS
    // ═══════════════════════════════════════════════════════

    private function disabledByParent(string $key): bool
    {
        $def = $this->registry()[$key] ?? null;
        if (!$def) return false;
        foreach (($def['requires'] ?? []) as $parent) {
            if (!$this->enabled($parent)) return true;
        }
        return false;
    }

    private function resolveTenantId(): ?string
    {
        try {
            $ctx = TenantContext::id();
            if ($ctx && $ctx !== '00000000-0000-0000-0000-000000000000') {
                return $ctx;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return Auth::user()?->tenant_id;
    }

    private function tenantRow(string $key): ?SysTenantFeature
    {
        if (array_key_exists($key, $this->tenantCache)) {
            return $this->tenantCache[$key];
        }

        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return $this->tenantCache[$key] = null;
        }

        try {
            return $this->tenantCache[$key] = SysTenantFeature::query()
                ->where('tenant_id', $tenantId)
                ->where('feature_key', $key)
                ->first();
        } catch (\Throwable $e) {
            \Log::warning("Feature tenantRow hata: {$e->getMessage()}");
            return $this->tenantCache[$key] = null;
        }
    }

    private function tenantOverride(string $key): ?bool
    {
        $row = $this->tenantRow($key);
        return $row ? (bool) $row->enabled : null;
    }

    private function globalRow(string $key): ?SysFeatureDefault
    {
        if (array_key_exists($key, $this->globalCache)) {
            return $this->globalCache[$key];
        }

        try {
            return $this->globalCache[$key] = SysFeatureDefault::find($key);
        } catch (\Throwable $e) {
            return $this->globalCache[$key] = null;
        }
    }

    private function globalDefault(string $key): ?bool
    {
        $row = $this->globalRow($key);
        return $row ? (bool) $row->enabled : null;
    }
}
