<?php
namespace App\Livewire\Mfg;
use App\Services\Mfg\MrpService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Tenant;

class Mrp extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public array $requirements = [];
    public bool $calculated = false;

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

    public function calculate() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        
        TenantContext::runAs($tid, function () {
            $this->requirements = app(MrpService::class)->calculateRequirements();
            $this->calculated = true;
        });
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        
        return view('livewire.mfg.mrp', compact('isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Malzeme İhtiyaç Planlaması (MRP)']);
    }
}
