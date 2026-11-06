<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_schedule_run', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('task_code', 100);
            $table->string('task_name', 255);
            $table->timestampTz('started_at');
            $table->timestampTz('finished_at')->nullable();
            $table->string('status', 20)->default('running'); // running|success|failed
            $table->text('output')->nullable();
            $table->text('error')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'task_code', 'started_at']);
            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE sys_schedule_run ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE sys_schedule_run FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON sys_schedule_run
                USING (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_schedule_run');
    }
};
