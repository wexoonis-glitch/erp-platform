<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // GLOBAL ülke referans tablosu (RLS yok — herkese açık, sistem verisi)
        Schema::create('sys_country', function (Blueprint $table) {
            $table->string('code', 2)->primary();   // ISO 3166-1 alpha-2: TR, US, DE...
            $table->string('name', 100);
            $table->string('default_currency', 3);
            $table->string('default_locale', 10);
            $table->string('default_timezone', 100);
            $table->string('tax_label', 50)->default('Tax');   // KDV, VAT, Sales Tax, GST...
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('number_format', 20)->default('1,234.56');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        // TENANT başına vergi rejimi (ülkeden bağımsız, generic)
        Schema::create('sys_tax_regime', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);            // TR-KDV-20, US-CA-SALES, EU-DE-VAT-19
            $table->string('name', 100);           // KDV %20, CA Sales Tax
            $table->decimal('rate', 8, 4);         // 20.0000
            $table->string('country', 2);
            $table->string('region', 10)->nullable(); // eyalet/bölge (US için)
            $table->boolean('is_inclusive')->default(false); // fiyat vergi dahil mi
            $table->string('applies_to', 20)->default('both'); // goods|services|both
            $table->boolean('is_default')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'country', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // TENANT hangi country pack'leri aktive etmiş
        Schema::create('sys_country_pack', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('pack_code', 10);       // TR, US, EU
            $table->string('version', 20)->default('1.0.0');
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->default('{}');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'pack_code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // tenant tablosuna ülke/locale/currency ekle (IF NOT EXISTS ile güvenli)
        DB::statement("ALTER TABLE tenants ADD COLUMN IF NOT EXISTS country varchar(2) DEFAULT 'TR'");
        DB::statement("ALTER TABLE tenants ADD COLUMN IF NOT EXISTS locale varchar(10) DEFAULT 'tr'");
        DB::statement("ALTER TABLE tenants ADD COLUMN IF NOT EXISTS currency varchar(3) DEFAULT 'TRY'");
        DB::statement("ALTER TABLE tenants ADD COLUMN IF NOT EXISTS timezone varchar(100) DEFAULT 'Europe/Istanbul'");

        // company_profile'a ülke/locale/tax_label ekle
        DB::statement("ALTER TABLE sys_company_profile ADD COLUMN IF NOT EXISTS country varchar(2) DEFAULT 'TR'");
        DB::statement("ALTER TABLE sys_company_profile ADD COLUMN IF NOT EXISTS locale varchar(10) DEFAULT 'tr'");
        DB::statement("ALTER TABLE sys_company_profile ADD COLUMN IF NOT EXISTS tax_label varchar(50) DEFAULT 'KDV'");
        DB::statement("ALTER TABLE sys_company_profile ADD COLUMN IF NOT EXISTS invoice_label varchar(50) DEFAULT 'Fatura'");

        // sys_tax_regime + sys_country_pack RLS
        foreach (['sys_tax_regime', 'sys_country_pack'] as $t) {
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
        Schema::dropIfExists('sys_country_pack');
        Schema::dropIfExists('sys_tax_regime');
        Schema::dropIfExists('sys_country');
    }
};
