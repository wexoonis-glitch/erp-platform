<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_module', function (Blueprint $table) {
            $table->uuid('tenant_id');
            $table->string('module_id', 30);              // 'PLT','FIN','RST'
            $table->string('version', 20);
            $table->string('status', 20)->default('installing'); // installing|active|suspended
            $table->jsonb('config')->default('{}');
            $table->timestampTz('installed_at')->useCurrent();

            $table->primary(['tenant_id', 'module_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_module');
    }
};
