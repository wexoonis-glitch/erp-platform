<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email', 255)->nullable();
            $table->string('function_id', 30)->nullable();
            $table->string('entity', 100);
            $table->string('entity_id', 100)->nullable();
            $table->string('action', 50);
            $table->jsonb('before_state')->nullable();
            $table->jsonb('after_state')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['entity', 'entity_id']);
            $table->index(['user_id']);
            $table->index(['action', 'created_at']);
        });

        DB::statement('ALTER TABLE audit_log ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE audit_log FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON audit_log
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
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON audit_log');
        DB::statement('ALTER TABLE audit_log DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('audit_log');
    }
};
