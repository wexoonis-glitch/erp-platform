<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_bank_statement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('bank_account_id');
            $table->string('file_name', 255);
            $table->string('format', 20)->default('csv');
            $table->string('file_path', 500)->nullable();
            $table->date('statement_from')->nullable();
            $table->date('statement_to')->nullable();
            $table->integer('total_lines')->default(0);
            $table->integer('matched_lines')->default(0);
            $table->string('status', 20)->default('imported');
            $table->timestampsTz();

            $table->index(['tenant_id', 'bank_account_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('fin_bank_account')->cascadeOnDelete();
        });

        Schema::create('sys_bank_statement_line', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('statement_id');
            $table->integer('line_no');
            $table->date('value_date');
            $table->decimal('amount', 18, 4);
            $table->string('description', 500)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('unmatched'); // unmatched|matched|ignored
            $table->uuid('matched_transaction_id')->nullable();
            $table->timestampTz('matched_at')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'statement_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('statement_id')->references('id')->on('sys_bank_statement')->cascadeOnDelete();
            $table->foreign('matched_transaction_id')->references('id')->on('fin_bank_transaction')->nullOnDelete();
        });

        foreach (['sys_bank_statement', 'sys_bank_statement_line'] as $t) {
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
        Schema::dropIfExists('sys_bank_statement_line');
        Schema::dropIfExists('sys_bank_statement');
    }
};
