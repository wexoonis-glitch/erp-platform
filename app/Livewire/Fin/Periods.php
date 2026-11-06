<?php
namespace App\Livewire\Fin;
use App\Models\FinAccountingPeriod;
use App\Models\Tenant;
use App\Services\Fin\PeriodClosingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Periods extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';

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

    public function toggle(string $year, string $month) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => app(PeriodClosingService::class)->toggleStatus($year, $month));
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $periods = [];
        
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$periods) {
                // Son 12 ayı listele
                for ($i = 0; $i < 12; $i++) {
                    $date = now()->subMonths($i);
                    $y = $date->format('Y');
                    $m = $date->format('m');
                    
                    $period = FinAccountingPeriod::where('year', $y)->where('month', $m)->first();
                    
                    $periods[] = [
                        'year' => $y,
                        'month' => $m,
                        'label' => $date->translatedFormat('F Y'),
                        'status' => $period ? $period->status : 'open',
                        'closed_at' => $period && $period->closed_at ? $period->closed_at->format('d.m.Y H:i') : null,
                    ];
                }
            });
        }

        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.periods', compact('periods', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Dönem Kapanışı']);
    }
}
