<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Taşıyıcılar / Kargo Firmaları
        Schema::create('log_carrier', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('tracking_url', 500)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Sevkiyatlar
        Schema::create('log_shipment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('sales_order_id');
            $table->uuid('carrier_id');
            $table->string('shipment_no', 30);
            $table->date('shipment_date');
            $table->string('tracking_no', 100)->nullable();
            $table->string('status', 20)->default('shipped'); // preparing|shipped|delivered
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'shipment_no']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('sales_order_id')->references('id')->on('otc_sales_order')->restrictOnDelete();
            $table->foreign('carrier_id')->references('id')->on('log_carrier')->restrictOnDelete();
        });

        // Sevkiyat Satırları
        Schema::create('log_shipment_line', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('shipment_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 18, 4);

            $table->index(['tenant_id', 'shipment_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('shipment_id')->references('id')->on('log_shipment')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->restrictOnDelete();
        });

        // RLS Politikaları
        foreach (['log_carrier', 'log_shipment', 'log_shipment_line'] as $t) {
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
        Schema::dropIfExists('log_shipment_line');
        Schema::dropIfExists('log_shipment');
        Schema::dropIfExists('log_carrier');
    }
};
