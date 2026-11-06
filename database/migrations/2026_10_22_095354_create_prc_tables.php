<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satın Alma Siparişi (PO)
        Schema::create('prc_purchase_order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('supplier_id');
            $table->string('po_no', 30);
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('vat_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->string('status', 20)->default('draft'); // draft|approved|received|cancelled
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'po_no']);
            $table->index(['tenant_id', 'status', 'order_date']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('fin_supplier')->restrictOnDelete();
        });

        // Satın Alma Sipariş Satırları
        Schema::create('prc_purchase_order_line', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('po_id');
            $table->uuid('product_id');
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('vat_amount', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);

            $table->index(['tenant_id', 'po_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('po_id')->references('id')->on('prc_purchase_order')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->restrictOnDelete();
        });

        // Mal Kabul (Goods Receipt - GR)
        Schema::create('prc_goods_receipt', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('po_id');
            $table->string('gr_no', 30);
            $table->date('receipt_date');
            $table->string('status', 20)->default('draft'); // draft|posted
            $table->timestampsTz();

            $table->unique(['tenant_id', 'gr_no']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('po_id')->references('id')->on('prc_purchase_order')->cascadeOnDelete();
        });

        // Mal Kabul Satırları
        Schema::create('prc_goods_receipt_line', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('gr_id');
            $table->uuid('po_line_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4);

            $table->index(['tenant_id', 'gr_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('gr_id')->references('id')->on('prc_goods_receipt')->cascadeOnDelete();
            $table->foreign('po_line_id')->references('id')->on('prc_purchase_order_line')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->restrictOnDelete();
        });

        // RLS Politikaları
        foreach (['prc_purchase_order', 'prc_purchase_order_line', 'prc_goods_receipt', 'prc_goods_receipt_line'] as $t) {
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
        Schema::dropIfExists('prc_goods_receipt_line');
        Schema::dropIfExists('prc_goods_receipt');
        Schema::dropIfExists('prc_purchase_order_line');
        Schema::dropIfExists('prc_purchase_order');
    }
};
