<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_price_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('currency', 3)->default('TRY');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->integer('priority')->default(100);
            $table->boolean('is_default')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('inv_price_list_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('price_list_id');
            $table->uuid('product_id');
            $table->decimal('unit_price', 18, 4);
            $table->decimal('min_quantity', 18, 4)->default(1);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'price_list_id', 'product_id', 'min_quantity']);
            $table->index(['tenant_id', 'price_list_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('price_list_id')->references('id')->on('inv_price_list')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->cascadeOnDelete();
        });

        Schema::table('fin_customer', function (Blueprint $table) {
            $table->uuid('price_list_id')->nullable();
            $table->foreign('price_list_id')->references('id')->on('inv_price_list')->nullOnDelete();
        });

        foreach (['inv_price_list', 'inv_price_list_item'] as $t) {
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
        Schema::table('fin_customer', fn(Blueprint $t) => $t->dropColumn('price_list_id'));
        Schema::dropIfExists('inv_price_list_item');
        Schema::dropIfExists('inv_price_list');
    }
};
