<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_sale_expense', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('sale_order_id');
            $table->string('name', 255);
            $table->decimal('amount', 18, 4)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index(['tenant_id', 'sale_order_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE fin_sale_expense ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fin_sale_expense FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON fin_sale_expense
                USING (current_setting('app.is_superadmin', true) = 'true'
                       OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true'
                       OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_sale_expense');
    }
};
