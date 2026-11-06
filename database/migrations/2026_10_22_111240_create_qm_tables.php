<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Muayene Planları
        Schema::create('qm_inspection_plan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->restrictOnDelete();
        });

        // Muayene Kriterleri (Plan içindeki maddeler)
        Schema::create('qm_inspection_criteria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('plan_id');
            $table->string('name', 255); // Örn: Uzunluk, Ağırlık, Renk
            $table->string('expected_value', 100); // Örn: 10cm, Kırmızı
            $table->integer('line_no')->default(0);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('qm_inspection_plan')->cascadeOnDelete();
        });

        // Muayene Kayıtları (NCR - Non-Conformance Report / Uygunsuzluk)
        Schema::create('qm_ncr', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->string('ncr_no', 30);
            $table->date('report_date');
            $table->string('source', 50); // incoming|production|customer
            $table->string('reference_no', 100)->nullable(); // Fatura No veya Üretim Emri No
            $table->decimal('quantity', 18, 4); // Hatalı miktar
            $table->string('status', 20)->default('open'); // open|investigating|closed
            $table->text('description')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'ncr_no']);
            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('inv_product')->restrictOnDelete();
        });

        // RLS Politikaları
        foreach (['qm_inspection_plan', 'qm_inspection_criteria', 'qm_ncr'] as $t) {
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
        Schema::dropIfExists('qm_ncr');
        Schema::dropIfExists('qm_inspection_criteria');
        Schema::dropIfExists('qm_inspection_plan');
    }
};
