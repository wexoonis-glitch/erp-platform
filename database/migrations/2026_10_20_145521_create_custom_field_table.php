<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('entity', 100);               // 'ap_invoice','tenant'
            $table->string('code', 50);                  // 'alerji_notu'
            $table->string('label', 255);
            $table->string('data_type', 20);             // string|number|date|bool|enum|json
            $table->jsonb('config')->default('{}');      // seçenekler, validasyon, default
            $table->timestampsTz();

            $table->unique(['tenant_id', 'entity', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field');
    }
};
