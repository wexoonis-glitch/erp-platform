<?php
namespace App\Support;

use App\Services\Platform\FeatureFlagService;

/**
 * Kolay kullanım için static facade.
 *
 *   Features::enabled('otc.sarfiyyat')
 *   Features::value('ux.undo.duration_seconds')
 */
final class Features
{
    public static function enabled(string $key): bool
    {
        try {
            return app(FeatureFlagService::class)->enabled($key);
        } catch (\Throwable $e) {
            \Log::warning("Features::enabled({$key}) hata: {$e->getMessage()}");
            return false;
        }
    }

    public static function value(string $key): mixed
    {
        try {
            return app(FeatureFlagService::class)->value($key);
        } catch (\Throwable $e) {
            \Log::warning("Features::value({$key}) hata: {$e->getMessage()}");
            return null;
        }
    }

    public static function __callStatic(string $method, array $args)
    {
        return app(FeatureFlagService::class)->$method(...$args);
    }
}
