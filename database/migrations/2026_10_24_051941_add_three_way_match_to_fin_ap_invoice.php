<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE fin_ap_invoice ADD COLUMN IF NOT EXISTS purchase_order_id uuid NULL');
        DB::statement('ALTER TABLE fin_ap_invoice ADD COLUMN IF NOT EXISTS goods_receipt_id uuid NULL');

        DB::statement('ALTER TABLE fin_ap_invoice DROP CONSTRAINT IF EXISTS fin_ap_invoice_purchase_order_id_foreign');
        DB::statement('ALTER TABLE fin_ap_invoice ADD CONSTRAINT fin_ap_invoice_purchase_order_id_foreign FOREIGN KEY (purchase_order_id) REFERENCES prc_purchase_order(id) ON DELETE SET NULL');

        DB::statement('ALTER TABLE fin_ap_invoice DROP CONSTRAINT IF EXISTS fin_ap_invoice_goods_receipt_id_foreign');
        DB::statement('ALTER TABLE fin_ap_invoice ADD CONSTRAINT fin_ap_invoice_goods_receipt_id_foreign FOREIGN KEY (goods_receipt_id) REFERENCES prc_goods_receipt(id) ON DELETE SET NULL');

        DB::statement('CREATE INDEX IF NOT EXISTS ix_ap_invoice_po ON fin_ap_invoice(purchase_order_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS ix_ap_invoice_gr ON fin_ap_invoice(goods_receipt_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE fin_ap_invoice DROP CONSTRAINT IF EXISTS fin_ap_invoice_purchase_order_id_foreign');
        DB::statement('ALTER TABLE fin_ap_invoice DROP CONSTRAINT IF EXISTS fin_ap_invoice_goods_receipt_id_foreign');
        DB::statement('DROP INDEX IF EXISTS ix_ap_invoice_po');
        DB::statement('DROP INDEX IF EXISTS ix_ap_invoice_gr');
        DB::statement('ALTER TABLE fin_ap_invoice DROP COLUMN IF EXISTS purchase_order_id');
        DB::statement('ALTER TABLE fin_ap_invoice DROP COLUMN IF EXISTS goods_receipt_id');
    }
};
