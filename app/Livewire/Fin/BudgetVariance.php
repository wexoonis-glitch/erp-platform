<?php
namespace App\Livewire\Fin;
use App\Models\Tenant;
use App\Services\Fin\BudgetService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class BudgetVariance extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public string $year = '';
    public array $report = [];

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->year = now()->format('Y');
        $this->load();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function updatedSelectedTenantId() { $this->load(); }
    public function updatedYear() { $this->load(); }

    public function load() {
        $tid = $this->effectiveTenantId();
        if (!$tid) { $this->report = []; return; }
        TenantContext::runAs($tid, function () {
            $this->report = app(BudgetService::class)->getVarianceReport($this->year);
        });
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.budget-variance', ['isSuper' => $isSuper, 'tenants' => $tenants])->layout('layouts.app', ['title' => 'Bütçe vs Gerçekleşen']);
    }
}
