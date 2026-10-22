<?php

namespace App\Livewire\Fin;

use App\Models\FinAccount;
use App\Models\Tenant;
use App\Services\Fin\ChartOfAccountService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ChartOfAccounts extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';

    public bool $showForm = false;
    public ?string $editingId = null;

    public string $code = '';
    public string $name = '';
    public string $type = 'asset';
    public string $parent_id = '';
    public bool $is_postable = true;
    public string $currency = 'TRY';
    public string $status = 'active';
    public string $description = '';

    public function mount()
    {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::where('status', 'active')->orderBy('code')->first(),
                superadmin: true
            );
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
    }

    private function effectiveTenantId(): ?string
    {
        if (TenantContext::isSuperadmin()) {
            return $this->selectedTenantId ?: null;
        }
        return Auth::user()->tenant_id;
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(string $id)
    {
        $tenantId = $this->effectiveTenantId();
        if (!$tenantId) { $this->addError('general', 'Tenant seçmelisiniz.'); return; }

        TenantContext::runAs($tenantId, function () use ($id) {
            $a = FinAccount::findOrFail($id);
            $this->editingId = $a->id;
            $this->code = $a->code;
            $this->name = $a->name;
            $this->type = $a->type;
            $this->parent_id = $a->parent_id ?? '';
            $this->is_postable = $a->is_postable;
            $this->currency = $a->currency;
            $this->status = $a->status;
            $this->description = $a->description ?? '';
        });

        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save()
    {
        $tenantId = $this->effectiveTenantId();
        if (!$tenantId) {
            $this->addError('tenant_id', 'Tenant seçmelisiniz.');
            return;
        }

        $data = [
            'code'        => $this->code,
            'name'        => $this->name,
            'type'        => $this->type,
            'parent_id'   => $this->parent_id ?: null,
            'is_postable' => $this->is_postable,
            'currency'    => $this->currency,
            'status'      => $this->status,
            'description' => $this->description ?: null,
        ];

        try {
            TenantContext::runAs($tenantId, function () use ($data) {
                $svc = app(ChartOfAccountService::class);
                if ($this->editingId) {
                    $svc->update($this->editingId, $data);
                } else {
                    $svc->create($data);
                }
            });
            $this->cancel();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) {
                $this->addError($f, $m[0]);
            }
        }
    }

    public function delete(string $id)
    {
        $tenantId = $this->effectiveTenantId();
        if (!$tenantId) { $this->addError('general', 'Tenant seçmelisiniz.'); return; }

        try {
            TenantContext::runAs($tenantId, function () use ($id) {
                app(ChartOfAccountService::class)->delete($id);
            });
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) {
                $this->addError($f, $m[0]);
            }
        }
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'name', 'parent_id', 'description']);
        $this->type = 'asset';
        $this->is_postable = true;
        $this->currency = 'TRY';
        $this->status = 'active';
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();
        $tenantId = $this->effectiveTenantId();

        $accounts = [];
        $tree = [];

        if ($tenantId) {
            TenantContext::runAs($tenantId, function () use (&$accounts, &$tree) {
                $accounts = FinAccount::orderBy('code')->get()->toArray();
                $tree = app(ChartOfAccountService::class)->treeForTenant();
            });
        }

        $tenants = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(),
                superadmin: true
            );
        }

        return view('livewire.fin.chart-of-accounts', [
            'title'   => 'Hesap Planı',
            'tree'    => $tree,
            'accounts' => $accounts,
            'isSuper' => $isSuper,
            'tenants' => $tenants,
        ]);
    }
}
