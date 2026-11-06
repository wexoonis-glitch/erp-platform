<?php
namespace App\Livewire\Inv;
use App\Models\InvStockTransfer;
use App\Models\InvWarehouse;
use App\Models\InvProduct;
use App\Models\Tenant;
use App\Services\Inv\WarehouseService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class StockTransfers extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    
    public string $from_warehouse_id = '', $to_warehouse_id = '', $product_id = '', $quantity = '1', $transfer_date = '', $notes = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->transfer_date = now()->toDateString();
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
            'from_warehouse_id' => 'required', 'to_warehouse_id' => 'required',
            'product_id' => 'required', 'quantity' => 'required|numeric|min:0.01', 'transfer_date' => 'required'
        ]);
        try {
            TenantContext::runAs($tid, fn() => app(WarehouseService::class)->transfer([
                'from_warehouse_id' => $this->from_warehouse_id, 'to_warehouse_id' => $this->to_warehouse_id,
                'product_id' => $this->product_id, 'quantity' => (float)$this->quantity,
                'transfer_date' => $this->transfer_date, 'notes' => $this->notes
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm() {
        $this->reset(['from_warehouse_id', 'to_warehouse_id', 'product_id', 'notes']);
        $this->quantity = '1'; $this->transfer_date = now()->toDateString();
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $transfers = []; $warehouses = []; $products = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$transfers, &$warehouses, &$products) {
                $transfers = InvStockTransfer::with(['fromWarehouse', 'toWarehouse', 'lines.product'])->orderByDesc('created_at')->limit(50)->get()->toArray();
                $warehouses = InvWarehouse::where('status', 'active')->orderBy('name')->get(['id', 'name'])->toArray();
                $products = InvProduct::where('track_stock', true)->orderBy('name')->get(['id', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.inv.stock-transfers', compact('transfers', 'warehouses', 'products', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Depo Transferleri']);
    }
}
