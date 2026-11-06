<?php
namespace App\Livewire\Fin;

use App\Models\CustomField;
use App\Models\FinCustomer;
use App\Models\InvPriceList;
use App\Models\Tenant;
use App\Services\Fin\CustomerService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Customers extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';
    public bool $showForm = false;
    public ?string $editingId = null;

    public string $code = '', $name = '', $tax_no = '', $tax_office = '', $email = '', $phone = '', $address = '', $currency = 'TRY', $status = 'active', $price_list_id = '';
    public array $customFields = [];

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

    public function openEdit(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () use ($id) {
            $c = FinCustomer::findOrFail($id);
            $this->editingId = $c->id;
            $this->code = $c->code; $this->name = $c->name;
            $this->tax_no = $c->tax_no ?? ''; $this->tax_office = $c->tax_office ?? '';
            $this->email = $c->email ?? ''; $this->phone = $c->phone ?? '';
            $this->address = $c->address ?? ''; $this->currency = $c->currency;
            $this->status = $c->status; $this->price_list_id = $c->price_list_id ?? '';
            $this->customFields = $c->custom_fields ?? [];
        });
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel() { $this->resetForm(); $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        $rules = [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ];
        $rules = array_merge($rules, CustomField::validateCustomFields('fin_customer'));
        $this->validate($rules);

        $data = [
            'code' => $this->code, 'name' => $this->name,
            'tax_no' => $this->tax_no ?: null, 'tax_office' => $this->tax_office ?: null,
            'email' => $this->email ?: null, 'phone' => $this->phone ?: null,
            'address' => $this->address ?: null, 'currency' => $this->currency,
            'status' => $this->status, 'price_list_id' => $this->price_list_id ?: null,
            'custom_fields' => $this->customFields,
        ];
        try {
            TenantContext::runAs($tid, function () use ($data) {
                $svc = app(CustomerService::class);
                if ($this->editingId) $svc->update($this->editingId, $data);
                else $svc->create($data);
            });
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            TenantContext::runAs($tid, fn() => app(CustomerService::class)->delete($id));
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    private function resetForm() {
        $this->reset(['editingId', 'code', 'name', 'tax_no', 'tax_office', 'email', 'phone', 'address', 'price_list_id', 'customFields']);
        $this->currency = 'TRY'; $this->status = 'active';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $customers = []; $priceLists = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$customers, &$priceLists) {
                $customers = FinCustomer::orderBy('code')->get()->toArray();
                $priceLists = InvPriceList::where('status', 'active')->orderBy('name')->get(['id', 'code', 'name'])->toArray();
            });
        }
        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.customers', compact('customers', 'priceLists', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Müşteriler']);
    }
}
