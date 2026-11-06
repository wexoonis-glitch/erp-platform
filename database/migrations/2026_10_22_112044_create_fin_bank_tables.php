<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Banka Hesapları ve Kasalar
        Schema::create('fin_bank_account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name', 255);
            $table->string('type', 20); // bank|cash
            $table->string('account_no', 50)->nullable(); // Hesap No
            $table->string('iban', 34)->nullable();
            $table->string('currency', 3)->default('TRY');
            $table->uuid('gl_account_id'); // Muhasebe hesabı (100 veya 102)
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('gl_account_id')->references('id')->on('fin_account')->restrictOnDelete();
        });

        // Kasa/Banka Hareketleri
        Schema::create('fin_bank_transaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('bank_account_id');
            $table->date('transaction_date');
            $table->string('type', 20); // deposit|withdrawal|transfer_out|transfer_in
            $table->decimal('amount', 18, 4);
            $table->uuid('counter_account_id')->nullable(); // Karşı hesap (Gider, Gelir vb.)
            $table->string('description', 500);
            $table->uuid('journal_entry_id')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'bank_account_id']);
            $table->index(['tenant_id', 'transaction_date']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('fin_bank_account')->cascadeOnDelete();
            $table->foreign('counter_account_id')->references('id')->on('fin_account')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('fin_journal_entry')->nullOnDelete();
        });

        // RLS Politikaları
        foreach (['fin_bank_account', 'fin_bank_transaction'] as $t) {
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
        Schema::dropIfExists('fin_bank_transaction');
        Schema::dropIfExists('fin_bank_account');
    }
};
