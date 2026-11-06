<?php
namespace App\Livewire\Documents;
use App\Models\SysDocument;
use App\Models\Tenant;
use App\Services\Platform\DocumentService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component {
    use WithFileUploads;

    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    
    public $file;
    public string $entity_type = 'general';
    public string $entity_id = '';

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
    public function cancel() { $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate([
            'file' => 'required|file|max:10240', // Maks 10MB
        ]);

        try {
            TenantContext::runAs($tid, fn() => app(DocumentService::class)->upload(
                $this->file, 
                $this->entity_type === 'general' ? null : $this->entity_type, 
                $this->entity_id ?: null
            ));
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => app(DocumentService::class)->delete($id));
    }

    private function resetForm() {
        $this->reset(['file', 'entity_id']);
        $this->entity_type = 'general';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $documents = [];
        if ($tid) { TenantContext::runAs($tid, function () use (&$documents) { $documents = SysDocument::orderByDesc('created_at')->get()->toArray(); }); }
        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        
        return view('livewire.documents.index', compact('documents', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Doküman Yönetimi']);
    }
}
