<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_budget', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('cost_center_id');
            $table->string('year', 4);
            $table->string('month', 2); // 01..12
            $table->decimal('planned_amount', 18, 4)->default(0);
            $table->string('notes', 500)->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'cost_center_id', 'year', 'month']);
            $table->index(['tenant_id', 'year']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cost_center_id')->references('id')->on('fin_cost_center')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE fin_budget ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_budget FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON fin_budget
                USING (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_budget');
    }
};
