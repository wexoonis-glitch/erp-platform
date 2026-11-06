<?php
namespace App\Livewire\Prc;
use App\Models\PrcPurchaseOrder;
use App\Models\PrcGoodsReceipt;
use App\Models\Tenant;
use App\Services\Prc\GoodsReceiptService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class GoodsReceipts extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public string $po_id = '';
    public string $receipt_date = '';
    public array $lines = [];

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->receipt_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function updatedPoId() {
        $tid = $this->effectiveTenantId();
        if (!$tid || !$this->po_id) return;
        TenantContext::runAs($tid, function () {
            $po = PrcPurchaseOrder::with('lines')->find($this->po_id);
            $this->lines = [];
            foreach ($po->lines as $l) {
                $rem = (float)$l->quantity - (float)$l->received_quantity;
                if ($rem > 0) {
                    $this->lines[] = ['po_line_id' => $l->id, 'product_id' => $l->product_id, 'name' => $l->product->name, 'quantity' => (string)$rem, 'unit_cost' => (string)$l->unit_price];
                }
            }
        });
    }

    public function openCreate() { $this->resetForm(); $this->showForm = true; }
    public function cancel() { $this->resetForm(); $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid || !$this->po_id) return;
        $lines = [];
        foreach ($this->lines as $l) {
            if ((float)$l['quantity'] > 0) $lines[] = $l;
        }
        try {
            TenantContext::runAs($tid, fn() => app(GoodsReceiptService::class)->createAndPost(['po_id' => $this->po_id, 'receipt_date' => $this->receipt_date], $lines));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm(): void { $this->reset(['po_id', 'lines']); $this->receipt_date = now()->toDateString(); }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $receipts = []; $pendingPos = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$receipts, &$pendingPos) {
                $receipts = PrcGoodsReceipt::with('purchaseOrder.supplier')->orderByDesc('receipt_date')->limit(100)->get()->toArray();
                $pendingPos = PrcPurchaseOrder::whereIn('status', ['approved', 'partially_received'])->with('supplier')->get()->map(fn($p) => ['id' => $p->id, 'po_no' => $p->po_no, 'supplier' => $p->supplier->name])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.prc.goods-receipts', compact('receipts', 'pendingPos', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Mal Kabul (GR)']);
    }
}
