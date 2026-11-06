<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Depo Transferleri
        Schema::create('inv_stock_transfer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('transfer_no', 30);
            $table->uuid('from_warehouse_id');
            $table->uuid('to_warehouse_id');
            $table->date('transfer_date');
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'transfer_no']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('from_warehouse_id')->references('id')->on('inv_warehouse')->restrictOnDelete();
            $table->foreign('to_warehouse_id')->references('id')->on('inv_warehouse')->restrictOnDelete();
        });

        // Transfer Satırları
        Schema::create('inv_stock_transfer_line', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('transfer_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 18, 4);

            $table->index(['tenant_id', 'transfer_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('transfer_id')->references('id')->on('inv_stock_transfer')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->restrictOnDelete();
        });

        // RLS
        foreach (['inv_stock_transfer', 'inv_stock_transfer_line'] as $t) {
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
        Schema::dropIfExists('inv_stock_transfer_line');
        Schema::dropIfExists('inv_stock_transfer');
    }
};
