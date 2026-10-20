<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class TenantContext
{
    private static ?string $tenantId = null;
    private static bool $superadmin = false;

    public static function bind(string $tenantId, bool $superadmin = false): void
    {
        self::$tenantId = $tenantId;
        self::$superadmin = $superadmin;

        DB::selectOne("SELECT set_config('app.tenant_id', ?, false)", [$tenantId]);
        DB::selectOne("SELECT set_config('app.is_superadmin', ?, false)", [$superadmin ? 'true' : 'false']);
    }

    public static function unbind(): void
    {
        self::$tenantId = null;
        self::$superadmin = false;

        DB::selectOne("SELECT set_config('app.tenant_id', '', false)");
        DB::selectOne("SELECT set_config('app.is_superadmin', 'false', false)");
    }

    public static function id(): ?string
    {
        return self::$tenantId;
    }

    public static function isSuperadmin(): bool
    {
        return self::$superadmin;
    }

    /**
     * Belirtilen tenant bağlamında callback çalıştırır, sonra ÖNCEKİ
     * bağlamı geri yükler (unbind değil — restore).
     *
     * İç içe runAs çağrılarında dış context korunur.
     */
    public static function runAs(string $tenantId, callable $fn, bool $superadmin = false): mixed
    {
        $prevTenantId   = self::$tenantId;
        $prevSuperadmin = self::$superadmin;

        self::bind($tenantId, $superadmin);

        try {
            return $fn();
        } finally {
            if ($prevTenantId === null) {
                self::unbind();
            } else {
                self::bind($prevTenantId, $prevSuperadmin);
            }
        }
    }
}
