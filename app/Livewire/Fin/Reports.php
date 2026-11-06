<?php

namespace App\Livewire\Fin;

use App\Models\Tenant;
use App\Services\Fin\FinReportService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class Reports extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    #[Url(as: 'tenant')]
    public string $selectedTenantId = '';

    #[Url(as: 'tab')]
    public string $tab = 'tb';

    #[Url(as: 'asOf')]
    public string $asOfDate = '';

    #[Url(as: 'from')]
    public string $fromDate = '';

    #[Url(as: 'to')]
    public string $toDate = '';

    public function mount()
    {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) abort(403);

        if ($actor->isSuperadmin()) {
            if (!$this->selectedTenantId) {
                $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->first(), superadmin: true);
                $this->selectedTenantId = $first?->id ?? '';
            }
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }

        if (!$this->asOfDate) $this->asOfDate = now()->toDateString();
        if (!$this->fromDate) $this->fromDate = now()->startOfMonth()->toDateString();
        if (!$this->toDate) $this->toDate = now()->endOfMonth()->toDateString();
    }

    public function setTab(string $tab)
    {
        $this->tab = $tab;
    }

    private function effectiveTenantId(): ?string
    {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();
        $tid = $this->effectiveTenantId();

        $data = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$data) {
                $svc = app(FinReportService::class);
                if ($this->tab === 'tb') {
                    $data['tb'] = $svc->trialBalance($this->asOfDate);
                } elseif ($this->tab === 'bs') {
                    $data['bs'] = $svc->balanceSheet($this->asOfDate);
                } elseif ($this->tab === 'is') {
                    $data['is'] = $svc->incomeStatement($this->fromDate, $this->toDate);
                } elseif ($this->tab === 'vat') {
                    $data['vat'] = $svc->vatReturn($this->fromDate, $this->toDate);
                }
            });
        }

        $tenants = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), superadmin: true);
        }

        return view('livewire.fin.reports', array_merge([
            'title'   => 'Finansal Raporlar',
            'isSuper' => $isSuper,
            'tenants' => $tenants,
        ], $data));
    }
}
