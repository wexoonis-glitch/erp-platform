<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Abonelik Paketleri (Planlar)
        Schema::create('saas_plan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->decimal('price', 18, 4)->default(0);
            $table->string('billing_cycle', 20)->default('monthly'); // monthly|yearly
            $table->jsonb('features')->default('{}');
            $table->string('status', 20)->default('active');
            $table->timestampsTz();
        });

        // Tenant Abonelikleri
        Schema::create('saas_subscription', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('plan_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active'); // active|past_due|canceled
            $table->timestampsTz();

            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('saas_plan')->restrictOnDelete();
        });

        // RLS
        DB::statement("ALTER TABLE saas_subscription ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE saas_subscription FORCE ROW LEVEL SECURITY");
        DB::statement(<<<SQL
            CREATE POLICY tenant_isolation ON saas_subscription
                USING (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_subscription');
        Schema::dropIfExists('saas_plan');
    }
};
