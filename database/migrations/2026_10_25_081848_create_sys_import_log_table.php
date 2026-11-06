<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_import_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('entity', 50);
            $table->string('file_name', 255)->nullable();
            $table->integer('total_rows')->default(0);
            $table->integer('created_count')->default(0);
            $table->integer('updated_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->jsonb('errors')->default('[]');
            $table->string('status', 20)->default('completed'); // completed|partial|failed
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'entity', 'created_at']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE sys_import_log ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE sys_import_log FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON sys_import_log
                USING (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_import_log');
    }
};
