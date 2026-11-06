<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE fin_ar_invoice ADD COLUMN IF NOT EXISTS sales_order_id uuid NULL');
        DB::statement('ALTER TABLE fin_ar_invoice DROP CONSTRAINT IF EXISTS fin_ar_invoice_sales_order_id_foreign');
        DB::statement('ALTER TABLE fin_ar_invoice ADD CONSTRAINT fin_ar_invoice_sales_order_id_foreign FOREIGN KEY (sales_order_id) REFERENCES otc_sales_order(id) ON DELETE SET NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS ix_ar_invoice_so ON fin_ar_invoice(sales_order_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE fin_ar_invoice DROP CONSTRAINT IF EXISTS fin_ar_invoice_sales_order_id_foreign');
        DB::statement('DROP INDEX IF EXISTS ix_ar_invoice_so');
        DB::statement('ALTER TABLE fin_ar_invoice DROP COLUMN IF EXISTS sales_order_id');
    }
};
