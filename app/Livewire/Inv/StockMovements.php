<?php
namespace App\Livewire\Inv;
use App\Models\InvProduct;
use App\Models\InvStockMovement;
use App\Models\Tenant;
use App\Services\Inv\StockService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class StockMovements extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';

    public bool $showInForm = false;
    public string $in_product_id = '';
    public string $in_quantity = '';
    public string $in_unit_cost = '';
    public string $in_date = '';
    public string $in_desc = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->in_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openInForm() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->reset(['in_product_id', 'in_quantity', 'in_unit_cost', 'in_desc']);
        $this->in_date = now()->toDateString();
        $this->showInForm = true;
    }

    public function cancelInForm() { 
        $this->showInForm = false; 
        $this->resetErrorBag(); 
    }

    public function saveIn() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate([
            'in_product_id' => 'required',
            'in_quantity' => 'required|numeric|min:0.01',
            'in_unit_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            TenantContext::runAs($tid, function () {
                $svc = app(StockService::class);
                $warehouse = $svc->getDefaultWarehouse(); // Güvenli depo çağrısı
                
                $svc->recordIn(
                    $this->in_product_id, $warehouse->id, $this->in_date, 
                    (float)$this->in_quantity, (float)($this->in_unit_cost ?: 0), 
                    'MANUAL', null, $this->in_desc ?: 'Manuel Stok Girişi'
                );
            });
            $this->cancelInForm();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $movements = []; $balances = []; $products = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$movements, &$balances, &$products) {
                $movements = InvStockMovement::with(['product', 'warehouse'])->orderByDesc('created_at')->limit(50)->get();
                $productsList = InvProduct::where('track_stock', true)->orderBy('code')->get();
                $svc = app(StockService::class);
                foreach ($productsList as $p) {
                    $balances[] = ['code' => $p->code, 'name' => $p->name, 'unit' => $p->unit, 'qty' => $svc->getBalance($p->id)];
                }
                $products = $productsList->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'purchase_price' => $p->purchase_price])->toArray();
            });
        }

        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);

        return view('livewire.inv.stock-movements', compact('movements', 'balances', 'products', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Stok Durumu']);
    }
}
