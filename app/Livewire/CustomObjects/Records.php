<?php

namespace App\Livewire\CustomObjects;

use App\Models\CustomObject;
use App\Models\CustomObjectData;
use App\Services\Platform\CustomObjectService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Records extends Component
{
    public string $objectId;
    public ?array $object = null;

    public bool $showForm = false;
    public ?string $editingRecordId = null;
    public array $recordData = [];

    public function mount(string $objectId)
    {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        $obj = CustomObject::findOrFail($objectId);

        if (!$actor->isSuperadmin() && $obj->tenant_id !== $actor->tenant_id) {
            abort(403, 'Başka bir tenant\'ın nesnesine erişim yok.');
        }

        $this->objectId = $obj->id;
        $this->object = [
            'id'     => $obj->id,
            'code'   => $obj->code,
            'label'  => $obj->label,
            'schema' => $obj->schema,
        ];
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->recordData = [];
        foreach ($this->object['schema']['fields'] ?? [] as $f) {
            $this->recordData[$f['code']] = $f['data_type'] === 'bool' ? false : '';
        }
    }

    public function openCreate()
    {
        $this->editingRecordId = null;
        $this->resetForm();
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function openEdit(string $recordId)
    {
        $record = CustomObjectData::where('object_id', $this->objectId)->findOrFail($recordId);
        $this->editingRecordId = $recordId;
        $this->resetForm();
        foreach ($record->data ?? [] as $k => $v) {
            if (array_key_exists($k, $this->recordData)) {
                $this->recordData[$k] = $v;
            }
        }
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel()
    {
        $this->showForm = false;
        $this->editingRecordId = null;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function save()
    {
        $service = app(CustomObjectService::class);

        // Boş değerleri temizle
        $data = [];
        foreach ($this->recordData as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $data[$k] = $v;
        }

        try {
            if ($this->editingRecordId) {
                $service->updateRecord($this->objectId, $this->editingRecordId, $data);
            } else {
                $service->createRecord($this->objectId, $data);
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                // field adı schema'daki kod ile eşleşiyor mu?
                $key = "recordData.{$field}";
                $this->addError($key, $messages[0]);
                // ek olarak genel hata
                $this->addError('saveError', $messages[0]);
            }
            return;
        }

        $this->cancel();
    }

    public function delete(string $recordId)
    {
        app(CustomObjectService::class)->deleteRecord($this->objectId, $recordId);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $records = app(CustomObjectService::class)->listRecords($this->objectId);

        return view('livewire.custom-objects.records', [
            'title'   => $this->object['label'] . ' — Kayıtlar',
            'object'  => $this->object,
            'records' => $records,
        ]);
    }
}
