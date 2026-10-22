<?php

namespace App\Services\Fin;

use App\Models\FinSupplier;
use App\Services\Platform\AuditService;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SupplierService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(array $data): FinSupplier
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) throw new \LogicException('Tenant yok.');

        if (FinSupplier::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Kod zaten kullanımda.']);
        }

        $supplier = FinSupplier::create([
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
        ]);

        $this->audit->record([
            'tenant_id'   => $tenantId,
            'entity'      => 'fin_supplier',
            'entity_id'   => $supplier->id,
            'action'      => 'create',
            'after_state' => $supplier->toArray(),
        ]);

        return $supplier;
    }

    public function update(string $id, array $data): FinSupplier
    {
        $supplier = FinSupplier::findOrFail($id);

        if (isset($data['code']) && $data['code'] !== $supplier->code) {
            if (FinSupplier::where('code', $data['code'])->where('id', '!=', $id)->exists()) {
                throw ValidationException::withMessages(['code' => 'Kod zaten kullanımda.']);
            }
        }

        $before = $supplier->toArray();
        $supplier->fill(array_intersect_key($data, array_flip([
            'code', 'name', 'tax_no', 'tax_office', 'email', 'phone', 'address', 'currency', 'status',
        ])));
        $supplier->save();

        $this->audit->record([
            'tenant_id'    => $supplier->tenant_id,
            'entity'       => 'fin_supplier',
            'entity_id'    => $supplier->id,
            'action'       => 'update',
            'before_state' => $before,
            'after_state'  => $supplier->fresh()->toArray(),
        ]);

        return $supplier->fresh();
    }

    public function delete(string $id): void
    {
        $supplier = FinSupplier::findOrFail($id);

        if ($supplier->invoices()->exists()) {
            throw ValidationException::withMessages([
                'supplier' => 'Tedarikçinin faturaları var, silinemez.',
            ]);
        }

        $before = $supplier->toArray();
        $supplier->delete();

        $this->audit->record([
            'tenant_id'    => $supplier->tenant_id,
            'entity'       => 'fin_supplier',
            'entity_id'    => $supplier->id,
            'action'       => 'delete',
            'before_state' => $before,
        ]);
    }
}
