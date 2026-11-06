<?php
namespace App\Services\Platform;

use App\Models\SysApprovalDecision;
use App\Models\SysApprovalRequest;
use App\Models\SysApprovalStep;
use App\Models\SysApprovalWorkflow;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class WorkflowService
{
    public function startRequest(string $entityType, string $entityId, string $label, float $amount, array $payload = []): ?SysApprovalRequest
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) return null;

        $existing = SysApprovalRequest::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'pending')
            ->first();
        if ($existing) {
            throw ValidationException::withMessages(['entity' => 'Bu kayıt için zaten bekleyen bir onay talebi var.']);
        }

        $workflow = SysApprovalWorkflow::where('entity_type', $entityType)
            ->where('is_active', true)
            ->first();
        if (!$workflow) return null;

        $steps = $this->applicableSteps($workflow, $amount);
        if ($steps->isEmpty()) return null;

        $first = $steps->first();
        $request = SysApprovalRequest::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'workflow_id' => $workflow->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_label' => $label,
            'payload' => $payload,
            'status' => 'pending',
            'current_step_order' => $first->order_no,
            'requested_by' => auth()->id(),
            'requested_at' => now(),
        ]);

        $this->notifyApprovers($request, $first);

        return $request->fresh();
    }

    public function approve(string $requestId, ?string $notes = null): SysApprovalRequest
    {
        $request = SysApprovalRequest::findOrFail($requestId);
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Bu talep zaten kapatılmış.']);
        }

        $user = auth()->user();
        if (!$this->canUserApprove($request, $user)) {
            throw ValidationException::withMessages(['auth' => 'Bu adımı onaylama yetkiniz yok.']);
        }

        $currentStep = $request->currentStep();
        if (!$currentStep) throw ValidationException::withMessages(['step' => 'Aktif adım bulunamadı.']);

        SysApprovalDecision::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $request->tenant_id,
            'request_id' => $request->id,
            'step_id' => $currentStep->id,
            'step_order' => $currentStep->order_no,
            'decision' => 'approve',
            'decided_by' => $user->id,
            'notes' => $notes,
            'decided_at' => now(),
        ]);

        $amount = (float) ($request->payload['amount'] ?? 0);
        $nextStep = $this->applicableSteps($request->workflow, $amount)
            ->firstWhere(fn($s) => $s->order_no > $request->current_step_order);

        if ($nextStep) {
            $request->update(['current_step_order' => $nextStep->order_no]);
            $this->notifyApprovers($request, $nextStep);
        } else {
            $request->update(['status' => 'approved', 'finished_at' => now()]);
            $this->onFinalize($request);
        }

        return $request->fresh();
    }

    public function reject(string $requestId, ?string $notes = null): SysApprovalRequest
    {
        $request = SysApprovalRequest::findOrFail($requestId);
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Bu talep zaten kapatılmış.']);
        }

        $user = auth()->user();
        if (!$this->canUserApprove($request, $user)) {
            throw ValidationException::withMessages(['auth' => 'Bu adımı onaylama yetkiniz yok.']);
        }

        $currentStep = $request->currentStep();
        if (!$currentStep) throw ValidationException::withMessages(['step' => 'Aktif adım bulunamadı.']);

        SysApprovalDecision::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $request->tenant_id,
            'request_id' => $request->id,
            'step_id' => $currentStep->id,
            'step_order' => $currentStep->order_no,
            'decision' => 'reject',
            'decided_by' => $user->id,
            'notes' => $notes,
            'decided_at' => now(),
        ]);

        $request->update(['status' => 'rejected', 'finished_at' => now()]);

        app(NotificationService::class)->notify(
            'danger',
            'Onay Reddedildi',
            "{$request->entity_label} — {$user->name} tarafından reddedildi. " . ($notes ? "Not: {$notes}" : ''),
            null,
            $request->requested_by
        );

        return $request->fresh();
    }

    public function pendingForUser(User $user): Collection
    {
        $requests = SysApprovalRequest::with(['workflow.steps', 'requester'])
            ->where('status', 'pending')
            ->orderBy('requested_at')
            ->get();

        return $requests->filter(fn($req) => $this->canUserApprove($req, $user))->values();
    }

    public function canUserApprove(SysApprovalRequest $request, User $user): bool
    {
        if ($user->isSuperadmin()) return true;
        $step = $request->currentStep();
        if (!$step) return false;
        return $user->role === $step->approver_role;
    }

    private function applicableSteps(SysApprovalWorkflow $workflow, float $amount): Collection
    {
        return $workflow->steps()
            ->where(function ($q) use ($amount) {
                $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount);
            })
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            })
            ->orderBy('order_no')
            ->get();
    }

    private function notifyApprovers(SysApprovalRequest $request, SysApprovalStep $step): void
    {
        $users = User::where('tenant_id', $request->tenant_id)
            ->where(function ($q) use ($step) {
                $q->where('role', $step->approver_role);
                if ($step->approver_role !== 'superadmin') {
                    $q->orWhere('role', 'superadmin');
                }
            })
            ->get();

        $notifier = app(NotificationService::class);
        foreach ($users as $u) {
            $notifier->notify(
                'warning',
                "Onayınız Bekleniyor: {$step->name}",
                "{$request->entity_label} — talep " . number_format((float) ($request->payload['amount'] ?? 0), 2) . " ₺",
                '/approvals/inbox',
                $u->id
            );
        }
    }

    private function onFinalize(SysApprovalRequest $request): void
    {
        match ($request->entity_type) {
            'expense' => $this->onExpenseFinalized($request),
            'purchase_order' => $this->onPoFinalized($request),
            'ap_invoice' => $this->onApFinalized($request),
            default => null,
        };
    }

    private function onExpenseFinalized(SysApprovalRequest $request): void
    {
        $claim = \App\Models\HrExpenseClaim::find($request->entity_id);
        if ($claim && $claim->status === 'submitted') {
            $claim->update(['status' => 'approved']);
            app(NotificationService::class)->notify(
                'success',
                'Masraf Onaylandı',
                "{$claim->description} tutarlı masraf onay sürecinden geçti.",
                '/hrm/expenses',
                $claim->employee?->user_id
            );
        }
    }

    private function onPoFinalized(SysApprovalRequest $request): void
    {
        try {
            app(\App\Services\Prc\PurchaseOrderService::class)->markApproved($request->entity_id);
        } catch (\Throwable) {
            // finalize sırasında hata olursa sessizce geç (log'a düşsün)
        }
    }

    private function onApFinalized(SysApprovalRequest $request): void
    {
        try {
            $invoice = \App\Models\FinApInvoice::find($request->entity_id);
            if ($invoice && $invoice->status === 'draft') {
                app(\App\Services\Fin\ApInvoiceService::class)->doPost($invoice);
            }
        } catch (\Throwable) {
            // finalize sırasında hata olursa sessizce geç
        }
    }
}
