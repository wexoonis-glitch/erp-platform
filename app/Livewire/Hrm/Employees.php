<?php
namespace App\Livewire\Hrm;
use App\Models\HrEmployee;
use App\Models\FinCostCenter;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Employees extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public ?string $editingId = null;

    public string $employee_no = '', $first_name = '', $last_name = '', $email = '', $phone = '', $national_id = '', $department = '', $cost_center_id = '', $position = '', $hire_date = '', $termination_date = '', $gross_salary = '0', $status = 'active';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->hire_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate() { $this->resetForm(); $this->showForm = true; }
    public function cancel() { $this->resetForm(); $this->showForm = false; $this->resetErrorBag(); }

    public function openEdit(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () use ($id) {
            $e = HrEmployee::findOrFail($id);
            $this->editingId = $e->id;
            $this->employee_no = $e->employee_no; $this->first_name = $e->first_name; $this->last_name = $e->last_name;
            $this->email = $e->email ?? ''; $this->phone = $e->phone ?? ''; $this->national_id = $e->national_id ?? '';
            $this->department = $e->department ?? ''; $this->cost_center_id = $e->cost_center_id ?? ''; $this->position = $e->position ?? '';
            $this->hire_date = $e->hire_date->toDateString(); $this->termination_date = $e->termination_date?->toDateString() ?? '';
            $this->gross_salary = (string) $e->gross_salary; $this->status = $e->status;
        });
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate(['employee_no' => 'required', 'first_name' => 'required', 'last_name' => 'required', 'hire_date' => 'required', 'gross_salary' => 'required|numeric|min:0']);
        
        try {
            TenantContext::runAs($tid, function () {
                $data = [
                    'employee_no' => $this->employee_no, 'first_name' => $this->first_name, 'last_name' => $this->last_name,
                    'email' => $this->email ?: null, 'phone' => $this->phone ?: null, 'national_id' => $this->national_id ?: null,
                    'department' => $this->department ?: null, 'cost_center_id' => $this->cost_center_id ?: null,
                    'position' => $this->position ?: null, 'hire_date' => $this->hire_date, 'termination_date' => $this->termination_date ?: null,
                    'gross_salary' => (float) $this->gross_salary, 'status' => $this->status,
                ];
                if ($this->editingId) {
                    $e = HrEmployee::findOrFail($this->editingId);
                    if (HrEmployee::where('employee_no', $this->employee_no)->where('id', '!=', $e->id)->exists()) throw ValidationException::withMessages(['employee_no' => 'Bu personel numarası zaten kullanımda.']);
                    $e->update($data);
                } else {
                    if (HrEmployee::where('employee_no', $this->employee_no)->exists()) throw ValidationException::withMessages(['employee_no' => 'Bu personel numarası zaten kullanımda.']);
                    $data['id'] = (string) Str::uuid(); $data['tenant_id'] = TenantContext::id();
                    HrEmployee::create($data);
                }
            });
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => HrEmployee::findOrFail($id)->delete());
    }

    private function resetForm() {
        $this->reset(['editingId', 'employee_no', 'first_name', 'last_name', 'email', 'phone', 'national_id', 'department', 'cost_center_id', 'position', 'termination_date']);
        $this->hire_date = now()->toDateString(); $this->gross_salary = '0'; $this->status = 'active';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $employees = []; $costCenters = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$employees, &$costCenters) {
                $employees = HrEmployee::orderBy('first_name')->get()->toArray();
                $costCenters = FinCostCenter::where('status', 'active')->orderBy('name')->get(['id', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.hrm.employees', compact('employees', 'costCenters', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Personel Yönetimi']);
    }
}
