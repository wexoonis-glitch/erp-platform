<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plugin', function (Blueprint $table) {
            $table->string('plugin_id', 50)->nullable()->after('id');
            $table->string('name', 255)->nullable()->after('plugin_id');
            $table->string('path', 500)->nullable()->after('module_id');
            $table->timestampTz('installed_at')->nullable()->after('status');
            $table->timestampTz('enabled_at')->nullable()->after('installed_at');
        });

        // Tablo boş olduğu için unique eklemek güvenli
        DB::statement('ALTER TABLE plugin ADD CONSTRAINT plugin_tenant_pluginid_unique UNIQUE (tenant_id, plugin_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE plugin DROP CONSTRAINT IF EXISTS plugin_tenant_pluginid_unique');

        Schema::table('plugin', function (Blueprint $table) {
            $table->dropColumn(['plugin_id', 'name', 'path', 'installed_at', 'enabled_at']);
        });
    }
};
