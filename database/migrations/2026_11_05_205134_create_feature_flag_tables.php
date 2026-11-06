<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════
        // 1. GLOBAL VARSAYILANLAR (superadmin yönetir)
        // ═══════════════════════════════════════════════════════
        Schema::create('sys_feature_default', function (Blueprint $table) {
            $table->string('feature_key', 120)->primary();
            $table->boolean('enabled')->default(true);
            $table->jsonb('config')->nullable();
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE sys_feature_default ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE sys_feature_default FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY superadmin_only ON sys_feature_default
                USING (current_setting('app.is_superadmin', true) = 'true')
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true')
        SQL);

        // ═══════════════════════════════════════════════════════
        // 2. TENANT OVERRIDE (tenant admin yönetir)
        // ═══════════════════════════════════════════════════════
        Schema::create('sys_tenant_feature', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('feature_key', 120);
            $table->boolean('enabled')->default(true);
            $table->jsonb('config')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'feature_key']);
            $table->index(['tenant_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE sys_tenant_feature ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE sys_tenant_feature FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON sys_tenant_feature
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

    public function down(): void
    {
        Schema::dropIfExists('sys_tenant_feature');
        Schema::dropIfExists('sys_feature_default');
    }
};
