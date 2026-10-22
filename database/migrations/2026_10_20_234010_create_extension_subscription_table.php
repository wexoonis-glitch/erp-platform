<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_subscription', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('plugin_id', 50);
            $table->string('event_key', 100);
            $table->string('handler_class', 255);
            $table->string('status', 20)->default('active');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['tenant_id', 'plugin_id', 'event_key']);
            $table->index(['tenant_id', 'event_key', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE extension_subscription ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE extension_subscription FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON extension_subscription
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
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON extension_subscription');
        DB::statement('ALTER TABLE extension_subscription DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('extension_subscription');
    }
};
