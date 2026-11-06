<?php
namespace App\Livewire\Ppm;
use App\Models\PpmProject;
use App\Models\FinCustomer;
use App\Models\Tenant;
use App\Services\Ppm\ProjectService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Projects extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public ?string $viewingProjectId = null;

    // Proje Formu
    public string $customer_id = '', $code = '', $name = '', $start_date = '', $end_date = '', $budget = '0', $description = '';

    // Görev Formu
    public string $task_name = '', $task_due_date = '', $task_estimated_hours = '0', $task_description = '';

    // Gider Formu
    public string $expense_date = '', $expense_description = '', $expense_amount = '0';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->start_date = now()->toDateString();
        $this->task_due_date = now()->addDays(7)->toDateString();
        $this->expense_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate() { $this->resetProjectForm(); $this->showForm = true; }
    public function cancel() { $this->showForm = false; $this->resetErrorBag(); }
    public function viewProject(string $id) { $this->viewingProjectId = $id; }
    public function closeProject() { $this->viewingProjectId = null; }

    public function saveProject() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate(['code' => 'required', 'name' => 'required', 'start_date' => 'required']);
        try {
            TenantContext::runAs($tid, fn() => app(ProjectService::class)->create([
                'customer_id' => $this->customer_id ?: null, 'code' => $this->code, 'name' => $this->name,
                'start_date' => $this->start_date, 'end_date' => $this->end_date ?: null,
                'budget' => (float)$this->budget, 'description' => $this->description,
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function addTask() {
        $tid = $this->effectiveTenantId();
        if (!$tid || !$this->viewingProjectId) return;
        $this->validate(['task_name' => 'required']);
        try {
            TenantContext::runAs($tid, fn() => app(ProjectService::class)->addTask($this->viewingProjectId, [
                'name' => $this->task_name, 'due_date' => $this->task_due_date ?: null,
                'estimated_hours' => (float)$this->task_estimated_hours, 'description' => $this->task_description,
            ]));
            $this->reset(['task_name', 'task_description']);
            $this->task_estimated_hours = '0';
            $this->dispatch('task-added');
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function updateTaskStatus(string $taskId, string $status) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => app(ProjectService::class)->updateTaskStatus($taskId, $status));
    }

    public function addExpense() {
        $tid = $this->effectiveTenantId();
        if (!$tid || !$this->viewingProjectId) return;
        $this->validate(['expense_description' => 'required', 'expense_amount' => 'required|numeric|min:0']);
        try {
            TenantContext::runAs($tid, fn() => app(ProjectService::class)->addExpense($this->viewingProjectId, [
                'expense_date' => $this->expense_date, 'description' => $this->expense_description, 'amount' => (float)$this->expense_amount,
            ]));
            $this->reset(['expense_description']);
            $this->expense_amount = '0';
            $this->dispatch('expense-added');
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetProjectForm() {
        $this->reset(['customer_id', 'code', 'name', 'end_date', 'description']);
        $this->start_date = now()->toDateString();
        $this->budget = '0';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $projects = []; $customers = []; $details = null;

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$projects, &$customers, &$details) {
                $projects = PpmProject::with('customer')->orderByDesc('created_at')->get()->toArray();
                $customers = FinCustomer::where('status', 'active')->orderBy('name')->get(['id', 'name'])->toArray();
                if ($this->viewingProjectId) {
                    $p = PpmProject::with(['tasks', 'expenses'])->find($this->viewingProjectId);
                    if ($p) $details = $p->toArray();
                }
            });
        }

        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.ppm.projects', compact('projects', 'customers', 'details', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Projeler']);
    }
}
