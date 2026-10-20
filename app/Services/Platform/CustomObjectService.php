<?php

namespace App\Services\Platform;

use App\Models\CustomObject;
use App\Models\CustomObjectData;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CustomObjectService
{
    private const ALLOWED_TYPES = ['string', 'number', 'date', 'bool', 'enum', 'json'];

    // ---------- Objects ----------

    public function create(array $data, string $tenantId): CustomObject
    {
        $this->validateSchema($data['schema']);

        $exists = TenantContext::runAs($tenantId, function () use ($data) {
            return CustomObject::where('code', $data['code'])->exists();
        });

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Bu kod zaten kullanımda.',
            ]);
        }

        return TenantContext::runAs($tenantId, function () use ($data, $tenantId) {
            return CustomObject::create([
                'tenant_id' => $tenantId,
                'code'      => $data['code'],
                'label'     => $data['label'],
                'schema'    => $data['schema'],
            ]);
        });
    }

    public function listAll(?string $tenantId = null): array
    {
        $q = CustomObject::query();
        if ($tenantId !== null) {
            $q->where('tenant_id', $tenantId);
        }
        return $q->orderBy('code')->get()->toArray();
    }

    public function show(string $id): CustomObject
    {
        return CustomObject::findOrFail($id);
    }

    public function update(string $id, array $data): CustomObject
    {
        $obj = CustomObject::findOrFail($id);

        if (array_key_exists('code', $data)) {
            throw ValidationException::withMessages([
                'code' => 'Bu alan güncellenemez: code',
            ]);
        }

        if (array_key_exists('schema', $data)) {
            $this->validateSchema($data['schema']);
        }

        $obj->fill(array_intersect_key($data, array_flip(['label', 'schema'])));
        $obj->save();

        return $obj->fresh();
    }

    public function delete(string $id): void
    {
        $obj = CustomObject::findOrFail($id);

        TenantContext::runAs($obj->tenant_id, function () use ($obj) {
            CustomObjectData::where('object_id', $obj->id)->delete();
            $obj->delete();
        });
    }

    // ---------- Records ----------

    public function listRecords(string $objectId): array
    {
        $obj = $this->show($objectId);

        return TenantContext::runAs($obj->tenant_id, function () use ($objectId) {
            return CustomObjectData::where('object_id', $objectId)
                ->orderByDesc('created_at')
                ->get(['id', 'data', 'created_at', 'updated_at'])
                ->toArray();
        });
    }

    public function showRecord(string $objectId, string $recordId): array
    {
        $obj = $this->show($objectId);

        return TenantContext::runAs($obj->tenant_id, function () use ($objectId, $recordId) {
            return CustomObjectData::where('object_id', $objectId)
                ->findOrFail($recordId)
                ->only(['id', 'data', 'created_at', 'updated_at']);
        });
    }

    public function createRecord(string $objectId, array $data): array
    {
        $obj = $this->show($objectId);

        $this->validateRecord($obj, $data);

        return TenantContext::runAs($obj->tenant_id, function () use ($objectId, $data, $obj) {
            $record = CustomObjectData::create([
                'tenant_id'  => $obj->tenant_id,
                'object_id'  => $objectId,
                'data'       => $data,
                'created_at' => now(),
            ]);
            return $record->only(['id', 'data', 'created_at', 'updated_at']);
        });
    }

    public function updateRecord(string $objectId, string $recordId, array $data): array
    {
        $obj = $this->show($objectId);

        return TenantContext::runAs($obj->tenant_id, function () use ($objectId, $recordId, $data, $obj) {
            $record = CustomObjectData::where('object_id', $objectId)->findOrFail($recordId);
            $merged = array_merge($record->data ?? [], $data);

            $this->validateRecord($obj, $merged);

            $record->data = $merged;
            $record->updated_at = now();
            $record->save();

            return $record->fresh()->only(['id', 'data', 'created_at', 'updated_at']);
        });
    }

    public function deleteRecord(string $objectId, string $recordId): void
    {
        $obj = $this->show($objectId);

        TenantContext::runAs($obj->tenant_id, function () use ($objectId, $recordId) {
            CustomObjectData::where('object_id', $objectId)->findOrFail($recordId)->delete();
        });
    }

    // ---------- Validation ----------

    private function validateSchema(array $schema): void
    {
        $fields = $schema['fields'] ?? null;

        if (!is_array($fields) || count($fields) === 0) {
            throw ValidationException::withMessages([
                'schema.fields' => 'En az bir alan tanımlanmalıdır.',
            ]);
        }

        $codes = [];
        foreach ($fields as $i => $f) {
            if (empty($f['code']) || !is_string($f['code'])) {
                throw ValidationException::withMessages([
                    "schema.fields.$i.code" => 'Her alan için code zorunlu.',
                ]);
            }
            if (!preg_match('/^[a-z][a-z0-9_]{0,49}$/', $f['code'])) {
                throw ValidationException::withMessages([
                    "schema.fields.$i.code" => 'Kod küçük harfle başlamalı; sadece harf, rakam, alt çizgi.',
                ]);
            }
            if (in_array($f['code'], $codes, true)) {
                throw ValidationException::withMessages([
                    "schema.fields.$i.code" => "Tekrarlanan kod: {$f['code']}",
                ]);
            }
            $codes[] = $f['code'];

            if (empty($f['label'])) {
                throw ValidationException::withMessages([
                    "schema.fields.$i.label" => 'Her alan için label zorunlu.',
                ]);
            }

            if (empty($f['data_type']) || !in_array($f['data_type'], self::ALLOWED_TYPES, true)) {
                throw ValidationException::withMessages([
                    "schema.fields.$i.data_type" => 'Geçersiz tip. Kabul: ' . implode(', ', self::ALLOWED_TYPES),
                ]);
            }

            if ($f['data_type'] === 'enum') {
                $opts = $f['options'] ?? null;
                if (!is_array($opts) || count($opts) === 0) {
                    throw ValidationException::withMessages([
                        "schema.fields.$i.options" => 'enum için options zorunlu.',
                    ]);
                }
            }
        }
    }

    private function validateRecord(CustomObject $obj, array $data): void
    {
        $fields = $obj->schema['fields'] ?? [];
        $errors = [];

        foreach ($fields as $f) {
            $code     = $f['code'];
            $required = (bool) ($f['required'] ?? false);
            $value    = $data[$code] ?? null;

            if ($required && ($value === null || $value === '')) {
                $errors[$code] = "Zorunlu alan: {$code}";
                continue;
            }

            if ($value === null) {
                continue;
            }

            switch ($f['data_type']) {
                case 'string':
                    if (!is_string($value)) {
                        $errors[$code] = "string bekleniyor: {$code}";
                    }
                    break;
                case 'number':
                    if (!is_numeric($value)) {
                        $errors[$code] = "number bekleniyor: {$code}";
                    }
                    break;
                case 'date':
                    if (!is_string($value) || strtotime($value) === false) {
                        $errors[$code] = "date bekleniyor (YYYY-MM-DD): {$code}";
                    }
                    break;
                case 'bool':
                    if (!is_bool($value)) {
                        $errors[$code] = "bool bekleniyor: {$code}";
                    }
                    break;
                case 'enum':
                    $opts = $f['options'] ?? [];
                    if (!in_array($value, $opts, true)) {
                        $errors[$code] = "Geçersiz değer: {$code}. Kabul: " . implode('|', $opts);
                    }
                    break;
            }
        }

        // Bilinmeyen alan kontrolü
        $knownCodes = array_column($fields, 'code');
        foreach (array_keys($data) as $k) {
            if (!in_array($k, $knownCodes, true)) {
                $errors[$k] = "Bilinmeyen alan: {$k}";
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
