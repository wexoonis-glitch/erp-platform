<?php
namespace App\Livewire\Log;
use App\Models\LogShipment;
use App\Models\LogCarrier;
use App\Models\OtcSalesOrder;
use App\Models\Tenant;
use App\Services\Log\ShippingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Shipments extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    
    public string $sales_order_id = '', $carrier_id = '', $shipment_date = '', $tracking_no = '', $notes = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->shipment_date = now()->toDateString();
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
        $this->validate(['sales_order_id' => 'required', 'carrier_id' => 'required', 'shipment_date' => 'required']);
        try {
            TenantContext::runAs($tid, fn() => app(ShippingService::class)->createShipment([
                'sales_order_id' => $this->sales_order_id, 'carrier_id' => $this->carrier_id,
                'shipment_date' => $this->shipment_date, 'tracking_no' => $this->tracking_no, 'notes' => $this->notes
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm() {
        $this->reset(['sales_order_id', 'carrier_id', 'tracking_no', 'notes']);
        $this->shipment_date = now()->toDateString();
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $shipments = []; $orders = []; $carriers = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$shipments, &$orders, &$carriers) {
                $shipments = LogShipment::with(['salesOrder.customer', 'carrier', 'lines.product'])->orderByDesc('created_at')->get()->toArray();
                $orders = OtcSalesOrder::whereIn('status', ['invoiced', 'approved'])->with('customer')->get()->map(fn($o) => ['id' => $o->id, 'name' => $o->so_no . ' - ' . $o->customer->name])->toArray();
                $carriers = LogCarrier::where('status', 'active')->orderBy('name')->get(['id', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.log.shipments', compact('shipments', 'orders', 'carriers', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Sevkiyatlar']);
    }
}
