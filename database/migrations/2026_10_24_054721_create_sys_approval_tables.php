<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_approval_workflow', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('entity_type', 50); // expense|purchase_order|ap_invoice|ar_invoice
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'entity_type', 'is_active']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('sys_approval_step', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('workflow_id');
            $table->integer('order_no');
            $table->string('name', 255);
            $table->string('approver_role', 50); // user|manager|admin|superadmin
            $table->decimal('min_amount', 18, 4)->nullable();
            $table->decimal('max_amount', 18, 4)->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'workflow_id', 'order_no']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('workflow_id')->references('id')->on('sys_approval_workflow')->cascadeOnDelete();
        });

        Schema::create('sys_approval_request', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('workflow_id');
            $table->string('entity_type', 50);
            $table->string('entity_id', 100);
            $table->string('entity_label', 255);
            $table->jsonb('payload')->default('{}');
            $table->string('status', 20)->default('pending'); // pending|approved|rejected|cancelled
            $table->integer('current_step_order')->default(1);
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestampTz('requested_at');
            $table->timestampTz('finished_at')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'entity_type', 'entity_id']);
            $table->index(['tenant_id', 'status']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('workflow_id')->references('id')->on('sys_approval_workflow')->cascadeOnDelete();
        });

        Schema::create('sys_approval_decision', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('request_id');
            $table->uuid('step_id');
            $table->integer('step_order');
            $table->string('decision', 20); // approve|reject
            $table->unsignedBigInteger('decided_by');
            $table->text('notes')->nullable();
            $table->timestampTz('decided_at');
            $table->timestampsTz();

            $table->index(['tenant_id', 'request_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('request_id')->references('id')->on('sys_approval_request')->cascadeOnDelete();
            $table->foreign('step_id')->references('id')->on('sys_approval_step')->cascadeOnDelete();
        });

        foreach (['sys_approval_workflow', 'sys_approval_step', 'sys_approval_request', 'sys_approval_decision'] as $t) {
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
        Schema::dropIfExists('sys_approval_decision');
        Schema::dropIfExists('sys_approval_request');
        Schema::dropIfExists('sys_approval_step');
        Schema::dropIfExists('sys_approval_workflow');
    }
};
