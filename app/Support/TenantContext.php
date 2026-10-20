<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class TenantContext
{
    private static ?string $tenantId = null;
    private static bool $superadmin = false;

    /**
     * PostgreSQL oturum değişkenlerini set eder.
     * RLS politikaları bu değişkenlere göre filtre uygular.
     */
    public static function bind(string $tenantId, bool $superadmin = false): void
    {
        self::$tenantId = $tenantId;
        self::$superadmin = $superadmin;

        // set_config(name, value, is_local) — is_local=false => oturum boyunca geçerli
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
     * Belirtilen tenant bağlamında callback çalıştırır, sonra temizler.
     * Test ve CLI için kullanışlı.
     */
    public static function runAs(string $tenantId, callable $fn, bool $superadmin = false): mixed
    {
        self::bind($tenantId, $superadmin);
        try {
            return $fn();
        } finally {
            self::unbind();
        }
    }
}
