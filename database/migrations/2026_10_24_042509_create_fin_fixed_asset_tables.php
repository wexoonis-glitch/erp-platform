<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_fixed_asset', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('category', 50); // vehicle|machinery|building|furniture|it
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 18, 4);
            $table->integer('useful_life_months'); // Faydalı ömür (ay)
            $table->string('depreciation_method', 20)->default('straight_line'); // straight_line|declining
            $table->decimal('salvage_value', 18, 4)->default(0); // Hurda değeri
            $table->decimal('accumulated_depreciation', 18, 4)->default(0);
            $table->decimal('book_value', 18, 4); // Kalan defter değeri

            $table->uuid('asset_account_id');       // 253, 254, 255
            $table->uuid('accumulated_dep_account_id'); // 257
            $table->uuid('depreciation_expense_account_id'); // 770 veya 730
            $table->uuid('cost_center_id')->nullable();

            $table->string('status', 20)->default('active'); // active|fully_depreciated|disposed
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_amount', 18, 4)->nullable();
            $table->string('disposal_notes', 500)->nullable();
            $table->uuid('eam_asset_id')->nullable(); // EAM ile opsiyonel bağ

            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_account_id')->references('id')->on('fin_account')->restrictOnDelete();
            $table->foreign('accumulated_dep_account_id')->references('id')->on('fin_account')->restrictOnDelete();
            $table->foreign('depreciation_expense_account_id')->references('id')->on('fin_account')->restrictOnDelete();
            $table->foreign('cost_center_id')->references('id')->on('fin_cost_center')->nullOnDelete();
        });

        Schema::create('fin_depreciation_schedule', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('asset_id');
            $table->string('period_year', 4);
            $table->string('period_month', 2);
            $table->decimal('depreciation_amount', 18, 4);
            $table->decimal('accumulated_after', 18, 4); // Bu taksitten sonra birikmiş amortisman
            $table->string('status', 20)->default('pending'); // pending|posted
            $table->uuid('journal_entry_id')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'asset_id', 'period_year', 'period_month']);
            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_id')->references('id')->on('fin_fixed_asset')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('fin_journal_entry')->nullOnDelete();
        });

        foreach (['fin_fixed_asset', 'fin_depreciation_schedule'] as $t) {
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
        Schema::dropIfExists('fin_depreciation_schedule');
        Schema::dropIfExists('fin_fixed_asset');
    }
};
