<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reçeteler (BOM)
        Schema::create('mfg_bom', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id'); // Üretilecek Mamul
            $table->string('code', 50);
            $table->string('name', 255);
            $table->decimal('quantity', 18, 4)->default(1); // 1 birim mamul için
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->restrictOnDelete();
        });

        // Reçete Satırları (Hammaddeler)
        Schema::create('mfg_bom_line', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('bom_id');
            $table->uuid('component_id'); // Hammadde
            $table->decimal('quantity', 18, 4); // Ne kadar gerekiyor
            $table->integer('line_no')->default(0);

            $table->index(['tenant_id', 'bom_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('bom_id')->references('id')->on('mfg_bom')->cascadeOnDelete();
            $table->foreign('component_id')->references('id')->on('inv_product')->restrictOnDelete();
        });

        // Üretim Emirleri
        Schema::create('mfg_production_order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id'); // Üretilecek Mamul
            $table->uuid('bom_id');
            $table->string('order_no', 30);
            $table->date('order_date');
            $table->decimal('quantity', 18, 4);
            $table->string('status', 20)->default('draft'); // draft|in_progress|completed|cancelled
            $table->decimal('total_cost', 18, 4)->default(0);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'order_no']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->restrictOnDelete();
            $table->foreign('bom_id')->references('id')->on('mfg_bom')->restrictOnDelete();
        });

        // RLS Politikaları
        foreach (['mfg_bom', 'mfg_bom_line', 'mfg_production_order'] as $t) {
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
        Schema::dropIfExists('mfg_production_order');
        Schema::dropIfExists('mfg_bom_line');
        Schema::dropIfExists('mfg_bom');
    }
};
