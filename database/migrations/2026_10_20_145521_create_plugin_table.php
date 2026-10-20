<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();        // null = vendor geneli
            $table->string('module_id', 30)->nullable();
            $table->string('version', 20);
            $table->jsonb('manifest');
            $table->string('vendor', 100)->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('status', 20)->default('registered')->index();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin');
    }
};
