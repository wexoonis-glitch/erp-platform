<?php

namespace App\Livewire\Fin;

use App\Models\FinAccount;
use App\Models\FinJournalEntry;
use App\Models\Tenant;
use App\Services\Fin\JournalEntryService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class JournalEntries extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';

    public bool $showForm = false;
    public ?string $editingId = null;

    public string $entry_date = '';
    public string $description = '';

    /** @var array<int, array{account_id:string, debit:string, credit:string, description:string}> */
    public array $lines = [];

    public function mount()
    {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::where('status', 'active')->orderBy('code')->first(),
                superadmin: true
            );
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->entry_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string
    {
        if (TenantContext::isSuperadmin()) {
            return $this->selectedTenantId ?: null;
        }
        return Auth::user()->tenant_id;
    }

    public function openCreate()
    {
        if (!$this->effectiveTenantId()) {
            $this->addError('general', 'Tenant seçmelisiniz.');
            return;
        }
        $this->resetForm();
        $this->lines = [$this->emptyLine(), $this->emptyLine()];
        $this->showForm = true;
    }

    public function openEdit(string $id)
    {
        $tenantId = $this->effectiveTenantId();
        if (!$tenantId) return;

        TenantContext::runAs($tenantId, function () use ($id) {
            $entry = FinJournalEntry::findOrFail($id);
            if ($entry->status !== 'draft') return;

            $this->editingId = $entry->id;
            $this->entry_date = $entry->entry_date->toDateString();
            $this->description = $entry->description;
            $this->lines = $entry->lines->map(fn($l) => [
                'account_id'  => $l->account_id,
                'debit'       => $l->debit > 0 ? (string) $l->debit : '',
                'credit'      => $l->credit > 0 ? (string) $l->credit : '',
                'description' => $l->description ?? '',
            ])->all();
            $this->showForm = true;
        });

        $this->resetErrorBag();
    }

    public function addLine()
    {
        $this->lines[] = $this->emptyLine();
    }

    public function removeLine(int $i)
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
    }

    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save(bool $postAfterSave = false)
    {
        $tenantId = $this->effectiveTenantId();
        if (!$tenantId) { $this->addError('general', 'Tenant seçmelisiniz.'); return; }

        $data = [
            'entry_date'  => $this->entry_date,
            'description' => $this->description,
        ];

        $lines = [];
        foreach ($this->lines as $i => $line) {
            if (empty($line['account_id'])) continue;
            $lines[] = [
                'account_id'  => $line['account_id'],
                'debit'       => (float) ($line['debit'] ?: 0),
                'credit'      => (float) ($line['credit'] ?: 0),
                'description' => $line['description'] ?: null,
            ];
        }

        try {
            TenantContext::runAs($tenantId, function () use ($data, $lines, $postAfterSave) {
                $svc = app(JournalEntryService::class);
                $entry = $this->editingId
                    ? $svc->update($this->editingId, $data, $lines)
                    : $svc->create($data, $lines);

                if ($postAfterSave) {
                    $svc->post($entry->id);
                }
            });
            $this->cancel();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) {
                $this->addError($f, $m[0]);
            }
        }
    }

    public function post(string $id)
    {
        $tenantId = $this->effectiveTenantId();
        if (!$tenantId) return;
        try {
            TenantContext::runAs($tenantId, fn() => app(JournalEntryService::class)->post($id));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) {
                $this->addError($f, $m[0]);
            }
        }
    }

    public function reverse(string $id)
    {
        $tenantId = $this->effectiveTenantId();
        if (!$tenantId) return;
        try {
            TenantContext::runAs($tenantId, fn() => app(JournalEntryService::class)->reverse($id));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) {
                $this->addError($f, $m[0]);
            }
        }
    }

    public function delete(string $id)
    {
        $tenantId = $this->effectiveTenantId();
        if (!$tenantId) return;
        try {
            TenantContext::runAs($tenantId, fn() => app(JournalEntryService::class)->delete($id));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) {
                $this->addError($f, $m[0]);
            }
        }
    }

    private function emptyLine(): array
    {
        return ['account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''];
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'description']);
        $this->entry_date = now()->toDateString();
        $this->lines = [];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();
        $tenantId = $this->effectiveTenantId();

        $entries = [];
        $accounts = [];

        if ($tenantId) {
            TenantContext::runAs($tenantId, function () use (&$entries, &$accounts) {
                $entries = FinJournalEntry::orderByDesc('entry_date')
                    ->orderByDesc('entry_no')
                    ->limit(100)
                    ->get(['id', 'entry_no', 'entry_date', 'description', 'status'])
                    ->toArray();

                $accounts = FinAccount::where('status', 'active')
                    ->where('is_postable', true)
                    ->orderBy('code')
                    ->get(['id', 'code', 'name'])
                    ->toArray();
            });
        }

        $tenants = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(),
                superadmin: true
            );
        }

        return view('livewire.fin.journal-entries', [
            'title'    => 'Yevmiye Kayıtları',
            'entries'  => $entries,
            'accounts' => $accounts,
            'isSuper'  => $isSuper,
            'tenants'  => $tenants,
        ]);
    }
}
