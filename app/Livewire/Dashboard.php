<?php
namespace App\Livewire;

use App\Services\Platform\DashboardService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    #[Layout('layouts.app')]
    public function render()
    {
        $user = Auth::user();
        $isSuper = $user->isSuperadmin();
        
        $kpis = [];
        $chart = ['labels' => [], 'values' => []];
        $recent = [];

        // Verileri çek (sadece tenant varsa)
        $tenantId = $isSuper ? TenantContext::id() : $user->tenant_id;
        
        if ($tenantId && $tenantId !== self::NULL_TENANT) {
            TenantContext::runAs($tenantId, function () use (&$kpis, &$chart, &$recent) {
                $svc = app(DashboardService::class);
                $kpis = $svc->getKpis();
                $chart = $svc->getSalesChartData();
                $recent = $svc->getRecentActivity();
            });
        }

        // Grafik verisini JS'e gönder
        $this->dispatch('dashboard-chart-updated', data: $chart);

        return view('livewire.dashboard', [
            'title' => 'Yönetim Paneli',
            'isSuper' => $isSuper,
            'kpis' => $kpis,
            'chart' => $chart,
            'recent' => $recent,
        ]);
    }
}
