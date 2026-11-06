<?php
namespace App\Livewire\Fin;
use App\Models\FinBankAccount;
use App\Models\SysBankStatement;
use App\Models\Tenant;
use App\Services\Fin\BankStatementService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class BankStatements extends Component {
    use WithFileUploads;

    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public string $bank_account_id = '';
    public string $format = 'csv';
    public $file = null;
    public string $message = '';
    public string $messageType = 'success';

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
            'bank_account_id' => 'required',
            'file' => 'required|file|max:10240',
        ]);

        try {
            $svc = app(BankStatementService::class);
            TenantContext::runAs($tid, function () use ($svc) {
                if ($this->format === 'mt940') $svc->importMt940($this->bank_account_id, $this->file);
                else $svc->importCsv($this->bank_account_id, $this->file);
            });
            $this->messageType = 'success';
            $this->message = 'Ekstre içe aktarıldı. Otomatik eşleştirme başlatılıyor...';
            $this->cancel();
        } catch (ValidationException $e) {
            $this->messageType = 'error';
            $this->message = collect($e->errors())->flatten()->first() ?? 'İçe aktarma başarısız.';
        }
    }

    public function autoMatch(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            $result = null;
            TenantContext::runAs($tid, function () use ($id, &$result) {
                $result = app(BankStatementService::class)->autoMatch($id);
            });
            $this->messageType = 'success';
            $this->message = "Otomatik eşleştirme: {$result['matched']} eşleşti, {$result['unmatched']} kaldı.";
        } catch (ValidationException $e) {
            $this->messageType = 'error';
            $this->message = collect($e->errors())->flatten()->first() ?? 'Eşleştirme başarısız.';
        }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, fn() => app(BankStatementService::class)->delete($id));
    }

    private function resetForm() {
        $this->reset(['file', 'bank_account_id']);
        $this->format = 'csv';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $statements = []; $accounts = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$statements, &$accounts) {
                $statements = SysBankStatement::with('bankAccount')->orderByDesc('created_at')->limit(100)->get()->map(fn($s) => [
                    'id' => $s->id, 'file_name' => $s->file_name, 'format' => $s->format,
                    'bank_account' => $s->bankAccount?->name ?? '—',
                    'statement_from' => $s->statement_from?->toDateString() ?? '—',
                    'statement_to' => $s->statement_to?->toDateString() ?? '—',
                    'total_lines' => $s->total_lines, 'matched_lines' => $s->matched_lines,
                    'status' => $s->status, 'created_at' => $s->created_at?->toDateTimeString() ?? '—',
                ])->toArray();
                $accounts = FinBankAccount::where('status', 'active')->orderBy('name')->get(['id', 'name', 'currency'])->toArray();
            });
        }
        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.bank-statements', compact('statements', 'accounts', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Banka Ekstreleri']);
    }
}
