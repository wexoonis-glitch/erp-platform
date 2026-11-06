<?php
namespace App\Livewire\Crm;
use App\Models\CrmOpportunity;
use App\Models\Tenant;
use App\Services\Crm\OpportunityService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Opportunities extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    
    public string $name = '', $amount = '0', $expected_close_date = '', $notes = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->expected_close_date = now()->addDays(30)->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate() { $this->showForm = true; }
    public function cancel() { $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate(['name' => 'required', 'amount' => 'required|numeric|min:0']);
        try {
            TenantContext::runAs($tid, fn() => app(OpportunityService::class)->create([
                'name' => $this->name, 'amount' => (float)$this->amount, 'expected_close_date' => $this->expected_close_date, 'notes' => $this->notes
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function updateStage(string $id, string $stage) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => app(OpportunityService::class)->updateStage($id, $stage));
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $opportunities = [];
        if ($tid) { TenantContext::runAs($tid, function () use (&$opportunities) { $opportunities = CrmOpportunity::orderByDesc('created_at')->get()->toArray(); }); }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.crm.opportunities', compact('opportunities', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Satış Fırsatları']);
    }
}
