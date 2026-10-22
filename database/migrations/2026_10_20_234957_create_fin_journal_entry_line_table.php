<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_journal_entry_line', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('journal_entry_id');
            $table->uuid('account_id');
            $table->decimal('debit', 18, 4)->default(0);
            $table->decimal('credit', 18, 4)->default(0);
            $table->string('description', 500)->nullable();
            $table->integer('line_no')->default(0);

            $table->index(['tenant_id', 'journal_entry_id']);
            $table->index(['tenant_id', 'account_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('fin_journal_entry')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('fin_account')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE fin_journal_entry_line ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_journal_entry_line FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON fin_journal_entry_line
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

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_journal_entry_line');
        DB::statement('ALTER TABLE fin_journal_entry_line DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('fin_journal_entry_line');
    }
};
