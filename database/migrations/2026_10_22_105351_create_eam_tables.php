<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Varlıklar (Makineler, Araçlar, Demirbaşlar)
        Schema::create('eam_asset', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('category', 100); // Makine, Araç, Bina, BT Ekipmanı
            $table->string('serial_no', 100)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 18, 4)->default(0);
            $table->string('status', 20)->default('active'); // active|maintenance|broken|retired
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Bakım Emirleri (Periyodik veya Arıza)
        Schema::create('eam_maintenance_order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('asset_id');
            $table->string('order_no', 30);
            $table->string('type', 20); // preventive|corrective (periyodik|arıza)
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->string('status', 20)->default('pending'); // pending|in_progress|completed|cancelled
            $table->text('description')->nullable();
            $table->decimal('cost', 18, 4)->default(0);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'order_no']);
            $table->index(['tenant_id', 'status', 'scheduled_date']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('eam_asset')->cascadeOnDelete();
        });

        // RLS Politikaları
        foreach (['eam_asset', 'eam_maintenance_order'] as $t) {
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
        Schema::dropIfExists('eam_maintenance_order');
        Schema::dropIfExists('eam_asset');
    }
};
