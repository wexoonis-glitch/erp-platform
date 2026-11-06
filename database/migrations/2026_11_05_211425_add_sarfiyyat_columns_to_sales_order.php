<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function detectTable(): ?string
    {
        // Bilinen isimleri dene
        foreach (['otc_sales_order', 'otc_sales_orders', 'sales_orders', 'otc_so'] as $candidate) {
            if (Schema::hasTable($candidate)) return $candidate;
        }
        // Model'den table adı al
        try {
            if (class_exists(\App\Models\OtcSalesOrder::class)) {
                $t = (new \App\Models\OtcSalesOrder)->getTable();
                if (Schema::hasTable($t)) return $t;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
    }

    public function up(): void
    {
        $table = $this->detectTable();
        if (!$table) {
            \Log::warning('add_sarfiyyat_columns: sales order tablosu bulunamadı');
            return;
        }

        Schema::table($table, function (Blueprint $t) {
            $t->decimal('sarfiyyat_total', 18, 4)->default(0);
            $t->boolean('sarfiyyat_charge_customer')->default(false);
        });
    }

    public function down(): void
    {
        $table = $this->detectTable();
        if (!$table) return;

        Schema::table($table, function (Blueprint $t) {
            $t->dropColumn(['sarfiyyat_total', 'sarfiyyat_charge_customer']);
        });
    }
};
