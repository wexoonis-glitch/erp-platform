<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE fin_ar_invoice ADD COLUMN IF NOT EXISTS ettn uuid NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS ix_ar_invoice_ettn ON fin_ar_invoice(ettn)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ix_ar_invoice_ettn');
        DB::statement('ALTER TABLE fin_ar_invoice DROP COLUMN IF EXISTS ettn');
    }
};
