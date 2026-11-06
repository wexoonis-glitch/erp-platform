<?php
namespace App\Livewire\Hrm;
use App\Models\HrPayrollRun;
use App\Models\Tenant;
use App\Services\Hrm\PayrollService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Payrolls extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;

    public string $period = '';
    public string $payment_date = '';
    public ?string $viewingRunId = null;

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->period = now()->format('Y-m');
        $this->payment_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate() { $this->showForm = true; }
    public function cancel() { $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate(['period' => 'required', 'payment_date' => 'required|date']);
        try {
            TenantContext::runAs($tid, fn() => app(PayrollService::class)->generatePayroll($this->period, $this->payment_date));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function post(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) { try { TenantContext::runAs($tid, fn() => app(PayrollService::class)->postPayroll($id)); } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); } }
    }

    public function viewDetails(string $id) { $this->viewingRunId = $id; }
    public function closeDetails() { $this->viewingRunId = null; }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $runs = []; $details = null;

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$runs, &$details) {
                $runs = HrPayrollRun::orderByDesc('period')->get()->toArray();
                if ($this->viewingRunId) {
                    $run = HrPayrollRun::with('items.employee')->find($this->viewingRunId);
                    if ($run) $details = $run->toArray();
                }
            });
        }

        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.hrm.payrolls', compact('runs', 'details', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Bordro Yönetimi']);
    }
}
