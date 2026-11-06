<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Müşteri master
        Schema::create('fin_customer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('tax_no', 30)->nullable();
            $table->string('tax_office', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('currency', 3)->default('TRY');
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Müşteri faturası
        Schema::create('fin_ar_invoice', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('customer_id');
            $table->string('invoice_no', 50);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('currency', 3)->default('TRY');
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('vat_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->decimal('paid_total', 18, 4)->default(0);
            $table->string('status', 20)->default('draft'); // draft|posted|paid|partially_paid|cancelled
            $table->uuid('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'customer_id', 'invoice_no']);
            $table->index(['tenant_id', 'status', 'invoice_date']);
            $table->index(['tenant_id', 'customer_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('fin_customer')->restrictOnDelete();
        });

        // Fatura satırları
        Schema::create('fin_ar_invoice_line', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('invoice_id');
            $table->uuid('revenue_account_id');
            $table->string('description', 500);
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('vat_amount', 18, 4)->default(0);
            $table->decimal('total', 18, 4)->default(0);
            $table->integer('line_no')->default(0);

            $table->index(['tenant_id', 'invoice_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('fin_ar_invoice')->cascadeOnDelete();
            $table->foreign('revenue_account_id')->references('id')->on('fin_account')->restrictOnDelete();
        });

        // journal_entry_id FK ayrı adımda
        Schema::table('fin_ar_invoice', function (Blueprint $table) {
            $table->foreign('journal_entry_id')->references('id')->on('fin_journal_entry')->nullOnDelete();
        });

        // RLS
        foreach (['fin_customer', 'fin_ar_invoice', 'fin_ar_invoice_line'] as $t) {
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
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_ar_invoice_line');
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_ar_invoice');
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_customer');
        DB::statement('ALTER TABLE fin_ar_invoice_line DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_ar_invoice DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_customer DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('fin_ar_invoice_line');
        Schema::dropIfExists('fin_ar_invoice');
        Schema::dropIfExists('fin_customer');
    }
};
