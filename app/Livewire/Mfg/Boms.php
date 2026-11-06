<?php
namespace App\Livewire\Mfg;
use App\Models\MfgBom;
use App\Models\InvProduct;
use App\Models\Tenant;
use App\Services\Mfg\ManufacturingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Boms extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public string $product_id = '';
    public string $code = '';
    public string $name = '';
    public string $quantity = '1';
    public array $lines = [];

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

    public function openCreate() { $this->resetForm(); $this->lines = [$this->emptyLine()]; $this->showForm = true; }
    public function addLine() { $this->lines[] = $this->emptyLine(); }
    public function removeLine(int $i) { unset($this->lines[$i]); $this->lines = array_values($this->lines); }
    public function cancel() { $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $data = ['product_id' => $this->product_id, 'code' => $this->code, 'name' => $this->name, 'quantity' => (float)$this->quantity];
        $lines = [];
        foreach ($this->lines as $l) {
            if (!empty($l['component_id']) && (float)$l['quantity'] > 0) {
                $lines[] = ['component_id' => $l['component_id'], 'quantity' => (float)$l['quantity']];
            }
        }
        try {
            TenantContext::runAs($tid, fn() => app(ManufacturingService::class)->createBom($data, $lines));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function emptyLine(): array { return ['component_id' => '', 'quantity' => '1']; }
    private function resetForm(): void { $this->reset(['product_id', 'code', 'name', 'quantity', 'lines']); }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $boms = []; $products = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$boms, &$products) {
                $boms = MfgBom::with(['product', 'lines.component'])->orderBy('code')->get()->toArray();
                $products = InvProduct::where('status', 'active')->orderBy('name')->get(['id', 'code', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.mfg.boms', compact('boms', 'products', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Reçeteler (BOM)']);
    }
}
