<?php
namespace App\Livewire\Fin;
use App\Models\FinCurrency;
use App\Models\Tenant;
use App\Services\Fin\CurrencyService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Currencies extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    
    public string $code = '', $name = '', $symbol = '';
    public bool $is_base = false;

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
        $this->validate(['code' => 'required|size:3', 'name' => 'required']);
        try {
            TenantContext::runAs($tid, fn() => app(CurrencyService::class)->createCurrency([
                'code' => $this->code, 'name' => $this->name, 'symbol' => $this->symbol, 'is_base' => $this->is_base
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm() { $this->reset(['code', 'name', 'symbol']); $this->is_base = false; }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $currencies = [];
        if ($tid) { TenantContext::runAs($tid, function () use (&$currencies) { $currencies = FinCurrency::orderBy('is_base', 'desc')->orderBy('code')->get()->toArray(); }); }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.currencies', compact('currencies', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Para Birimleri']);
    }
}
