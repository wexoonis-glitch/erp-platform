<?php
namespace App\Livewire\Eam;
use App\Models\EamMaintenanceOrder;
use App\Models\EamAsset;
use App\Models\Tenant;
use App\Services\Eam\MaintenanceService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MaintenanceOrders extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;

    public string $asset_id = '', $type = 'preventive', $scheduled_date = '', $description = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->scheduled_date = now()->toDateString();
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
        $this->validate(['asset_id' => 'required', 'type' => 'required', 'scheduled_date' => 'required']);
        try {
            TenantContext::runAs($tid, fn() => app(MaintenanceService::class)->create(['asset_id' => $this->asset_id, 'type' => $this->type, 'scheduled_date' => $this->scheduled_date, 'description' => $this->description]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function complete(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) { try { TenantContext::runAs($tid, fn() => app(MaintenanceService::class)->complete($id, 0)); } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); } }
    }

    private function resetForm() { $this->reset(['asset_id', 'description']); $this->type = 'preventive'; $this->scheduled_date = now()->toDateString(); }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $orders = []; $assets = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$orders, &$assets) {
                $orders = EamMaintenanceOrder::with('asset')->orderByDesc('scheduled_date')->limit(100)->get()->toArray();
                $assets = EamAsset::where('status', '!=', 'retired')->orderBy('name')->get(['id', 'code', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.eam.maintenance-orders', compact('orders', 'assets', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Bakım Emirleri']);
    }
}
