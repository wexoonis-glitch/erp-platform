<?php
namespace App\Livewire\Crm;
use App\Models\CrmLead;
use App\Models\Tenant;
use App\Services\Crm\LeadService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Leads extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    
    public string $name = '', $company = '', $email = '', $phone = '', $source = 'website', $notes = '';

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

    public function openCreate() { $this->resetForm(); $this->showForm = true; }
    public function cancel() { $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate(['name' => 'required']);
        try {
            TenantContext::runAs($tid, fn() => app(LeadService::class)->create([
                'name' => $this->name, 'company' => $this->company, 'email' => $this->email,
                'phone' => $this->phone, 'source' => $this->source, 'notes' => $this->notes
            ]));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function updateStatus(string $id, string $status) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => app(LeadService::class)->updateStatus($id, $status));
    }

    private function resetForm() {
        $this->reset(['name', 'company', 'email', 'phone', 'notes']);
        $this->source = 'website';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $leads = [];
        if ($tid) { TenantContext::runAs($tid, function () use (&$leads) { $leads = CrmLead::orderByDesc('created_at')->get()->toArray(); }); }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.crm.leads', compact('leads', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Potansiyel Müşteriler']);
    }
}
