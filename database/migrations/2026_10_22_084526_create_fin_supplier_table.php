<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_supplier', function (Blueprint $table) {
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

        DB::statement('ALTER TABLE fin_supplier ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_supplier FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON fin_supplier
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
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON fin_supplier');
        DB::statement('ALTER TABLE fin_supplier DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('fin_supplier');
    }
};
