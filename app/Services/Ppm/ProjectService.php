<?php
namespace App\Services\Ppm;
use App\Models\PpmProject;
use App\Models\PpmTask;
use App\Models\PpmExpense;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProjectService {
    public function create(array $data): PpmProject {
        $tenantId = TenantContext::id();
        if (PpmProject::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Bu proje kodu zaten kullanımda.']);
        }
        return PpmProject::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'customer_id' => $data['customer_id'] ?? null, 'code' => $data['code'], 'name' => $data['name'],
            'start_date' => $data['start_date'], 'end_date' => $data['end_date'] ?? null,
            'budget' => $data['budget'] ?? 0, 'status' => 'active', 'description' => $data['description'] ?? null,
        ]);
    }

    public function addTask(string $projectId, array $data): PpmTask {
        $tenantId = TenantContext::id();
        return PpmTask::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'project_id' => $projectId,
            'name' => $data['name'], 'status' => 'todo', 'due_date' => $data['due_date'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? 0, 'actual_hours' => 0,
            'description' => $data['description'] ?? null,
        ]);
    }

    public function updateTaskStatus(string $taskId, string $status): void {
        PpmTask::findOrFail($taskId)->update(['status' => $status]);
    }

    public function addExpense(string $projectId, array $data): PpmExpense {
        $tenantId = TenantContext::id();
        return PpmExpense::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'project_id' => $projectId,
            'expense_date' => $data['expense_date'], 'description' => $data['description'], 'amount' => $data['amount'],
        ]);
    }
}
