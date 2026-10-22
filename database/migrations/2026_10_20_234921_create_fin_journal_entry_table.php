<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_journal_entry', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('entry_no', 30);
            $table->date('entry_date');
            $table->string('description', 500);
            $table->string('status', 20)->default('draft');
            $table->timestampTz('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->string('source_type', 100)->nullable();
            $table->string('source_id', 100)->nullable();
            $table->uuid('reverses_entry_id')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'entry_no']);
            $table->index(['tenant_id', 'entry_date', 'status']);
            $table->index(['tenant_id', 'source_type', 'source_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Self-referencing FK ayrı adımda
        Schema::table('fin_journal_entry', function (Blueprint $table) {
            $table->foreign('reverses_entry_id')
                ->references('id')
                ->on('fin_journal_entry')
                ->nullOnDelete();
        });

        DB::statement('ALTER TABLE fin_journal_entry ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_journal_entry FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON fin_journal_entry
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
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_journal_entry');
        DB::statement('ALTER TABLE fin_journal_entry DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('fin_journal_entry');
    }
};
