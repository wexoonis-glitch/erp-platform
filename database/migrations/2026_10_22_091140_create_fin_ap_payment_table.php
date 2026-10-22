<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_ap_payment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('supplier_id');
            $table->uuid('bank_account_id');
            $table->string('payment_no', 30);
            $table->date('payment_date');
            $table->decimal('amount', 18, 4);
            $table->string('currency', 3)->default('TRY');
            $table->string('method', 20)->default('bank'); // cash|bank|check|other
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft'); // draft|posted|cancelled
            $table->uuid('journal_entry_id')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'payment_no']);
            $table->index(['tenant_id', 'status', 'payment_date']);
            $table->index(['tenant_id', 'supplier_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('fin_supplier')->restrictOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('fin_account')->restrictOnDelete();
        });

        Schema::create('fin_ap_payment_allocation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('payment_id');
            $table->uuid('invoice_id');
            $table->decimal('amount', 18, 4);

            $table->index(['tenant_id', 'payment_id']);
            $table->index(['tenant_id', 'invoice_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('payment_id')->references('id')->on('fin_ap_payment')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('fin_ap_invoice')->restrictOnDelete();
        });

        // journal_entry_id FK ayrı adımda
        Schema::table('fin_ap_payment', function (Blueprint $table) {
            $table->foreign('journal_entry_id')->references('id')->on('fin_journal_entry')->nullOnDelete();
        });

        // RLS
        foreach (['fin_ap_payment', 'fin_ap_payment_allocation'] as $t) {
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
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_ap_payment_allocation');
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_ap_payment');
        DB::statement('ALTER TABLE fin_ap_payment_allocation DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_ap_payment DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('fin_ap_payment_allocation');
        Schema::dropIfExists('fin_ap_payment');
    }
};
