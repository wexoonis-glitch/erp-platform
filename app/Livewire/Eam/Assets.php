<?php
namespace App\Livewire\Eam;
use App\Models\EamAsset;
use App\Models\Tenant;
use App\Services\Eam\AssetService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Assets extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public ?string $editingId = null;

    public string $code = '', $name = '', $category = 'Makine', $serial_no = '', $purchase_date = '', $purchase_cost = '0', $status = 'active', $notes = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->purchase_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate() { $this->resetForm(); $this->showForm = true; }
    public function cancel() { $this->showForm = false; $this->resetErrorBag(); }
    
    public function openEdit(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () use ($id) {
            $a = EamAsset::findOrFail($id);
            $this->editingId = $a->id;
            $this->code = $a->code; $this->name = $a->name; $this->category = $a->category;
            $this->serial_no = $a->serial_no ?? ''; $this->purchase_date = $a->purchase_date?->toDateString() ?? '';
            $this->purchase_cost = (string)$a->purchase_cost; $this->status = $a->status; $this->notes = $a->notes ?? '';
        });
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate(['code' => 'required', 'name' => 'required', 'category' => 'required']);
        $data = [
            'code' => $this->code, 'name' => $this->name, 'category' => $this->category,
            'serial_no' => $this->serial_no ?: null, 'purchase_date' => $this->purchase_date ?: null,
            'purchase_cost' => (float)$this->purchase_cost, 'status' => $this->status, 'notes' => $this->notes ?: null,
        ];
        try {
            TenantContext::runAs($tid, function () use ($data) {
                $svc = app(AssetService::class);
                if ($this->editingId) $svc->update($this->editingId, $data);
                else $svc->create($data);
            });
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) { try { TenantContext::runAs($tid, fn() => app(AssetService::class)->delete($id)); } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); } }
    }

    private function resetForm() {
        $this->reset(['editingId', 'code', 'name', 'serial_no', 'notes']);
        $this->category = 'Makine'; $this->purchase_date = now()->toDateString(); $this->purchase_cost = '0'; $this->status = 'active';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $assets = [];
        if ($tid) { TenantContext::runAs($tid, function () use (&$assets) { $assets = EamAsset::orderBy('code')->get()->toArray(); }); }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.eam.assets', compact('assets', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Varlık Yönetimi']);
    }
}
