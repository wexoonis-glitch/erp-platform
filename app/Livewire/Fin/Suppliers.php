<?php

namespace App\Livewire\Fin;

use App\Models\FinSupplier;
use App\Models\Tenant;
use App\Services\Fin\SupplierService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Suppliers extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';

    public bool $showForm = false;
    public ?string $editingId = null;

    public string $code = '';
    public string $name = '';
    public string $tax_no = '';
    public string $tax_office = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $currency = 'TRY';
    public string $status = 'active';

    public function mount()
    {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) abort(403);

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
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(string $id)
    {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        TenantContext::runAs($tid, function () use ($id) {
            $s = FinSupplier::findOrFail($id);
            $this->editingId = $s->id;
            $this->code = $s->code;
            $this->name = $s->name;
            $this->tax_no = $s->tax_no ?? '';
            $this->tax_office = $s->tax_office ?? '';
            $this->email = $s->email ?? '';
            $this->phone = $s->phone ?? '';
            $this->address = $s->address ?? '';
            $this->currency = $s->currency;
            $this->status = $s->status;
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
        $tid = $this->effectiveTenantId();
        if (!$tid) { $this->addError('general', 'Tenant seçmelisiniz.'); return; }

        $data = [
            'code'       => $this->code,
            'name'       => $this->name,
            'tax_no'     => $this->tax_no ?: null,
            'tax_office' => $this->tax_office ?: null,
            'email'      => $this->email ?: null,
            'phone'      => $this->phone ?: null,
            'address'    => $this->address ?: null,
            'currency'   => $this->currency,
            'status'     => $this->status,
        ];

        try {
            TenantContext::runAs($tid, function () use ($data) {
                $svc = app(SupplierService::class);
                if ($this->editingId) $svc->update($this->editingId, $data);
                else $svc->create($data);
            });
            $this->cancel();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function delete(string $id)
    {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            TenantContext::runAs($tid, fn() => app(SupplierService::class)->delete($id));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'name', 'tax_no', 'tax_office', 'email', 'phone', 'address']);
        $this->currency = 'TRY';
        $this->status = 'active';
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();
        $tid = $this->effectiveTenantId();

        $suppliers = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$suppliers) {
                $suppliers = FinSupplier::orderBy('code')->get()->toArray();
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

        return view('livewire.fin.suppliers', [
            'title'      => 'Tedarikçiler',
            'suppliers'  => $suppliers,
            'isSuper'    => $isSuper,
            'tenants'    => $tenants,
        ]);
    }
}
