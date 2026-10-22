<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('type', 20);
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_postable')->default(true);
            $table->string('currency', 3)->default('TRY');
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Self-referencing FK ayrı adımda eklenir (PostgreSQL PK sırası için)
        Schema::table('fin_account', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('fin_account')
                ->nullOnDelete();
        });

        DB::statement('ALTER TABLE fin_account ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_account FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON fin_account
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
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_account');
        DB::statement('ALTER TABLE fin_account DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('fin_account');
    }
};
