<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_company_profile', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->unique();

            $table->string('legal_name', 255);           // Resmi unvan
            $table->string('display_name', 255);         // Kısa ticari ad
            $table->string('tax_no', 30)->nullable();
            $table->string('tax_office', 100)->nullable();

            $table->string('address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->default('Türkiye');
            $table->string('postal_code', 20)->nullable();

            $table->string('phone', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website', 255)->nullable();

            $table->string('logo_path', 500)->nullable();
            $table->string('default_currency', 3)->default('TRY');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1);

            $table->string('invoice_prefix', 20)->default('INV'); // Fatura serisi
            $table->string('purchase_invoice_prefix', 20)->default('AP');
            $table->string('sales_order_prefix', 20)->default('SO');
            $table->string('purchase_order_prefix', 20)->default('PO');

            $table->boolean('einvoice_registered')->default(false);
            $table->string('einvoice_integrator', 50)->nullable(); // gib|uyumsoft|foriba|...
            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE sys_company_profile ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE sys_company_profile FORCE ROW LEVEL SECURITY');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON sys_company_profile
                USING (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_company_profile');
    }
};
