<?php
namespace App\Livewire\Qm;
use App\Models\QmNcr;
use App\Models\InvProduct;
use App\Models\Tenant;
use App\Services\Qm\QualityService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Ncrs extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;

    public string $product_id = '', $report_date = '', $source = 'incoming', $reference_no = '', $quantity = '1', $description = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->report_date = now()->toDateString();
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
        $this->validate(['product_id' => 'required', 'report_date' => 'required', 'quantity' => 'required|numeric|min:0.01']);
        try {
            TenantContext::runAs($tid, fn() => app(QualityService::class)->createNcr([
                'product_id' => $this->product_id, 'report_date' => $this->report_date, 'source' => $this->source,
                'reference_no' => $this->reference_no, 'quantity' => (float)$this->quantity, 'description' => $this->description,
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function updateStatus(string $id, string $status) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => app(QualityService::class)->updateNcrStatus($id, $status));
    }

    private function resetForm() {
        $this->reset(['product_id', 'reference_no', 'description']);
        $this->report_date = now()->toDateString(); $this->source = 'incoming'; $this->quantity = '1';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $ncrs = []; $products = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$ncrs, &$products) {
                $ncrs = QmNcr::with('product')->orderByDesc('report_date')->limit(100)->get()->toArray();
                $products = InvProduct::where('status', 'active')->orderBy('name')->get(['id', 'name'])->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.qm.ncrs', compact('ncrs', 'products', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Uygunsuzluk Raporları (NCR)']);
    }
}
