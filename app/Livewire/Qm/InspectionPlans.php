<?php
namespace App\Livewire\Qm;
use App\Models\QmInspectionPlan;
use App\Models\InvProduct;
use App\Models\Tenant;
use App\Services\Qm\QualityService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class InspectionPlans extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;

    public string $product_id = '', $code = '', $name = '', $description = '';
    public array $criteria = [];

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

    public function openCreate() { $this->resetForm(); $this->criteria = [['name' => '', 'expected_value' => '']]; $this->showForm = true; }
    public function addCriteria() { $this->criteria[] = ['name' => '', 'expected_value' => '']; }
    public function removeCriteria(int $i) { unset($this->criteria[$i]); $this->criteria = array_values($this->criteria); }
    public function cancel() { $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate(['product_id' => 'required', 'code' => 'required', 'name' => 'required']);
        try {
            $cleanCriteria = array_filter($this->criteria, fn($c) => !empty($c['name']));
            TenantContext::runAs($tid, fn() => app(QualityService::class)->createPlan([
                'product_id' => $this->product_id, 'code' => $this->code, 'name' => $this->name, 'description' => $this->description,
            ], $cleanCriteria));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm() {
        $this->reset(['product_id', 'code', 'name', 'description', 'criteria']);
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $plans = []; $products = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$plans, &$products) {
                $plans = QmInspectionPlan::with(['product', 'criteria'])->orderByDesc('created_at')->get()->toArray();
                $products = InvProduct::where('status', 'active')->orderBy('name')->get(['id', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.qm.inspection-plans', compact('plans', 'products', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Muayene Planları']);
    }
}
