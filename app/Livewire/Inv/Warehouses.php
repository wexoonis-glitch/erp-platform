<?php
namespace App\Livewire\Inv;
use App\Models\InvWarehouse;
use App\Models\Tenant;
use App\Services\Inv\WarehouseService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Warehouses extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public string $code = '', $name = '';

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
        $this->validate(['code' => 'required', 'name' => 'required']);
        try {
            TenantContext::runAs($tid, fn() => app(WarehouseService::class)->createWarehouse(['code' => $this->code, 'name' => $this->name]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            TenantContext::runAs($tid, function () use ($id) {
                $wh = InvWarehouse::findOrFail($id);
                if ($wh->code === 'MAIN') throw ValidationException::withMessages(['code' => 'Ana Depo silinemez.']);
                $wh->delete();
            });
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm() { $this->reset(['code', 'name']); }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $warehouses = [];
        if ($tid) { TenantContext::runAs($tid, function () use (&$warehouses) { $warehouses = InvWarehouse::orderBy('code')->get()->toArray(); }); }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.inv.warehouses', compact('warehouses', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Depolar']);
    }
}
