<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // RLS'yi aç + sahibi de politikaya tabi tut (FORCE)
        DB::statement('ALTER TABLE tenants ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE tenants FORCE ROW LEVEL SECURITY');

        // Politika:
        //   - app.is_superadmin = 'true' ise tüm satırlar görünür
        //   - app.tenant_id ayarlıysa sadece kendi satırı görünür
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON tenants
                USING (
                    current_setting('app.is_superadmin', true) = 'true'
                    OR id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
                )
                WITH CHECK (
                    current_setting('app.is_superadmin', true) = 'true'
                    OR id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
                )
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON tenants');
        DB::statement('ALTER TABLE tenants DISABLE ROW LEVEL SECURITY');
    }
};
