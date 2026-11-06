<?php
namespace App\Livewire\Fin;
use App\Models\FinExchangeRate;
use App\Models\FinCurrency;
use App\Models\Tenant;
use App\Services\Fin\CurrencyService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ExchangeRates extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    
    public string $currency_id = '', $rate_date = '', $rate = '0';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->rate_date = now()->toDateString();
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
        $this->validate(['currency_id' => 'required', 'rate_date' => 'required', 'rate' => 'required|numeric|min:0.000001']);
        try {
            TenantContext::runAs($tid, fn() => app(CurrencyService::class)->addRate([
                'currency_id' => $this->currency_id, 'rate_date' => $this->rate_date, 'rate' => (float)$this->rate
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $rates = []; $currencies = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$rates, &$currencies) {
                $rates = FinExchangeRate::with('currency')->orderByDesc('rate_date')->limit(100)->get()->toArray();
                $currencies = FinCurrency::where('is_base', false)->where('status', 'active')->get(['id', 'code', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.exchange-rates', compact('rates', 'currencies', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Döviz Kurları']);
    }
}
