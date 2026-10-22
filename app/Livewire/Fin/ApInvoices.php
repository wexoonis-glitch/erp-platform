<?php

namespace App\Livewire\Fin;

use App\Models\FinAccount;
use App\Models\FinApInvoice;
use App\Models\FinSupplier;
use App\Models\Tenant;
use App\Services\Fin\ApInvoiceService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ApInvoices extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';

    public bool $showForm = false;

    public string $supplier_id = '';
    public string $invoice_no = '';
    public string $invoice_date = '';
    public string $due_date = '';
    public string $currency = 'TRY';
    public string $notes = '';

    /** @var array<int, array{expense_account_id:string, description:string, quantity:string, unit_price:string, vat_rate:string}> */
    public array $lines = [];

    public function mount()
    {
        $actor = Auth::user();
        if (!in_array($actor->role, ['admin', 'superadmin'], true)) abort(403);

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
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->addDays(30)->toDateString();
    }

    private function effectiveTenantId(): ?string
    {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate()
    {
        if (!$this->effectiveTenantId()) { $this->addError('general', 'Tenant seçin.'); return; }
        $this->resetForm();
        $this->lines = [$this->emptyLine()];
        $this->showForm = true;
    }

    public function addLine() { $this->lines[] = $this->emptyLine(); }
    public function removeLine(int $i) { unset($this->lines[$i]); $this->lines = array_values($this->lines); }

    public function cancel()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save(bool $postAfter = false)
    {
        $tid = $this->effectiveTenantId();
        if (!$tid) { $this->addError('general', 'Tenant seçin.'); return; }

        $data = [
            'supplier_id'  => $this->supplier_id,
            'invoice_no'   => $this->invoice_no,
            'invoice_date' => $this->invoice_date,
            'due_date'     => $this->due_date,
            'currency'     => $this->currency,
            'notes'        => $this->notes ?: null,
        ];

        $lines = [];
        foreach ($this->lines as $line) {
            if (empty($line['expense_account_id'])) continue;
            $lines[] = [
                'expense_account_id' => $line['expense_account_id'],
                'description'        => $line['description'],
                'quantity'           => (float) ($line['quantity'] ?: 0),
                'unit_price'         => (float) ($line['unit_price'] ?: 0),
                'vat_rate'           => (float) ($line['vat_rate'] ?: 0),
            ];
        }

        try {
            TenantContext::runAs($tid, function () use ($data, $lines, $postAfter) {
                $svc = app(ApInvoiceService::class);
                $invoice = $svc->create($data, $lines);
                if ($postAfter) $svc->post($invoice->id);
            });
            $this->cancel();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function post(string $id)
    {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            TenantContext::runAs($tid, fn() => app(ApInvoiceService::class)->post($id));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function delete(string $id)
    {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            TenantContext::runAs($tid, fn() => app(ApInvoiceService::class)->delete($id));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    private function emptyLine(): array
    {
        return ['expense_account_id' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '', 'vat_rate' => '20'];
    }

    private function resetForm(): void
    {
        $this->reset(['supplier_id', 'invoice_no', 'notes', 'lines']);
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->addDays(30)->toDateString();
        $this->currency = 'TRY';
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();
        $tid = $this->effectiveTenantId();

        $invoices = [];
        $suppliers = [];
        $expenseAccounts = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$invoices, &$suppliers, &$expenseAccounts) {
                $invoices = FinApInvoice::with('supplier')
                    ->orderByDesc('invoice_date')
                    ->orderByDesc('created_at')
                    ->limit(100)
                    ->get()
                    ->map(fn($i) => [
                        'id'          => $i->id,
                        'invoice_no'  => $i->invoice_no,
                        'invoice_date'=> $i->invoice_date->toDateString(),
                        'due_date'    => $i->due_date->toDateString(),
                        'supplier'    => $i->supplier?->name ?? '—',
                        'grand_total' => (float) $i->grand_total,
                        'paid_total'  => (float) $i->paid_total,
                        'status'      => $i->status,
                        'currency'    => $i->currency,
                    ])
                    ->toArray();

                $suppliers = FinSupplier::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray();

                // Gider/stok hesapları (postable, type=expense veya asset)
                $expenseAccounts = FinAccount::where('status', 'active')
                    ->where('is_postable', true)
                    ->whereIn('type', ['expense', 'asset'])
                    ->orderBy('code')
                    ->get(['id', 'code', 'name', 'type'])
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

        return view('livewire.fin.ap-invoices', [
            'title'           => 'Tedarikçi Faturaları',
            'invoices'        => $invoices,
            'suppliers'       => $suppliers,
            'expenseAccounts' => $expenseAccounts,
            'isSuper'         => $isSuper,
            'tenants'         => $tenants,
        ]);
    }
}
