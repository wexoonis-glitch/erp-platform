<?php
namespace App\Livewire\Fin;
use App\Models\FinCostCenter;
use App\Models\FinBudget;
use App\Models\Tenant;
use App\Services\Fin\BudgetService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Budgets extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public string $year = '';
    public array $grid = []; // [cc_id => ['01' => '', ..., '12' => '']]
    public string $message = '';

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->year = now()->format('Y');
        $this->loadGrid();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function updatedSelectedTenantId() { $this->loadGrid(); }
    public function updatedYear() { $this->loadGrid(); }

    public function loadGrid() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        TenantContext::runAs($tid, function () {
            $centers = FinCostCenter::where('status', 'active')->orderBy('code')->get();
            $existing = FinBudget::where('year', $this->year)->get();

            $grid = [];
            foreach ($centers as $cc) {
                $grid[$cc->id] = [];
                for ($m = 1; $m <= 12; $m++) {
                    $mm = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                    $grid[$cc->id][$mm] = '';
                }
            }
            foreach ($existing as $e) {
                if (isset($grid[$e->cost_center_id])) {
                    $grid[$e->cost_center_id][$e->month] = (string) $e->planned_amount;
                }
            }
            $this->grid = $grid;
        });
        $this->message = '';
    }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () {
            $svc = app(BudgetService::class);
            foreach ($this->grid as $ccId => $months) {
                $svc->setBudget($ccId, $this->year, $months);
            }
        });
        $this->message = 'Bütçe kaydedildi.';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $centers = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$centers) {
                $centers = FinCostCenter::where('status', 'active')->orderBy('code')->get()->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.budgets', compact('centers', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Bütçe Planlama']);
    }
}
