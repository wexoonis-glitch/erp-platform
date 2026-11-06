<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Projeler
        Schema::create('ppm_project', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('customer_id')->nullable(); // Hangi müşteri için
            $table->string('code', 50);
            $table->string('name', 255);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('budget', 18, 4)->default(0);
            $table->string('status', 20)->default('active'); // planning|active|completed|cancelled
            $table->text('description')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('fin_customer')->nullOnDelete();
        });

        // Proje Görevleri
        Schema::create('ppm_task', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('project_id');
            $table->string('name', 255);
            $table->string('status', 20)->default('todo'); // todo|in_progress|done
            $table->date('due_date')->nullable();
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'project_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('ppm_project')->cascadeOnDelete();
        });

        // Proje Giderleri
        Schema::create('ppm_expense', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('project_id');
            $table->date('expense_date');
            $table->string('description', 500);
            $table->decimal('amount', 18, 4);
            $table->timestampsTz();

            $table->index(['tenant_id', 'project_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('ppm_project')->cascadeOnDelete();
        });

        // RLS Politikaları
        foreach (['ppm_project', 'ppm_task', 'ppm_expense'] as $t) {
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
        Schema::dropIfExists('ppm_expense');
        Schema::dropIfExists('ppm_task');
        Schema::dropIfExists('ppm_project');
    }
};
