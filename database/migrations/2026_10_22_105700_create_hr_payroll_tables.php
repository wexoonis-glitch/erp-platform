<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bordro Dönemi (Payroll Run)
        Schema::create('hr_payroll_run', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('period', 7); // Örn: 2026-10
            $table->date('payment_date');
            $table->decimal('total_gross', 18, 4)->default(0);
            $table->decimal('total_deductions', 18, 4)->default(0);
            $table->decimal('total_net', 18, 4)->default(0);
            $table->string('status', 20)->default('draft'); // draft|posted
            $table->uuid('journal_entry_id')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'period']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Bordro Kalemleri (Personel başına)
        Schema::create('hr_payroll_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('payroll_run_id');
            $table->uuid('employee_id');
            $table->decimal('gross_salary', 18, 4);
            $table->decimal('sgk_employee', 18, 4); // %14
            $table->decimal('sgk_employer', 18, 4); // %20.5 (İşveren payı, maliyet)
            $table->decimal('unemployment_employee', 18, 4); // %1
            $table->decimal('income_tax', 18, 4); // Gelir vergisi (basit %15)
            $table->decimal('stamp_tax', 18, 4); // Damga vergisi (basit %0.759)
            $table->decimal('net_salary', 18, 4);

            $table->index(['tenant_id', 'payroll_run_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('payroll_run_id')->references('id')->on('hr_payroll_run')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('hr_employee')->restrictOnDelete();
        });

        // journal_entry_id FK ayrı adımda
        Schema::table('hr_payroll_run', function (Blueprint $table) {
            $table->foreign('journal_entry_id')->references('id')->on('fin_journal_entry')->nullOnDelete();
        });

        // RLS Politikaları
        foreach (['hr_payroll_run', 'hr_payroll_item'] as $t) {
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
        Schema::dropIfExists('hr_payroll_item');
        Schema::dropIfExists('hr_payroll_run');
    }
};
