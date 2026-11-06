<?php
namespace App\Livewire\Fin;
use App\Models\FinBankAccount;
use App\Models\FinAccount;
use App\Models\Tenant;
use App\Services\Fin\BankService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class BankAccounts extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;

    public string $name = '', $type = 'bank', $account_no = '', $iban = '', $currency = 'TRY', $gl_account_id = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
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
        $this->validate(['name' => 'required', 'gl_account_id' => 'required']);
        try {
            TenantContext::runAs($tid, fn() => app(BankService::class)->createAccount([
                'name' => $this->name, 'type' => $this->type, 'account_no' => $this->account_no,
                'iban' => $this->iban, 'currency' => $this->currency, 'gl_account_id' => $this->gl_account_id
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm() {
        $this->reset(['name', 'account_no', 'iban', 'gl_account_id']);
        $this->type = 'bank'; $this->currency = 'TRY';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $accounts = []; $glAccounts = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$accounts, &$glAccounts) {
                $accounts = FinBankAccount::orderBy('name')->get()->toArray();
                $glAccounts = FinAccount::whereIn('code', ['100', '102', '103'])->orderBy('code')->get(['id', 'code', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.bank-accounts', compact('accounts', 'glAccounts', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Banka & Kasa Hesapları']);
    }
}
