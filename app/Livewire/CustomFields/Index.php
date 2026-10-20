<?php

namespace App\Livewire\CustomFields;

use App\Models\CustomField;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Index extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    private const BUILTIN_ENTITIES = ['tenant', 'user', 'ap_invoice', 'ar_invoice', 'sales_order', 'purchase_order', 'inventory_item'];

    public bool $showCreate = false;

    #[Validate('required|string|max:100')]
    public string $entity = '';

    #[Validate('required|string|max:50|regex:/^[a-z][a-z0-9_]{0,49}$/')]
    public string $code = '';

    #[Validate('required|string|max:255')]
    public string $label = '';

    #[Validate('required|in:string,number,date,bool,enum,json')]
    public string $data_type = 'string';

    public string $enum_options = '';

    public string $tenant_id = '';

    // Edit state
    public ?string $editingId = null;
    public string $editLabel = '';
    public string $editEnumOptions = '';

    public function mount()
    {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }
        if (!$actor->isSuperadmin()) {
            $this->tenant_id = $actor->tenant_id ?? '';
        }
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

        if (!in_array($this->entity, self::BUILTIN_ENTITIES, true)) {
            $objExists = TenantContext::runAs($targetTenantId, function () {
                return \App\Models\CustomObject::where('code', $this->entity)->exists();
            });
            if (!$objExists) {
                $this->addError('entity', "Bilinmeyen entity: {$this->entity}");
                return;
            }
        }

        $config = [];
        if ($this->data_type === 'enum') {
            $opts = array_filter(array_map('trim', explode(',', $this->enum_options)));
            if (empty($opts)) {
                $this->addError('enum_options', 'En az bir seçenek girin (virgülle ayrılmış).');
                return;
            }
            $config = ['options' => array_values($opts)];
        }

        $exists = TenantContext::runAs($targetTenantId, function () {
            return CustomField::where('entity', $this->entity)->where('code', $this->code)->exists();
        });
        if ($exists) {
            $this->addError('code', 'Bu entity için kod zaten kullanımda.');
            return;
        }

        TenantContext::runAs($targetTenantId, function () use ($targetTenantId, $config) {
            CustomField::create([
                'id'        => (string) Str::uuid(),
                'tenant_id' => $targetTenantId,
                'entity'    => $this->entity,
                'code'      => $this->code,
                'label'     => $this->label,
                'data_type' => $this->data_type,
                'config'    => $config,
            ]);
        });

        $this->reset(['entity', 'code', 'label', 'data_type', 'enum_options', 'showCreate']);
        if ($actorIsSuper) {
            $this->reset('tenant_id');
        } else {
            $this->tenant_id = $actor->tenant_id;
        }
    }

    public function openEdit(string $id)
    {
        $f = CustomField::findOrFail($id);
        $this->editingId = $f->id;
        $this->editLabel = $f->label;
        $this->editEnumOptions = '';
        if ($f->data_type === 'enum') {
            $this->editEnumOptions = implode(', ', $f->config['options'] ?? []);
        }
        $this->resetErrorBag();
    }

    public function cancelEdit()
    {
        $this->editingId = null;
        $this->editLabel = '';
        $this->editEnumOptions = '';
        $this->resetErrorBag();
    }

    public function saveEdit()
    {
        $this->validate([
            'editLabel' => 'required|string|max:255',
        ]);

        $f = CustomField::findOrFail($this->editingId);

        $config = $f->config ?? [];
        if ($f->data_type === 'enum') {
            $opts = array_filter(array_map('trim', explode(',', $this->editEnumOptions)));
            if (empty($opts)) {
                $this->addError('editEnumOptions', 'En az bir seçenek girin.');
                return;
            }
            $config = ['options' => array_values($opts)];
        }

        $f->label = $this->editLabel;
        $f->config = $config;
        $f->save();

        $this->cancelEdit();
    }

    public function delete(string $id)
    {
        CustomField::findOrFail($id)->delete();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();

        $fields = CustomField::orderBy('entity')->orderBy('code')
            ->get(['id', 'tenant_id', 'entity', 'code', 'label', 'data_type', 'config'])
            ->toArray();

        $tenants = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::orderBy('code')->get(['id', 'code', 'name'])->toArray(),
                superadmin: true
            );
        }

        return view('livewire.custom-fields.index', [
            'title'    => 'Custom Fields',
            'isSuper'  => $isSuper,
            'fields'   => $fields,
            'tenants'  => $tenants,
            'entities' => self::BUILTIN_ENTITIES,
        ]);
    }
}
