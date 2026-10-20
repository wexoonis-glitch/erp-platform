<?php

namespace App\Services\Platform;

use App\Models\CustomField;
use App\Models\CustomObject;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CustomFieldService
{
    private const BUILTIN_ENTITIES = [
        'tenant',
        'user',
        'ap_invoice',
        'ar_invoice',
        'sales_order',
        'purchase_order',
        'inventory_item',
    ];

    private const ALLOWED_TYPES = ['string', 'number', 'date', 'bool', 'enum', 'json'];

    /**
     * @param string $tenantId  Hangi tenant'a ait olduğu (superadmin veya tenant admin tarafından çözülür)
     */
    public function create(array $data, string $tenantId): CustomField
    {
        $this->validateDataType($data);
        $this->validateEntity($data['entity'], $tenantId);

        // Tenant içinde kod benzersiz mi? (RLS aktif, tenant bağlamında çalışıyor)
        $exists = TenantContext::runAs($tenantId, function () use ($data) {
            return CustomField::where('entity', $data['entity'])
                ->where('code', $data['code'])
                ->exists();
        });

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Bu entity için kod zaten kullanımda.',
            ]);
        }

        return TenantContext::runAs($tenantId, function () use ($data, $tenantId) {
            return CustomField::create([
                'id'        => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'entity'    => $data['entity'],
                'code'      => $data['code'],
                'label'     => $data['label'],
                'data_type' => $data['data_type'],
                'config'    => $data['config'] ?? [],
            ]);
        });
    }

    public function listAll(?string $entity = null, ?string $tenantId = null): array
    {
        $q = CustomField::query();
        if ($entity !== null) {
            $q->where('entity', $entity);
        }
        if ($tenantId !== null) {
            $q->where('tenant_id', $tenantId);
        }
        return $q->orderBy('entity')->orderBy('code')->get()->toArray();
    }

    public function show(string $id): CustomField
    {
        return CustomField::findOrFail($id);
    }

    public function update(string $id, array $data): CustomField
    {
        $field = CustomField::findOrFail($id);

        $forbidden = ['entity', 'code', 'data_type'];
        foreach ($forbidden as $f) {
            if (array_key_exists($f, $data)) {
                throw ValidationException::withMessages([
                    $f => "Bu alan güncellenemez: {$f}",
                ]);
            }
        }

        if (array_key_exists('config', $data) && $field->data_type === 'enum') {
            $this->validateEnumOptions($data['config']);
        }

        $field->fill(array_intersect_key($data, array_flip(['label', 'config'])));
        $field->save();

        return $field->fresh();
    }

    public function delete(string $id): void
    {
        CustomField::findOrFail($id)->delete();
    }

    public function schemaFor(string $entity, ?string $tenantId = null): array
    {
        $this->validateEntity($entity, $tenantId);

        $builtin = $this->builtinSchema($entity);

        $custom = [];
        if ($tenantId !== null) {
            $custom = TenantContext::runAs($tenantId, function () use ($entity) {
                return CustomField::where('entity', $entity)
                    ->orderBy('code')
                    ->get(['code', 'label', 'data_type', 'config'])
                    ->map(fn($f) => [
                        'code'      => $f->code,
                        'label'     => $f->label,
                        'data_type' => $f->data_type,
                        'config'    => $f->config ?? [],
                        'custom'    => true,
                    ])
                    ->all();
            });
        }

        return [
            'entity' => $entity,
            'fields' => array_merge($builtin, $custom),
        ];
    }

    private function builtinSchema(string $entity): array
    {
        $common = [
            'tenant' => [
                ['code' => 'code',   'label' => 'Kod',   'data_type' => 'string'],
                ['code' => 'name',   'label' => 'Ad',    'data_type' => 'string'],
                ['code' => 'tier',   'label' => 'Tier',  'data_type' => 'string'],
                ['code' => 'status', 'label' => 'Durum', 'data_type' => 'string'],
            ],
            'user' => [
                ['code' => 'name',  'label' => 'Ad',   'data_type' => 'string'],
                ['code' => 'email', 'label' => 'Email','data_type' => 'string'],
                ['code' => 'role',  'label' => 'Rol',  'data_type' => 'string'],
            ],
            'ap_invoice' => [
                ['code' => 'invoice_no', 'label' => 'Fatura No', 'data_type' => 'string'],
                ['code' => 'amount',     'label' => 'Tutar',     'data_type' => 'number'],
                ['code' => 'currency',   'label' => 'Döviz',     'data_type' => 'string'],
            ],
        ];

        return $common[$entity] ?? [];
    }

    private function validateDataType(array $data): void
    {
        if (!in_array($data['data_type'], self::ALLOWED_TYPES, true)) {
            throw ValidationException::withMessages([
                'data_type' => 'Geçersiz tip. Kabul: ' . implode(', ', self::ALLOWED_TYPES),
            ]);
        }

        if ($data['data_type'] === 'enum') {
            $this->validateEnumOptions($data['config'] ?? []);
        }
    }

    private function validateEnumOptions(array $config): void
    {
        $options = $config['options'] ?? null;
        if (!is_array($options) || count($options) === 0) {
            throw ValidationException::withMessages([
                'config.options' => 'enum tipi için config.options en az 1 seçenek içermelidir.',
            ]);
        }
        foreach ($options as $o) {
            if (!is_string($o) || $o === '') {
                throw ValidationException::withMessages([
                    'config.options' => 'Seçenekler boş olamaz ve string olmalı.',
                ]);
            }
        }
    }

    private function validateEntity(string $entity, ?string $tenantId): void
    {
        if (in_array($entity, self::BUILTIN_ENTITIES, true)) {
            return;
        }

        if ($tenantId !== null) {
            $exists = TenantContext::runAs($tenantId, function () use ($entity) {
                return CustomObject::where('code', $entity)->exists();
            });
            if ($exists) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'entity' => "Bilinmeyen entity: {$entity}",
        ]);
    }
}
