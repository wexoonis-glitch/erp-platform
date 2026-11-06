<?php
namespace App\Livewire\Mfg;
use App\Models\MfgProductionOrder;
use App\Models\MfgBom;
use App\Models\Tenant;
use App\Services\Mfg\ManufacturingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ProductionOrders extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public string $bom_id = '';
    public string $order_date = '';
    public string $quantity = '1';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->order_date = now()->toDateString();
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
        try {
            TenantContext::runAs($tid, fn() => app(ManufacturingService::class)->createProductionOrder(['bom_id' => $this->bom_id, 'order_date' => $this->order_date, 'quantity' => (float)$this->quantity]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function complete(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) {
            try { TenantContext::runAs($tid, fn() => app(ManufacturingService::class)->completeProductionOrder($id)); } 
            catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
        }
    }

    private function resetForm(): void { $this->reset(['bom_id', 'quantity']); $this->order_date = now()->toDateString(); }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $orders = []; $boms = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$orders, &$boms) {
                $orders = MfgProductionOrder::with('product')->orderByDesc('order_date')->limit(100)->get()->toArray();
                $boms = MfgBom::where('status', 'active')->with('product')->get()->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.mfg.production-orders', compact('orders', 'boms', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Üretim Emirleri']);
    }
}
