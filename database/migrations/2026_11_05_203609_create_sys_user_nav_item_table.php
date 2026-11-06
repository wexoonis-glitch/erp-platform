<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_user_nav_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('route_name', 120);
            $table->string('kind', 20); // favorite | recent
            $table->integer('sort_order')->default(0);
            $table->integer('use_count')->default(1);
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'user_id', 'kind', 'route_name']);
            $table->index(['user_id', 'kind', 'sort_order']);
            $table->index(['user_id', 'kind', 'last_used_at']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE sys_user_nav_item ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE sys_user_nav_item FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON sys_user_nav_item
                USING (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_user_nav_item');
    }
};
