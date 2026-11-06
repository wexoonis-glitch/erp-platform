<?php
namespace App\Livewire\Approvals;

use App\Models\SysApprovalWorkflow;
use App\Models\SysApprovalStep;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Workflows extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;

    public string $code = '', $name = '', $entity_type = 'expense', $description = '';
    public array $steps = [];

    public array $entityTypes = [
        'expense' => 'Masraf',
        'purchase_order' => 'Satın Alma Siparişi',
        'ap_invoice' => 'Tedarikçi Faturası',
        'ar_invoice' => 'Müşteri Faturası',
    ];

    public array $roles = [
        'user' => 'Kullanıcı',
        'manager' => 'Müdür',
        'admin' => 'Yönetici',
        'superadmin' => 'Süper Yönetici',
    ];

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

    public function openCreate() {
        $this->resetForm();
        $this->steps = [$this->emptyStep(1)];
        $this->showForm = true;
    }
    public function cancel() { $this->resetForm(); $this->showForm = false; $this->resetErrorBag(); }

    public function addStep() {
        $this->steps[] = $this->emptyStep(count($this->steps) + 1);
    }

    public function removeStep(int $i) {
        unset($this->steps[$i]);
        $this->steps = array_values($this->steps);
        foreach ($this->steps as $idx => &$s) { $s['order_no'] = $idx + 1; }
    }

    private function emptyStep(int $order): array {
        return ['order_no' => $order, 'name' => '', 'approver_role' => 'admin', 'min_amount' => '', 'max_amount' => ''];
    }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        $this->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'entity_type' => 'required|in:expense,purchase_order,ap_invoice,ar_invoice',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.approver_role' => 'required|in:user,manager,admin,superadmin',
        ]);

        try {
            TenantContext::runAs($tid, function () use ($tid) {
                if (SysApprovalWorkflow::where('code', $this->code)->exists()) {
                    throw ValidationException::withMessages(['code' => 'Bu kod zaten kullanımda.']);
                }

                $wf = SysApprovalWorkflow::create([
                    'id' => (string) Str::uuid(),
                    'tenant_id' => $tid,
                    'code' => $this->code,
                    'name' => $this->name,
                    'entity_type' => $this->entity_type,
                    'description' => $this->description ?: null,
                    'is_active' => true,
                ]);

                foreach ($this->steps as $idx => $step) {
                    SysApprovalStep::create([
                        'id' => (string) Str::uuid(),
                        'tenant_id' => $tid,
                        'workflow_id' => $wf->id,
                        'order_no' => $idx + 1,
                        'name' => $step['name'],
                        'approver_role' => $step['approver_role'],
                        'min_amount' => $step['min_amount'] !== '' ? (float) $step['min_amount'] : null,
                        'max_amount' => $step['max_amount'] !== '' ? (float) $step['max_amount'] : null,
                    ]);
                }
            });
            $this->cancel();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => SysApprovalWorkflow::findOrFail($id)->delete());
    }

    public function toggleActive(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () use ($id) {
            $wf = SysApprovalWorkflow::findOrFail($id);
            $wf->update(['is_active' => !$wf->is_active]);
        });
    }

    private function resetForm() {
        $this->reset(['code', 'name', 'description', 'steps']);
        $this->entity_type = 'expense';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $workflows = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$workflows) {
                $workflows = SysApprovalWorkflow::with(['steps'])->orderBy('entity_type')->orderBy('code')->get()->map(fn($w) => [
                    'id' => $w->id,
                    'code' => $w->code,
                    'name' => $w->name,
                    'entity_type' => $w->entity_type,
                    'description' => $w->description,
                    'is_active' => $w->is_active,
                    'steps' => $w->steps->map(fn($s) => [
                        'order_no' => $s->order_no,
                        'name' => $s->name,
                        'approver_role' => $s->approver_role,
                        'min_amount' => $s->min_amount,
                        'max_amount' => $s->max_amount,
                    ])->toArray(),
                ])->toArray();
            });
        }
        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.approvals.workflows', compact('workflows', 'tenants', 'isSuper'))->layout('layouts.app', ['title' => 'Onay Akışları']);
    }
}
