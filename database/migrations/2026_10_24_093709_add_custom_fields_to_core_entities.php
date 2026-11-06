<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE fin_customer ADD COLUMN IF NOT EXISTS custom_fields jsonb DEFAULT '{}'::jsonb");
        DB::statement("ALTER TABLE inv_product ADD COLUMN IF NOT EXISTS custom_fields jsonb DEFAULT '{}'::jsonb");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE fin_customer DROP COLUMN IF EXISTS custom_fields");
        DB::statement("ALTER TABLE inv_product DROP COLUMN IF EXISTS custom_fields");
    }
};
