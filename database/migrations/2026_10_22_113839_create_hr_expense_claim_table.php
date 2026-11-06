<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_expense_claim', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('employee_id');
            $table->date('expense_date');
            $table->string('category', 50); // travel|meal|accommodation|other
            $table->decimal('amount', 18, 4);
            $table->text('description');
            $table->string('status', 20)->default('draft'); // draft|submitted|approved|paid|rejected
            
            // Ödeme yapıldığında doldurulacak alanlar
            $table->uuid('payment_account_id')->nullable(); 
            $table->uuid('journal_entry_id')->nullable();
            
            $table->timestampsTz();

            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('hr_employee')->restrictOnDelete();
            $table->foreign('payment_account_id')->references('id')->on('fin_bank_account')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('fin_journal_entry')->nullOnDelete();
        });

        DB::statement('ALTER TABLE hr_expense_claim ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE hr_expense_claim FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON hr_expense_claim
                USING (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_expense_claim');
    }
};
