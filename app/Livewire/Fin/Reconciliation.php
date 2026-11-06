<?php
namespace App\Livewire\Fin;
use App\Models\FinBankTransaction;
use App\Models\SysBankStatement;
use App\Models\SysBankStatementLine;
use App\Models\Tenant;
use App\Services\Fin\BankStatementService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class Reconciliation extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    #[Url] public string $statementId = '';
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

    public function match(string $lineId, string $txId) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            TenantContext::runAs($tid, fn() => app(BankStatementService::class)->manualMatch($lineId, $txId));
            $this->messageType = 'success';
            $this->message = 'Eşleştirildi.';
        } catch (ValidationException $e) {
            $this->messageType = 'error';
            $this->message = collect($e->errors())->flatten()->first() ?? 'Eşleştirme başarısız.';
        }
    }

    public function unmatch(string $lineId) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, fn() => app(BankStatementService::class)->unmatch($lineId));
        $this->messageType = 'success';
        $this->message = 'Eşleşme kaldırıldı.';
    }

    public function ignore(string $lineId) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, fn() => app(BankStatementService::class)->ignore($lineId));
        $this->message = 'Satır yok sayıldı.';
    }

    public function autoMatch() {
        if (!$this->statementId) return;
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            $result = null;
            TenantContext::runAs($tid, function () use (&$result) {
                $result = app(BankStatementService::class)->autoMatch($this->statementId);
            });
            $this->messageType = 'success';
            $this->message = "Otomatik eşleştirme: {$result['matched']} eşleşti, {$result['unmatched']} kaldı.";
        } catch (ValidationException $e) {
            $this->messageType = 'error';
            $this->message = collect($e->errors())->flatten()->first() ?? 'Hata.';
        }
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $statements = []; $lines = []; $availableTransactions = [];
        $statement = null;

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$statements, &$lines, &$availableTransactions, &$statement) {
                $statements = SysBankStatement::with('bankAccount')->orderByDesc('created_at')->get()->map(fn($s) => [
                    'id' => $s->id, 'label' => "{$s->file_name} — {$s->bankAccount?->name} ({$s->created_at->format('d.m.Y H:i')})",
                ])->toArray();

                if ($this->statementId) {
                    $statement = SysBankStatement::with(['bankAccount', 'lines'])->find($this->statementId);
                    if ($statement) {
                        $lines = $statement->lines->map(fn($l) => [
                            'id' => $l->id, 'line_no' => $l->line_no,
                            'value_date' => $l->value_date?->toDateString() ?? '—',
                            'amount' => (float) $l->amount,
                            'description' => $l->description,
                            'reference' => $l->reference,
                            'status' => $l->status,
                            'matched_transaction_id' => $l->matched_transaction_id,
                            'matched_date' => $l->matchedTransaction?->transaction_date?->toDateString() ?? null,
                            'matched_amount' => $l->matchedTransaction ? (float) $l->matchedTransaction->amount : null,
                        ])->toArray();

                        $matchedIds = SysBankStatementLine::where('tenant_id', $statement->tenant_id)
                            ->whereNotNull('matched_transaction_id')->pluck('matched_transaction_id')->toArray();

                        $availableTransactions = FinBankTransaction::where('bank_account_id', $statement->bank_account_id)
                            ->whereNotIn('id', $matchedIds)
                            ->orderByDesc('transaction_date')
                            ->limit(200)
                            ->get()
                            ->map(fn($t) => [
                                'id' => $t->id,
                                'transaction_date' => $t->transaction_date?->toDateString() ?? '—',
                                'type' => $t->type,
                                'amount' => (float) $t->amount,
                                'description' => $t->description,
                            ])->toArray();
                    }
                }
            });
        }

        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.reconciliation', compact('statements', 'lines', 'availableTransactions', 'statement', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Banka Mutabakatı']);
    }
}
