<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Potansiyel Müşteriler (Leads)
        Schema::create('crm_lead', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name', 255);
            $table->string('company', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('source', 50)->nullable(); // website, referral, cold_call, etc.
            $table->string('status', 20)->default('new'); // new|contacted|qualified|lost
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Satış Fırsatları (Opportunities)
        Schema::create('crm_opportunity', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('customer_id')->nullable(); // Eğer kazanılırsa müşteriye bağlanır
            $table->string('name', 255);
            $table->decimal('amount', 18, 4)->default(0);
            $table->date('expected_close_date')->nullable();
            $table->string('stage', 50)->default('qualification'); // qualification|proposal|negotiation|closed_won|closed_lost
            $table->integer('probability')->default(10); // 0-100%
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'stage']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('fin_customer')->nullOnDelete();
        });

        // RLS Politikaları
        foreach (['crm_lead', 'crm_opportunity'] as $t) {
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
        Schema::dropIfExists('crm_opportunity');
        Schema::dropIfExists('crm_lead');
    }
};
