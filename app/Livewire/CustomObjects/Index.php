<?php

namespace App\Livewire\CustomObjects;

use App\Models\CustomObject;
use App\Models\CustomObjectData;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Index extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public bool $showCreate = false;

    #[Validate('required|string|max:50|regex:/^[a-z][a-z0-9_]{0,49}$/')]
    public string $code = '';

    #[Validate('required|string|max:255')]
    public string $label = '';

    public string $tenant_id = '';

    public array $schemaFields = [];

    // Edit state
    public ?string $editingId = null;
    public string $editLabel = '';
    public array $editSchemaFields = [];

    public function mount()
    {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }
        if (!$actor->isSuperadmin()) {
            $this->tenant_id = $actor->tenant_id ?? '';
        }
        $this->schemaFields = [$this->emptyField()];
    }

    private function emptyField(): array
    {
        return ['code' => '', 'label' => '', 'data_type' => 'string', 'required' => false, 'options' => ''];
    }

    public function addField()
    {
        $this->schemaFields[] = $this->emptyField();
    }

    public function removeField(int $index)
    {
        unset($this->schemaFields[$index]);
        $this->schemaFields = array_values($this->schemaFields);
    }

    public function addEditField()
    {
        $this->editSchemaFields[] = $this->emptyField();
    }

    public function removeEditField(int $index)
    {
        unset($this->editSchemaFields[$index]);
        $this->editSchemaFields = array_values($this->editSchemaFields);
    }

    public function create()
    {
        $this->validate();

        $actor = Auth::user();
        $actorIsSuper = $actor->isSuperadmin();
        $targetTenantId = $actorIsSuper ? $this->tenant_id : $actor->tenant_id;

        if (empty($targetTenantId)) {
            $this->addError('tenant_id', 'Tenant seçmelisiniz.');
            return;
        }

        $schema = $this->buildSchema($this->schemaFields);
        if ($schema === null) return;

        $exists = TenantContext::runAs($targetTenantId, function () {
            return CustomObject::where('code', $this->code)->exists();
        });
        if ($exists) {
            $this->addError('code', 'Bu kod zaten kullanımda.');
            return;
        }

        try {
            TenantContext::runAs($targetTenantId, function () use ($targetTenantId, $schema) {
                CustomObject::create([
                    'tenant_id' => $targetTenantId,
                    'code'      => $this->code,
                    'label'     => $this->label,
                    'schema'    => $schema,
                ]);
            });
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
            return;
        }

        $this->reset(['code', 'label', 'showCreate', 'schemaFields']);
        $this->schemaFields = [$this->emptyField()];
        if ($actorIsSuper) {
            $this->reset('tenant_id');
        } else {
            $this->tenant_id = $actor->tenant_id;
        }
    }

    public function openEdit(string $id)
    {
        $obj = CustomObject::findOrFail($id);
        $this->editingId = $obj->id;
        $this->editLabel = $obj->label;
        $this->editSchemaFields = [];
        foreach ($obj->schema['fields'] ?? [] as $f) {
            $this->editSchemaFields[] = [
                'code'      => $f['code'] ?? '',
                'label'     => $f['label'] ?? '',
                'data_type' => $f['data_type'] ?? 'string',
                'required'  => (bool) ($f['required'] ?? false),
                'options'   => implode(',', $f['options'] ?? []),
            ];
        }
        if (empty($this->editSchemaFields)) {
            $this->editSchemaFields = [$this->emptyField()];
        }
        $this->resetErrorBag();
    }

    public function cancelEdit()
    {
        $this->reset(['editingId', 'editLabel', 'editSchemaFields']);
        $this->resetErrorBag();
    }

    public function saveEdit()
    {
        $this->validate(['editLabel' => 'required|string|max:255']);

        $schema = $this->buildSchema($this->editSchemaFields);
        if ($schema === null) return;

        try {
            $obj = CustomObject::findOrFail($this->editingId);
            $obj->label = $this->editLabel;
            $obj->schema = $schema;
            $obj->save();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
            return;
        }

        $this->cancelEdit();
    }

    public function delete(string $id)
    {
        $obj = CustomObject::findOrFail($id);
        TenantContext::runAs($obj->tenant_id, function () use ($obj) {
            CustomObjectData::where('object_id', $obj->id)->delete();
            $obj->delete();
        });
    }

    private function buildSchema(array $fields): ?array
    {
        $clean = [];
        $codes = [];

        foreach ($fields as $i => $f) {
            $code = trim($f['code'] ?? '');
            $label = trim($f['label'] ?? '');
            $type = $f['data_type'] ?? 'string';

            if ($code === '' && $label === '') continue;

            if (!preg_match('/^[a-z][a-z0-9_]{0,49}$/', $code)) {
                $this->addError("schemaFields.{$i}.code", 'Kod küçük harfle başlamalı; sadece harf/rakam/alt çizgi.');
                return null;
            }
            if ($label === '') {
                $this->addError("schemaFields.{$i}.label", 'Label zorunlu.');
                return null;
            }
            if (in_array($code, $codes, true)) {
                $this->addError("schemaFields.{$i}.code", "Tekrarlanan kod: {$code}");
                return null;
            }
            if (!in_array($type, ['string', 'number', 'date', 'bool', 'enum', 'json'], true)) {
                $this->addError("schemaFields.{$i}.data_type", 'Geçersiz tip.');
                return null;
            }

            $codes[] = $code;
            $entry = [
                'code'      => $code,
                'label'     => $label,
                'data_type' => $type,
                'required'  => (bool) ($f['required'] ?? false),
            ];

            if ($type === 'enum') {
                $opts = array_values(array_filter(array_map('trim', explode(',', $f['options'] ?? ''))));
                if (empty($opts)) {
                    $this->addError("schemaFields.{$i}.options", 'enum için seçenek zorunlu.');
                    return null;
                }
                $entry['options'] = $opts;
            }

            $clean[] = $entry;
        }

        if (empty($clean)) {
            $this->addError('schemaFields', 'En az bir alan tanımlanmalı.');
            return null;
        }

        return ['fields' => $clean];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();

        $objects = CustomObject::orderBy('code')
            ->get(['id', 'tenant_id', 'code', 'label', 'schema'])
            ->map(fn($o) => [
                'id'          => $o->id,
                'tenant_id'   => $o->tenant_id,
                'code'        => $o->code,
                'label'       => $o->label,
                'field_count' => count($o->schema['fields'] ?? []),
            ])
            ->toArray();

        $tenants = [];
        $tenantLookup = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::orderBy('code')->get(['id', 'code', 'name'])->toArray(),
                superadmin: true
            );
            foreach ($tenants as $t) {
                $tenantLookup[$t['id']] = ['code' => $t['code'], 'name' => $t['name']];
            }
        }

        return view('livewire.custom-objects.index', [
            'title'        => 'Custom Objects',
            'isSuper'      => $isSuper,
            'objects'      => $objects,
            'tenants'      => $tenants,
            'tenantLookup' => $tenantLookup,
        ]);
    }
}
