<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('employee_no', 30);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('national_id', 30)->nullable(); // TCKN
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->decimal('gross_salary', 18, 4)->default(0);
            $table->string('status', 20)->default('active'); // active|on_leave|terminated
            $table->timestampsTz();

            $table->unique(['tenant_id', 'employee_no']);
            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE hr_employee ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE hr_employee FORCE ROW LEVEL SECURITY');

        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation ON hr_employee
                USING (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
                WITH CHECK (current_setting('app.is_superadmin', true) = 'true' OR tenant_id = NULLIF(current_setting('app.tenant_id', true), '')::uuid)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee');
    }
};
