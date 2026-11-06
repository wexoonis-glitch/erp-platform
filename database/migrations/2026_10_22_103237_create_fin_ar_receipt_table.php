<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_ar_receipt', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('customer_id');
            $table->uuid('bank_account_id');
            $table->string('receipt_no', 30);
            $table->date('receipt_date');
            $table->decimal('amount', 18, 4);
            $table->string('currency', 3)->default('TRY');
            $table->string('method', 20)->default('bank'); // cash|bank|check|other
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft'); // draft|posted|cancelled
            $table->uuid('journal_entry_id')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'receipt_no']);
            $table->index(['tenant_id', 'status', 'receipt_date']);
            $table->index(['tenant_id', 'customer_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('fin_customer')->restrictOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('fin_account')->restrictOnDelete();
        });

        Schema::create('fin_ar_receipt_allocation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('receipt_id');
            $table->uuid('invoice_id');
            $table->decimal('amount', 18, 4);

            $table->index(['tenant_id', 'receipt_id']);
            $table->index(['tenant_id', 'invoice_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('receipt_id')->references('id')->on('fin_ar_receipt')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('fin_ar_invoice')->restrictOnDelete();
        });

        // journal_entry_id FK ayrı adımda
        Schema::table('fin_ar_receipt', function (Blueprint $table) {
            $table->foreign('journal_entry_id')->references('id')->on('fin_journal_entry')->nullOnDelete();
        });

        // RLS
        foreach (['fin_ar_receipt', 'fin_ar_receipt_allocation'] as $t) {
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
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_ar_receipt_allocation');
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_ar_receipt');
        DB::statement('ALTER TABLE fin_ar_receipt_allocation DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_ar_receipt DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('fin_ar_receipt_allocation');
        Schema::dropIfExists('fin_ar_receipt');
    }
};
