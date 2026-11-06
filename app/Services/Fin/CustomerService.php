<?php
namespace App\Services\Fin;

use App\Models\FinCustomer;
use App\Services\Platform\AuditService;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CustomerService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(array $data): FinCustomer
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        if (FinCustomer::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Kod zaten kullanımda.']);
        }

        $customer = FinCustomer::create([
            'id'         => (string) Str::uuid(),
            'tenant_id'  => $tenantId,
            'code'       => $data['code'],
            'name'       => $data['name'],
            'tax_no'     => $data['tax_no'] ?? null,
            'tax_office' => $data['tax_office'] ?? null,
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'address'    => $data['address'] ?? null,
            'currency'   => $data['currency'] ?? 'TRY',
            'status'     => $data['status'] ?? 'active',
            'price_list_id' => $data['price_list_id'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? [],
        ]);

        $this->audit->record([
            'tenant_id' => $tenantId, 'entity' => 'fin_customer', 'entity_id' => $customer->id,
            'action' => 'create', 'after_state' => $customer->toArray(),
        ]);

        return $customer;
    }

    public function update(string $id, array $data): FinCustomer
    {
        $customer = FinCustomer::findOrFail($id);

        if (isset($data['code']) && $data['code'] !== $customer->code) {
            if (FinCustomer::where('code', $data['code'])->where('id', '!=', $id)->exists()) {
                throw ValidationException::withMessages(['code' => 'Kod zaten kullanımda.']);
            }
        }

        $before = $customer->toArray();
        $customer->fill(array_intersect_key($data, array_flip([
            'code', 'name', 'tax_no', 'tax_office', 'email', 'phone',
            'address', 'currency', 'status', 'price_list_id', 'custom_fields',
        ])));
        $customer->save();

        $this->audit->record([
            'tenant_id' => $customer->tenant_id, 'entity' => 'fin_customer', 'entity_id' => $customer->id,
            'action' => 'update', 'before_state' => $before, 'after_state' => $customer->fresh()->toArray(),
        ]);

        return $customer->fresh();
    }

    public function delete(string $id): void
    {
        $customer = FinCustomer::findOrFail($id);
        if ($customer->invoices()->exists()) {
            throw ValidationException::withMessages(['customer' => 'Müşterinin faturaları var, silinemez.']);
        }
        $before = $customer->toArray();
        $customer->delete();
        $this->audit->record([
            'tenant_id' => $customer->tenant_id, 'entity' => 'fin_customer', 'entity_id' => $customer->id,
            'action' => 'delete', 'before_state' => $before,
        ]);
    }
}
