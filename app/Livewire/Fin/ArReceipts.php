<?php
namespace App\Livewire\Fin;
use App\Models\FinAccount;
use App\Models\FinArInvoice;
use App\Models\FinArReceipt;
use App\Models\FinCustomer;
use App\Models\Tenant;
use App\Services\Fin\ArReceiptService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ArReceipts extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;

    public string $customer_id = '';
    public string $bank_account_id = '';
    public string $receipt_date = '';
    public string $amount = '';
    public string $currency = 'TRY';
    public string $method = 'bank';
    public string $reference = '';
    public string $notes = '';
    public array $allocations = [];

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->receipt_date = now()->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function updatedCustomerId() {
        $this->allocations = [];
        $tid = $this->effectiveTenantId();
        if (!$tid || !$this->customer_id) return;

        TenantContext::runAs($tid, function () {
            $openInvoices = FinArInvoice::where('customer_id', $this->customer_id)->whereIn('status', ['posted', 'partially_paid'])->orderBy('due_date')->get();
            foreach ($openInvoices as $inv) {
                if ((float) $inv->grand_total - (float) $inv->paid_total > 0) {
                    $this->allocations[] = ['invoice_id' => $inv->id, 'amount' => ''];
                }
            }
        });
    }

    public function openCreate() { if (!$this->effectiveTenantId()) return; $this->resetForm(); $this->showForm = true; }
    public function cancel() { $this->resetForm(); $this->showForm = false; $this->resetErrorBag(); }

    public function save(bool $postAfter = false) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $data = ['customer_id' => $this->customer_id, 'bank_account_id' => $this->bank_account_id, 'receipt_date' => $this->receipt_date, 'amount' => (float)$this->amount, 'currency' => $this->currency, 'method' => $this->method, 'reference' => $this->reference, 'notes' => $this->notes];
        $allocs = [];
        foreach ($this->allocations as $a) {
            if (!empty($a['invoice_id']) && !empty($a['amount'])) $allocs[] = ['invoice_id' => $a['invoice_id'], 'amount' => (float)$a['amount']];
        }
        try {
            TenantContext::runAs($tid, function () use ($data, $allocs, $postAfter) {
                $svc = app(ArReceiptService::class);
                $receipt = $svc->create($data, $allocs);
                if ($postAfter) $svc->post($receipt->id);
            });
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function post(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) { try { TenantContext::runAs($tid, fn() => app(ArReceiptService::class)->post($id)); } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); } }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => app(ArReceiptService::class)->delete($id));
    }

    private function resetForm() { $this->reset(['customer_id', 'bank_account_id', 'amount', 'reference', 'notes', 'allocations']); $this->receipt_date = now()->toDateString(); $this->currency = 'TRY'; $this->method = 'bank'; }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $receipts = []; $customers = []; $bankAccounts = []; $openInvoices = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$receipts, &$customers, &$bankAccounts, &$openInvoices) {
                $receipts = FinArReceipt::with('customer')->orderByDesc('receipt_date')->limit(100)->get()->map(fn($r) => ['id' => $r->id, 'receipt_no' => $r->receipt_no, 'receipt_date' => $r->receipt_date->toDateString(), 'customer' => $r->customer?->name, 'amount' => (float)$r->amount, 'currency' => $r->currency, 'status' => $r->status])->toArray();
                $customers = FinCustomer::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $bankAccounts = FinAccount::where('status', 'active')->where('is_postable', true)->whereIn('code', ['100', '102', '103'])->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                if ($this->customer_id) {
                    $openInvoices = FinArInvoice::where('customer_id', $this->customer_id)->whereIn('status', ['posted', 'partially_paid'])->orderBy('due_date')->get()->map(fn($i) => ['id' => $i->id, 'invoice_no' => $i->invoice_no, 'remaining' => (float)$i->grand_total - (float)$i->paid_total])->toArray();
                }
            });
        }

        $tenants = []; if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.ar-receipts', compact('receipts', 'customers', 'bankAccounts', 'openInvoices', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Müşteri Tahsilatları']);
    }
}
