<?php
namespace App\Livewire\Prc;
use App\Models\PrcPurchaseOrder;
use App\Models\FinSupplier;
use App\Models\InvProduct;
use App\Models\SysApprovalRequest;
use App\Models\Tenant;
use App\Services\Prc\PurchaseOrderService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PurchaseOrders extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public string $supplier_id = '';
    public string $order_date = '';
    public string $expected_date = '';
    public string $notes = '';
    public array $lines = [];
    public string $message = '';

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

    public function openCreate() { $this->resetForm(); $this->lines = [$this->emptyLine()]; $this->showForm = true; }
    public function addLine() { $this->lines[] = $this->emptyLine(); }
    public function removeLine(int $i) { unset($this->lines[$i]); $this->lines = array_values($this->lines); }
    public function cancel() { $this->resetForm(); $this->showForm = false; $this->resetErrorBag(); }

    public function productChanged(int $index) {
        $tid = $this->effectiveTenantId();
        $pid = $this->lines[$index]['product_id'] ?? null;
        if (!$tid || !$pid) return;
        TenantContext::runAs($tid, function () use ($index, $pid) {
            $p = InvProduct::find($pid);
            if ($p) {
                $this->lines[$index]['unit_price'] = (string)$p->purchase_price;
                $this->lines[$index]['vat_rate'] = (string)$p->vat_rate;
            }
        });
    }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $data = ['supplier_id' => $this->supplier_id, 'order_date' => $this->order_date, 'expected_date' => $this->expected_date ?: null, 'notes' => $this->notes];
        $lines = [];
        foreach ($this->lines as $l) {
            if (empty($l['product_id'])) continue;
            $lines[] = ['product_id' => $l['product_id'], 'quantity' => (float)$l['quantity'], 'unit_price' => (float)$l['unit_price'], 'vat_rate' => (float)$l['vat_rate'], 'description' => null];
        }
        try {
            TenantContext::runAs($tid, fn() => app(PurchaseOrderService::class)->create($data, $lines));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function approve(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->message = '';
        try {
            TenantContext::runAs($tid, fn() => app(PurchaseOrderService::class)->approve($id));

            // Onay akışı var mı kontrol et
            $hasPending = TenantContext::runAs($tid, fn() => SysApprovalRequest::where('entity_type', 'purchase_order')->where('entity_id', $id)->where('status', 'pending')->exists());
            $this->message = $hasPending
                ? 'Onay sürecine gönderildi. Onay tamamlandığında PO otomatik onaylanacak.'
                : 'PO onaylandı.';
        } catch (ValidationException $e) {
            $this->addError('general', collect($e->errors())->flatten()->first());
        }
    }

    private function emptyLine(): array { return ['product_id' => '', 'quantity' => '1', 'unit_price' => '0', 'vat_rate' => '20']; }
    private function resetForm(): void { $this->reset(['supplier_id', 'expected_date', 'notes', 'lines']); $this->order_date = now()->toDateString(); }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $orders = []; $suppliers = []; $products = []; $pendingPoIds = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$orders, &$suppliers, &$products, &$pendingPoIds) {
                $orders = PrcPurchaseOrder::with('supplier')->orderByDesc('order_date')->limit(100)->get()->toArray();
                $suppliers = FinSupplier::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $products = InvProduct::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $pendingPoIds = SysApprovalRequest::where('entity_type', 'purchase_order')->where('status', 'pending')->pluck('entity_id')->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.prc.purchase-orders', compact('orders', 'suppliers', 'products', 'isSuper', 'tenants', 'pendingPoIds'))->layout('layouts.app', ['title' => 'Satın Alma Siparişleri']);
    }
}
