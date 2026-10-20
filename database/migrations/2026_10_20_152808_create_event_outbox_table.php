<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_outbox', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('tenant_id');
            $table->string('event_key', 100);
            $table->jsonb('payload')->default('{}');
            $table->string('status', 20)->default('pending');
            $table->integer('attempts')->default(0);
            $table->timestampTz('available_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('processed_at')->nullable();
            $table->text('last_error')->nullable();

            $table->index(['status', 'available_at']);
            $table->index(['event_key']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE event_outbox ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE event_outbox FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON event_outbox
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
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON event_outbox');
        DB::statement('ALTER TABLE event_outbox DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('event_outbox');
    }
};
