<?php

namespace App\Livewire\Fin;

use App\Models\FinAccount;
use App\Models\FinApInvoice;
use App\Models\FinApPayment;
use App\Models\FinSupplier;
use App\Models\Tenant;
use App\Services\Fin\ApPaymentService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ApPayments extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';

    public bool $showForm = false;

    public string $supplier_id = '';
    public string $bank_account_id = '';
    public string $payment_date = '';
    public string $amount = '';
    public string $currency = 'TRY';
    public string $method = 'bank';
    public string $reference = '';
    public string $notes = '';

    /** @var array<int, array{invoice_id:string, amount:string}> */
    public array $allocations = [];

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
        $this->payment_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string
    {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function updatedSupplierId()
    {
        // Tedarikçi seçilince açık faturaları otomatik doldur
        $this->allocations = [];
        $tid = $this->effectiveTenantId();
        if (!$tid || !$this->supplier_id) return;

        TenantContext::runAs($tid, function () {
            $openInvoices = FinApInvoice::where('supplier_id', $this->supplier_id)
                ->whereIn('status', ['posted', 'partially_paid'])
                ->orderBy('due_date')
                ->get();

            foreach ($openInvoices as $inv) {
                $remaining = (float) $inv->grand_total - (float) $inv->paid_total;
                if ($remaining > 0) {
                    $this->allocations[] = [
                        'invoice_id' => $inv->id,
                        'amount'     => '', // kullanıcı girer
                    ];
                }
            }
        });
    }

    public function openCreate()
    {
        if (!$this->effectiveTenantId()) { $this->addError('general', 'Tenant seçin.'); return; }
        $this->resetForm();
        $this->showForm = true;
    }

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
            'supplier_id'     => $this->supplier_id,
            'bank_account_id' => $this->bank_account_id,
            'payment_date'    => $this->payment_date,
            'amount'          => (float) $this->amount,
            'currency'        => $this->currency,
            'method'          => $this->method,
            'reference'       => $this->reference ?: null,
            'notes'           => $this->notes ?: null,
        ];

        $allocs = [];
        foreach ($this->allocations as $a) {
            if (empty($a['invoice_id']) || empty($a['amount'])) continue;
            $allocs[] = [
                'invoice_id' => $a['invoice_id'],
                'amount'     => (float) $a['amount'],
            ];
        }

        try {
            TenantContext::runAs($tid, function () use ($data, $allocs, $postAfter) {
                $svc = app(ApPaymentService::class);
                $payment = $svc->create($data, $allocs);
                if ($postAfter) $svc->post($payment->id);
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
            TenantContext::runAs($tid, fn() => app(ApPaymentService::class)->post($id));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function delete(string $id)
    {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        try {
            TenantContext::runAs($tid, fn() => app(ApPaymentService::class)->delete($id));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    private function resetForm(): void
    {
        $this->reset(['supplier_id', 'bank_account_id', 'amount', 'reference', 'notes', 'allocations']);
        $this->payment_date = now()->toDateString();
        $this->currency = 'TRY';
        $this->method = 'bank';
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();
        $tid = $this->effectiveTenantId();

        $payments = [];
        $suppliers = [];
        $bankAccounts = [];
        $openInvoices = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$payments, &$suppliers, &$bankAccounts, &$openInvoices) {
                $payments = FinApPayment::with('supplier')
                    ->orderByDesc('payment_date')
                    ->orderByDesc('created_at')
                    ->limit(100)
                    ->get()
                    ->map(fn($p) => [
                        'id'            => $p->id,
                        'payment_no'    => $p->payment_no,
                        'payment_date'  => $p->payment_date->toDateString(),
                        'supplier'      => $p->supplier?->name ?? '—',
                        'amount'        => (float) $p->amount,
                        'currency'      => $p->currency,
                        'method'        => $p->method,
                        'reference'     => $p->reference,
                        'status'        => $p->status,
                    ])
                    ->toArray();

                $suppliers = FinSupplier::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray();

                // Ödeme hesapları: 100 Kasa, 102 Bankalar (postable, asset)
                $bankAccounts = FinAccount::where('status', 'active')
                    ->where('is_postable', true)
                    ->whereIn('code', ['100', '102', '103'])
                    ->orderBy('code')
                    ->get(['id', 'code', 'name'])
                    ->toArray();

                if ($this->supplier_id) {
                    $openInvoices = FinApInvoice::where('supplier_id', $this->supplier_id)
                        ->whereIn('status', ['posted', 'partially_paid'])
                        ->orderBy('due_date')
                        ->get()
                        ->map(fn($i) => [
                            'id'          => $i->id,
                            'invoice_no'  => $i->invoice_no,
                            'invoice_date'=> $i->invoice_date->toDateString(),
                            'due_date'    => $i->due_date->toDateString(),
                            'grand_total' => (float) $i->grand_total,
                            'paid_total'  => (float) $i->paid_total,
                            'remaining'   => (float) $i->grand_total - (float) $i->paid_total,
                        ])
                        ->toArray();
                }
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

        return view('livewire.fin.ap-payments', [
            'title'        => 'Tedarikçi Ödemeleri',
            'payments'     => $payments,
            'suppliers'    => $suppliers,
            'bankAccounts' => $bankAccounts,
            'openInvoices' => $openInvoices,
            'isSuper'      => $isSuper,
            'tenants'      => $tenants,
        ]);
    }
}
