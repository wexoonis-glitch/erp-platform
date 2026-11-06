<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ürün kartına vergi rejimi
        DB::statement('ALTER TABLE inv_product ADD COLUMN IF NOT EXISTS tax_regime_id uuid NULL');
        DB::statement('ALTER TABLE inv_product DROP CONSTRAINT IF EXISTS inv_product_tax_regime_id_foreign');
        DB::statement('ALTER TABLE inv_product ADD CONSTRAINT inv_product_tax_regime_id_foreign FOREIGN KEY (tax_regime_id) REFERENCES sys_tax_regime(id) ON DELETE SET NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS ix_inv_product_tax ON inv_product(tax_regime_id)');

        // AR fatura satırına vergi rejimi
        DB::statement('ALTER TABLE fin_ar_invoice_line ADD COLUMN IF NOT EXISTS tax_regime_id uuid NULL');
        DB::statement('ALTER TABLE fin_ar_invoice_line DROP CONSTRAINT IF EXISTS fin_ar_invoice_line_tax_regime_id_foreign');
        DB::statement('ALTER TABLE fin_ar_invoice_line ADD CONSTRAINT fin_ar_invoice_line_tax_regime_id_foreign FOREIGN KEY (tax_regime_id) REFERENCES sys_tax_regime(id) ON DELETE SET NULL');

        // AP fatura satırına vergi rejimi
        DB::statement('ALTER TABLE fin_ap_invoice_line ADD COLUMN IF NOT EXISTS tax_regime_id uuid NULL');
        DB::statement('ALTER TABLE fin_ap_invoice_line DROP CONSTRAINT IF EXISTS fin_ap_invoice_line_tax_regime_id_foreign');
        DB::statement('ALTER TABLE fin_ap_invoice_line ADD CONSTRAINT fin_ap_invoice_line_tax_regime_id_foreign FOREIGN KEY (tax_regime_id) REFERENCES sys_tax_regime(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE fin_ap_invoice_line DROP CONSTRAINT IF EXISTS fin_ap_invoice_line_tax_regime_id_foreign');
        DB::statement('ALTER TABLE fin_ar_invoice_line DROP CONSTRAINT IF EXISTS fin_ar_invoice_line_tax_regime_id_foreign');
        DB::statement('ALTER TABLE inv_product DROP CONSTRAINT IF EXISTS inv_product_tax_regime_id_foreign');
        DB::statement('DROP INDEX IF EXISTS ix_inv_product_tax');
        DB::statement('ALTER TABLE inv_product DROP COLUMN IF EXISTS tax_regime_id');
        DB::statement('ALTER TABLE fin_ar_invoice_line DROP COLUMN IF EXISTS tax_regime_id');
        DB::statement('ALTER TABLE fin_ap_invoice_line DROP COLUMN IF EXISTS tax_regime_id');
    }
};
