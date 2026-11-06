<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Depolar
        Schema::create('inv_warehouse', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Ürünler
        Schema::create('inv_product', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('unit', 20)->default('Adet');
            $table->boolean('track_stock')->default(true);
            $table->decimal('sales_price', 18, 4)->default(0);
            $table->decimal('purchase_price', 18, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(20);
            $table->uuid('revenue_account_id'); // 600 Yurtiçi Satışlar
            $table->uuid('inventory_account_id')->nullable(); // 153 Ticari Mallar
            $table->uuid('cogs_account_id')->nullable(); // 621 Satılan Malın Maliyeti
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('revenue_account_id')->references('id')->on('fin_account')->restrictOnDelete();
            $table->foreign('inventory_account_id')->references('id')->on('fin_account')->restrictOnDelete();
            $table->foreign('cogs_account_id')->references('id')->on('fin_account')->restrictOnDelete();
        });

        // Stok Hareketleri
        Schema::create('inv_stock_movement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->uuid('warehouse_id');
            $table->date('movement_date');
            $table->string('type', 10); // in, out
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->string('description', 500)->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'product_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('inv_warehouse')->cascadeOnDelete();
        });

        // Fatura tablosuna depo ve ürün desteği ekle
        Schema::table('fin_ar_invoice', function (Blueprint $table) {
            $table->uuid('warehouse_id')->nullable()->after('customer_id');
            $table->foreign('warehouse_id')->references('id')->on('inv_warehouse')->nullOnDelete();
        });

        Schema::table('fin_ar_invoice_line', function (Blueprint $table) {
            $table->uuid('product_id')->nullable()->after('revenue_account_id');
            $table->foreign('product_id')->references('id')->on('inv_product')->nullOnDelete();
        });

        // RLS Politikaları
        foreach (['inv_warehouse', 'inv_product', 'inv_stock_movement'] as $t) {
            DB::statement("ALTER TABLE {$t} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$t} FORCE ROW LEVEL SECURITY");
            DB::statement(<<<SQL
                CREATE POLICY tenant_isolation ON {$t}
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
    }

    public function down(): void
    {
        Schema::table('fin_ar_invoice_line', fn(Blueprint $t) => $t->dropColumn('product_id'));
        Schema::table('fin_ar_invoice', fn(Blueprint $t) => $t->dropColumn('warehouse_id'));
        Schema::dropIfExists('inv_stock_movement');
        Schema::dropIfExists('inv_product');
        Schema::dropIfExists('inv_warehouse');
    }
};
