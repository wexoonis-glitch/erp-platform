<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('function_registry', function (Blueprint $table) {
            $table->string('id', 30)->primary();        // 'FIN-AP-0142'
            $table->string('domain', 10)->index();      // 'FIN'
            $table->string('subprocess', 50)->index();  // 'AP-Invoice'
            $table->string('name', 255);
            $table->string('type', 10)->index();        // CRUD|VAL|CALC|WF|DOC|INT|RPT|BATCH|CFG|AUD
            $table->text('description')->nullable();
            $table->string('version', 20)->default('1.0.0');
            $table->string('status', 20)->default('draft')->index(); // draft|active|deprecated|retired
            $table->string('owner', 100)->nullable();
            $table->integer('sla_ms')->nullable();
            $table->jsonb('spec')->default('{}');       // tam spesifikasyon kartı
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('function_registry');
    }
};
