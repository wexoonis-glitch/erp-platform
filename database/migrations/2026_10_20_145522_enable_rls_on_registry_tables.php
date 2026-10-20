<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Her tenant-scoped tablo için aynı desen:
        // - superadmin = 'true' ise tüm satırlar
        // - tenant_id = app.tenant_id ise kendi satırları
        $tables = ['tenant_module', 'custom_field', 'custom_object', 'custom_object_data'];

        foreach ($tables as $t) {
            DB::statement("ALTER TABLE {$t} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$t} FORCE ROW LEVEL SECURITY");

            DB::statement(<<<SQL
                CREATE POLICY tenant_isolation ON {$t}
                    USING (
                        current_setting('app.is_superadmin', true) = 'true'
                        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
                    )
                    WITH CHECK (
                        current_setting('app.is_superadmin', true) = 'true'
                        OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
                    )
            SQL);
        }

        // plugin tablosu: null tenant_id = vendor geneli, herkes görebilir
        DB::statement('ALTER TABLE plugin ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE plugin FORCE ROW LEVEL SECURITY');

        DB::statement(<<<SQL
            CREATE POLICY tenant_isolation ON plugin
                USING (
                    current_setting('app.is_superadmin', true) = 'true'
                    OR tenant_id IS NULL
                    OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
                )
                WITH CHECK (
                    current_setting('app.is_superadmin', true) = 'true'
                    OR tenant_id IS NULL
                    OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid
                )
        SQL);
    }

    public function down(): void
    {
        foreach (['tenant_module', 'custom_field', 'custom_object', 'custom_object_data', 'plugin'] as $t) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$t}");
            DB::statement("ALTER TABLE {$t} DISABLE ROW LEVEL SECURITY");
        }
    }
};
