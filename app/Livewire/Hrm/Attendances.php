<?php
namespace App\Livewire\Hrm;
use App\Models\HrAttendance;
use App\Models\HrEmployee;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Attendances extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;

    public string $employee_id = '';
    public string $date = '';
    public string $status = 'present';
    public string $overtime_hours = '0';
    public string $notes = '';

    public function mount() {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) abort(403);

        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate() {
        $this->resetForm();
        $this->showForm = true;
    }

    public function cancel() { 
        $this->resetForm(); 
        $this->showForm = false; 
        $this->resetErrorBag(); 
    }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        
        $this->validate([
            'employee_id' => 'required',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,leave,sick,holiday',
            'overtime_hours' => 'required|numeric|min:0|max:24',
        ]);

        try {
            TenantContext::runAs($tid, function () use ($tid) {
                // Aynı gün için zaten kayıt var mı? (Update or Create)
                $attendance = HrAttendance::where('employee_id', $this->employee_id)
                    ->where('date', $this->date)
                    ->first();

                if ($attendance) {
                    $attendance->update([
                        'status' => $this->status,
                        'overtime_hours' => (float) $this->overtime_hours,
                        'notes' => $this->notes ?: null,
                    ]);
                } else {
                    HrAttendance::create([
                        'id' => (string) Str::uuid(),
                        'tenant_id' => $tid,
                        'employee_id' => $this->employee_id,
                        'date' => $this->date,
                        'status' => $this->status,
                        'overtime_hours' => (float) $this->overtime_hours,
                        'notes' => $this->notes ?: null,
                    ]);
                }
            });
            $this->cancel();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, fn() => HrAttendance::findOrFail($id)->delete());
    }

    private function resetForm() {
        $this->reset(['employee_id', 'notes']);
        $this->date = now()->toDateString();
        $this->status = 'present';
        $this->overtime_hours = '0';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $attendances = [];
        $employees = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$attendances, &$employees) {
                // Son 100 puantaj kaydı
                $attendances = HrAttendance::with('employee')->orderByDesc('date')->orderByDesc('created_at')->limit(100)->get()->toArray();
                // Aktif personel listesi (dropdown için)
                $employees = HrEmployee::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])->toArray();
            });
        }

        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);

        return view('livewire.hrm.attendances', compact('attendances', 'employees', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Puantaj Yönetimi']);
    }
}
