<?php
namespace App\Livewire\Fin;
use App\Models\FinBankTransaction;
use App\Models\FinBankAccount;
use App\Models\FinAccount;
use App\Models\Tenant;
use App\Services\Fin\BankService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class BankTransactions extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;

    public string $bank_account_id = '', $transaction_date = '', $type = 'deposit', $amount = '0', $counter_account_id = '', $description = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->transaction_date = now()->toDateString();
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
            'bank_account_id' => 'required', 'transaction_date' => 'required',
            'amount' => 'required|numeric|min:0.01', 'counter_account_id' => 'required', 'description' => 'required'
        ]);
        
        $data = [
            'bank_account_id' => $this->bank_account_id, 'transaction_date' => $this->transaction_date,
            'amount' => (float)$this->amount, 'counter_account_id' => $this->counter_account_id, 'description' => $this->description
        ];

        try {
            TenantContext::runAs($tid, function () use ($data) {
                $svc = app(BankService::class);
                if ($this->type === 'deposit') $svc->recordDeposit($data);
                else $svc->recordWithdrawal($data);
            });
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm() {
        $this->reset(['bank_account_id', 'counter_account_id', 'description']);
        $this->transaction_date = now()->toDateString(); $this->type = 'deposit'; $this->amount = '0';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $transactions = []; $bankAccounts = []; $accounts = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$transactions, &$bankAccounts, &$accounts) {
                $transactions = FinBankTransaction::with(['bankAccount', 'counterAccount'])->orderByDesc('transaction_date')->limit(100)->get()->toArray();
                $bankAccounts = FinBankAccount::where('status', 'active')->orderBy('name')->get(['id', 'name'])->toArray();
                $accounts = FinAccount::where('is_postable', true)->whereNotIn('code', ['100', '102', '103'])->orderBy('code')->get(['id', 'code', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.bank-transactions', compact('transactions', 'bankAccounts', 'accounts', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Banka Hareketleri']);
    }
}
