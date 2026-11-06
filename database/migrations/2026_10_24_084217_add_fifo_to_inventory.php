<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ürün: maliyet yöntemi
        DB::statement("ALTER TABLE inv_product ADD COLUMN IF NOT EXISTS cost_method varchar(20) DEFAULT 'standard'");
        // Değerler: standard | fifo | average (average ileride)

        // Stok hareketi: FIFO katmanında kalan miktar
        DB::statement("ALTER TABLE inv_stock_movement ADD COLUMN IF NOT EXISTS remaining_quantity numeric(18,4) NULL");

        // Mevcut 'in' hareketleri için kalan = quantity olarak doldur (geçmiş veri uyumu)
        DB::statement("UPDATE inv_stock_movement SET remaining_quantity = quantity WHERE type = 'in' AND remaining_quantity IS NULL");
        // 'out' hareketleri için 0
        DB::statement("UPDATE inv_stock_movement SET remaining_quantity = 0 WHERE type = 'out' AND remaining_quantity IS NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE inv_product DROP COLUMN IF EXISTS cost_method");
        DB::statement("ALTER TABLE inv_stock_movement DROP COLUMN IF EXISTS remaining_quantity");
    }
};
