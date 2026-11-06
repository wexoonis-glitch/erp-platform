<?php
namespace App\Livewire\Fin;
use App\Models\FinDepreciationSchedule;
use App\Models\Tenant;
use App\Services\Fin\DepreciationService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Depreciation extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public string $year = '', $month = '';
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
        $this->month = now()->format('m');
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function run() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate(['year' => 'required', 'month' => 'required']);
        try {
            $count = 0;
            TenantContext::runAs($tid, function () use (&$count) {
                $count = app(DepreciationService::class)->runPeriod($this->year, $this->month);
            });
            $this->message = "{$count} varlık için amortisman post edildi.";
        } catch (ValidationException $e) {
            $this->message = $e->errors()['period'][0] ?? 'Hata oluştu.';
        }
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $pending = []; $posted = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$pending, &$posted) {
                $pending = FinDepreciationSchedule::where('status', 'pending')
                    ->with('asset')->orderBy('period_year')->orderBy('period_month')
                    ->limit(50)->get()->toArray();
                $posted = FinDepreciationSchedule::where('status', 'posted')
                    ->with('asset')->orderByDesc('period_year')->orderByDesc('period_month')
                    ->limit(30)->get()->toArray();
            });
        }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.depreciation', compact('pending', 'posted', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Amortisman Koşusu']);
    }
}
