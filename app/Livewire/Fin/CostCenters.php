<?php
namespace App\Livewire\Fin;
use App\Models\FinCostCenter;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class CostCenters extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    
    public string $code = '', $name = '', $manager_name = '';

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
            TenantContext::runAs($tid, function () use ($tid) {
                if (FinCostCenter::where('code', $this->code)->exists()) {
                    throw ValidationException::withMessages(['code' => 'Bu masraf merkezi kodu zaten kullanımda.']);
                }
                FinCostCenter::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tid,
                    'code' => $this->code, 'name' => $this->name, 'manager_name' => $this->manager_name ?: null, 'status' => 'active',
                ]);
            });
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, fn() => FinCostCenter::findOrFail($id)->delete());
    }

    private function resetForm() { $this->reset(['code', 'name', 'manager_name']); }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $centers = [];
        if ($tid) { TenantContext::runAs($tid, function () use (&$centers) { $centers = FinCostCenter::orderBy('code')->get()->toArray(); }); }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.cost-centers', compact('centers', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Masraf Merkezleri']);
    }
}
