<?php
namespace App\Livewire\Approvals;

use App\Models\SysApprovalRequest;
use App\Models\Tenant;
use App\Services\Platform\WorkflowService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Inbox extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public string $message = '';
    public string $messageType = 'success';
    public ?string $actioningId = null;
    public string $actionType = ''; // approve|reject
    public string $notes = '';

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

    public function openAction(string $id, string $type) {
        $this->actioningId = $id;
        $this->actionType = $type;
        $this->notes = '';
    }

    public function cancelAction() {
        $this->actioningId = null;
        $this->actionType = '';
        $this->notes = '';
    }

    public function confirmAction() {
        $tid = $this->effectiveTenantId();
        if (!$tid || !$this->actioningId) return;

        try {
            TenantContext::runAs($tid, function () {
                $svc = app(WorkflowService::class);
                if ($this->actionType === 'approve') {
                    $svc->approve($this->actioningId, $this->notes ?: null);
                } else {
                    $svc->reject($this->actioningId, $this->notes ?: null);
                }
            });
            $this->messageType = 'success';
            $this->message = $this->actionType === 'approve' ? 'Onaylandı.' : 'Reddedildi.';
            $this->cancelAction();
        } catch (ValidationException $e) {
            $this->messageType = 'error';
            $this->message = collect($e->errors())->flatten()->first() ?? 'İşlem başarısız.';
        }
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $pending = [];
        $history = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$pending, &$history) {
                $svc = app(WorkflowService::class);
                $user = Auth::user();

                $pending = $svc->pendingForUser($user)->map(fn($r) => [
                    'id' => $r->id,
                    'entity_type' => $r->entity_type,
                    'entity_label' => $r->entity_label,
                    'amount' => (float) ($r->payload['amount'] ?? 0),
                    'description' => $r->payload['description'] ?? null,
                    'current_step' => $r->currentStep()?->name,
                    'workflow_name' => $r->workflow?->name,
                    'requester' => $r->requester?->name ?? '—',
                    'requested_at' => $r->requested_at?->toDateTimeString() ?? '—',
                    'payload' => $r->payload,
                ])->toArray();

                $history = SysApprovalRequest::whereIn('status', ['approved', 'rejected'])
                    ->orderByDesc('finished_at')
                    ->limit(30)
                    ->get()
                    ->map(fn($r) => [
                        'id' => $r->id,
                        'entity_label' => $r->entity_label,
                        'status' => $r->status,
                        'finished_at' => $r->finished_at?->toDateTimeString() ?? '—',
                        'workflow_name' => $r->workflow?->name,
                    ])->toArray();
            });
        }

        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.approvals.inbox', compact('pending', 'history', 'tenants', 'isSuper'))->layout('layouts.app', ['title' => 'Onay Kutusu']);
    }
}
