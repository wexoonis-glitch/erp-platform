<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satış Siparişi
        Schema::create('otc_sales_order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('customer_id');
            $table->string('so_no', 30);
            $table->date('order_date');
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('vat_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->string('status', 20)->default('draft'); // draft|approved|invoiced|cancelled
            $table->uuid('ar_invoice_id')->nullable(); // Faturaya dönüştüyse ID'si tutulur
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'so_no']);
            $table->index(['tenant_id', 'status', 'order_date']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('fin_customer')->restrictOnDelete();
        });

        // Satış Sipariş Satırları
        Schema::create('otc_sales_order_line', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('so_id');
            $table->uuid('product_id');
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('vat_amount', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);

            $table->index(['tenant_id', 'so_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('so_id')->references('id')->on('otc_sales_order')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->restrictOnDelete();
        });

        // ar_invoice_id FK ayrı adımda
        Schema::table('otc_sales_order', function (Blueprint $table) {
            $table->foreign('ar_invoice_id')->references('id')->on('fin_ar_invoice')->nullOnDelete();
        });

        // RLS Politikaları
        foreach (['otc_sales_order', 'otc_sales_order_line'] as $t) {
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
        Schema::dropIfExists('otc_sales_order_line');
        Schema::dropIfExists('otc_sales_order');
    }
};
