<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_cost_center', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('manager_name', 255)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::table('hr_employee', function (Blueprint $table) {
            $table->uuid('cost_center_id')->nullable();
            $table->foreign('cost_center_id')->references('id')->on('fin_cost_center')->nullOnDelete();
        });

        Schema::table('ppm_project', function (Blueprint $table) {
            $table->uuid('cost_center_id')->nullable();
            $table->foreign('cost_center_id')->references('id')->on('fin_cost_center')->nullOnDelete();
        });

        DB::statement('ALTER TABLE fin_cost_center ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_cost_center FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON fin_cost_center
                USING (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::table('ppm_project', fn(Blueprint $t) => $t->dropColumn('cost_center_id'));
        Schema::table('hr_employee', fn(Blueprint $t) => $t->dropColumn('cost_center_id'));
        Schema::dropIfExists('fin_cost_center');
    }
};
