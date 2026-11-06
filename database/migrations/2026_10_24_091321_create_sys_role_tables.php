<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_role', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);        // custom key: 'accountant', 'warehouse_mgr', 'sales_rep'...
            $table->string('name', 255);       // görünen ad
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // silinemez varsayılan roller
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('sys_role_module', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('role_id');
            $table->string('module_id', 20);   // PLT, FIN, INV, PRC, OTC, MFG, HRM, CRM, PPM, EAM, QM, LOG
            $table->timestampsTz();

            $table->unique(['tenant_id', 'role_id', 'module_id']);
            $table->index(['tenant_id', 'role_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('sys_role')->cascadeOnDelete();
        });

        // users.role artık serbest string (custom rol kodları için)
        DB::statement('ALTER TABLE users ALTER COLUMN role TYPE varchar(50)');

        foreach (['sys_role', 'sys_role_module'] as $t) {
            DB::statement("ALTER TABLE {$t} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$t} FORCE ROW LEVEL SECURITY");
            DB::statement(<<<SQL
                CREATE POLICY tenant_isolation ON {$t}
                    USING (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                    WITH CHECK (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_role_module');
        Schema::dropIfExists('sys_role');
    }
};
