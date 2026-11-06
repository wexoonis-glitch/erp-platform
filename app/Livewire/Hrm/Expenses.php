<?php
namespace App\Livewire\Hrm;
use App\Models\HrExpenseClaim;
use App\Models\HrEmployee;
use App\Models\FinBankAccount;
use App\Models\Tenant;
use App\Services\Hrm\ExpenseService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Expenses extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    
    public bool $showForm = false;
    public bool $showPayForm = false;
    public ?string $payingId = null;

    // Masraf Formu
    public string $employee_id = '', $expense_date = '', $category = 'travel', $amount = '0', $description = '';
    
    // Ödeme Formu
    public string $payment_account_id = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->expense_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate() { $this->resetForm(); $this->showForm = true; }
    public function cancel() { $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate([
            'employee_id' => 'required', 'expense_date' => 'required',
            'amount' => 'required|numeric|min:0.01', 'description' => 'required'
        ]);
        try {
            TenantContext::runAs($tid, fn() => app(ExpenseService::class)->create([
                'employee_id' => $this->employee_id, 'expense_date' => $this->expense_date,
                'category' => $this->category, 'amount' => (float)$this->amount, 'description' => $this->description
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function submit(string $id) { $tid = $this->effectiveTenantId(); if ($tid) TenantContext::runAs($tid, fn() => app(ExpenseService::class)->submit($id)); }
    public function approve(string $id) { $tid = $this->effectiveTenantId(); if ($tid) TenantContext::runAs($tid, fn() => app(ExpenseService::class)->approve($id)); }

    public function openPay(string $id) { $this->payingId = $id; $this->showPayForm = true; }
    public function cancelPay() { $this->showPayForm = false; $this->payingId = null; $this->resetErrorBag(); }
    
    public function pay() {
        $tid = $this->effectiveTenantId();
        if (!$tid || !$this->payingId) return;
        $this->validate(['payment_account_id' => 'required']);
        try {
            TenantContext::runAs($tid, fn() => app(ExpenseService::class)->pay($this->payingId, $this->payment_account_id));
            $this->cancelPay();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm() {
        $this->reset(['employee_id', 'description']);
        $this->expense_date = now()->toDateString(); $this->category = 'travel'; $this->amount = '0';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $expenses = []; $employees = []; $bankAccounts = [];
        
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$expenses, &$employees, &$bankAccounts) {
                $expenses = HrExpenseClaim::with('employee')->orderByDesc('created_at')->get()->toArray();
                $employees = HrEmployee::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name'])->toArray();
                $bankAccounts = FinBankAccount::where('status', 'active')->orderBy('name')->get(['id', 'name'])->toArray();
            });
        }
        
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.hrm.expenses', compact('expenses', 'employees', 'bankAccounts', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Masraf Yönetimi']);
    }
}
