<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE inv_product ADD COLUMN IF NOT EXISTS replacement_cost numeric(18,4) DEFAULT 0");
        DB::statement("ALTER TABLE inv_product ADD COLUMN IF NOT EXISTS target_margin_pct numeric(8,4) DEFAULT 30");
        DB::statement("ALTER TABLE inv_product ADD COLUMN IF NOT EXISTS min_margin_pct numeric(8,4) DEFAULT 10");
        DB::statement("ALTER TABLE inv_product ADD COLUMN IF NOT EXISTS overhead_per_unit numeric(18,4) DEFAULT 0");

        // Mevcut ürünler için replacement_cost = purchase_price (başlangıç değeri)
        DB::statement("UPDATE inv_product SET replacement_cost = purchase_price WHERE replacement_cost = 0 AND purchase_price > 0");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE inv_product DROP COLUMN IF EXISTS replacement_cost");
        DB::statement("ALTER TABLE inv_product DROP COLUMN IF EXISTS target_margin_pct");
        DB::statement("ALTER TABLE inv_product DROP COLUMN IF EXISTS min_margin_pct");
        DB::statement("ALTER TABLE inv_product DROP COLUMN IF EXISTS overhead_per_unit");
    }
};
