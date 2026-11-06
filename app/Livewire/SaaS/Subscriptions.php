<?php
namespace App\Livewire\SaaS;
use App\Models\SaasSubscription;
use App\Models\SaasPlan;
use App\Models\Tenant;
use App\Support\TenantContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Str;

class Subscriptions extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public bool $showForm = false;
    public string $tenant_id = '', $plan_id = '', $start_date = '', $end_date = '';

    public function mount() {
        $this->start_date = now()->toDateString();
        $this->end_date = now()->addYear()->toDateString();
    }

    public function openCreate() { $this->showForm = true; }
    public function cancel() { $this->showForm = false; }

    public function save() {
        $this->validate(['tenant_id' => 'required', 'plan_id' => 'required', 'start_date' => 'required', 'end_date' => 'required']);
        SaasSubscription::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $this->tenant_id, 'plan_id' => $this->plan_id,
            'start_date' => $this->start_date, 'end_date' => $this->end_date, 'status' => 'active',
        ]);
        $this->cancel();
    }

    #[Layout('layouts.app')]
    public function render() {
        $subs = SaasSubscription::with(['tenant', 'plan'])->orderByDesc('created_at')->get()->toArray();
        $plans = SaasPlan::where('status', 'active')->get(['id', 'name', 'price'])->toArray();
        $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::orderBy('name')->get(['id', 'name'])->toArray(), true);
        return view('livewire.saas.subscriptions', compact('subs', 'plans', 'tenants'))->layout('layouts.app', ['title' => 'Tenant Abonelikleri']);
    }
}
