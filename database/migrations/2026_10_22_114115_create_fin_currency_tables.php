<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Para Birimleri
        Schema::create('fin_currency', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 3); // TRY, USD, EUR
            $table->string('name', 100);
            $table->string('symbol', 10)->nullable();
            $table->boolean('is_base')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Döviz Kurları
        Schema::create('fin_exchange_rate', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('currency_id');
            $table->date('rate_date');
            $table->decimal('rate', 18, 6); // 1 USD = 32.500000 TRY
            $table->timestampsTz();

            $table->unique(['tenant_id', 'currency_id', 'rate_date']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('currency_id')->references('id')->on('fin_currency')->cascadeOnDelete();
        });

        // RLS Politikaları
        foreach (['fin_currency', 'fin_exchange_rate'] as $t) {
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
        Schema::dropIfExists('fin_exchange_rate');
        Schema::dropIfExists('fin_currency');
    }
};
