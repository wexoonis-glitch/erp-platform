<?php
namespace App\Livewire\Fin;
use App\Models\FinArInvoice;
use App\Models\FinCustomer;
use App\Models\InvProduct;
use App\Models\Tenant;
use App\Services\Fin\ArInvoiceService;
use App\Services\Fin\TaxResolverService;
use App\Services\Inv\PricingService;
use App\Services\Inv\PriceListService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ArInvoices extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;
    public string $customer_id = '', $invoice_no = '', $invoice_date = '', $due_date = '', $currency = 'TRY', $notes = '';
    public array $lines = [];

    public function mount() {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->addDays(30)->toDateString();
    }

    private function effectiveTenantId(): ?string {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    public function openCreate() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->resetForm();
        $this->lines = [$this->emptyLine()];
        $this->showForm = true;
    }

    public function addLine() { $this->lines[] = $this->emptyLine(); }
    public function removeLine(int $i) { unset($this->lines[$i]); $this->lines = array_values($this->lines); }
    public function cancel() { $this->resetForm(); $this->showForm = false; $this->resetErrorBag(); }

    public function productChanged(int $index) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $productId = $this->lines[$index]['product_id'] ?? null;
        if (!$productId) return;

        TenantContext::runAs($tid, function () use ($index, $productId) {
            $p = InvProduct::find($productId);
            if (!$p) return;

            $this->lines[$index]['revenue_account_id'] = $p->revenue_account_id;
            $this->lines[$index]['description'] = $p->name;

            $tax = app(TaxResolverService::class)->resolveForProduct($productId);
            $this->lines[$index]['vat_rate'] = (string) $tax['rate'];
            $this->lines[$index]['tax_regime_id'] = $tax['regime_id'] ?? '';

            $qty = (float) ($this->lines[$index]['quantity'] ?? 1);
            $this->lines[$index]['unit_price'] = (string) app(PriceListService::class)
                ->resolve($productId, $this->customer_id ?: null, $qty);

            $this->refreshMarginAnalysis($index, $p, (float) $this->lines[$index]['unit_price']);
        });
    }

    public function quantityChanged(int $index) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $productId = $this->lines[$index]['product_id'] ?? null;
        if (!$productId) return;

        TenantContext::runAs($tid, function () use ($index, $productId) {
            $qty = (float) ($this->lines[$index]['quantity'] ?? 1);
            if ($qty > 0) {
                $this->lines[$index]['unit_price'] = (string) app(PriceListService::class)
                    ->resolve($productId, $this->customer_id ?: null, $qty);
            }
            $p = InvProduct::find($productId);
            if ($p) $this->refreshMarginAnalysis($index, $p, (float) ($this->lines[$index]['unit_price'] ?? 0));
        });
    }

    public function priceChanged(int $index) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $productId = $this->lines[$index]['product_id'] ?? null;
        if (!$productId) return;
        TenantContext::runAs($tid, function () use ($index, $productId) {
            $p = InvProduct::find($productId);
            if ($p) $this->refreshMarginAnalysis($index, $p, (float) ($this->lines[$index]['unit_price'] ?? 0));
        });
    }

    private function refreshMarginAnalysis(int $index, InvProduct $p, float $unitPrice): void
    {
        $analysis = app(PricingService::class)->analyze($p, $unitPrice);
        $this->lines[$index]['margin_warning'] = $analysis['below_min'] ? [
            'message' => 'Bu fiyat minimum altında',
            'minimum_price' => $analysis['minimum_price'],
            'cash_margin_pct' => $analysis['cash_margin_pct'],
            'cash_profit' => $analysis['cash_profit'],
            'below_cost' => $analysis['below_cost'],
        ] : null;
    }

    public function customerChanged() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () {
            foreach ($this->lines as $i => $line) {
                if (!empty($line['product_id'])) {
                    $qty = (float) ($line['quantity'] ?? 1);
                    $this->lines[$i]['unit_price'] = (string) app(PriceListService::class)
                        ->resolve($line['product_id'], $this->customer_id ?: null, $qty);
                    $p = InvProduct::find($line['product_id']);
                    if ($p) $this->refreshMarginAnalysis($i, $p, (float) $this->lines[$i]['unit_price']);
                }
            }
        });
    }

    public function save(bool $postAfter = false) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $data = [
            'customer_id' => $this->customer_id, 'invoice_no' => $this->invoice_no,
            'invoice_date' => $this->invoice_date, 'due_date' => $this->due_date,
            'currency' => $this->currency, 'notes' => $this->notes ?: null,
        ];
        $lines = [];
        foreach ($this->lines as $line) {
            if (empty($line['revenue_account_id'])) continue;
            $lines[] = [
                'revenue_account_id' => $line['revenue_account_id'],
                'product_id' => $line['product_id'] ?: null,
                'tax_regime_id' => $line['tax_regime_id'] ?: null,
                'description' => $line['description'],
                'quantity' => (float) ($line['quantity'] ?: 0),
                'unit_price' => (float) ($line['unit_price'] ?: 0),
                'vat_rate' => (float) ($line['vat_rate'] ?: 0),
            ];
        }
        try {
            TenantContext::runAs($tid, function () use ($data, $lines, $postAfter) {
                $svc = app(ArInvoiceService::class);
                $invoice = $svc->create($data, $lines);
                if ($postAfter) $svc->post($invoice->id);
            });
            $this->cancel();
        } catch (ValidationException $e) { foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]); }
    }

    public function post(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => app(ArInvoiceService::class)->post($id));
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => app(ArInvoiceService::class)->delete($id));
    }

    private function emptyLine(): array {
        return [
            'product_id' => '', 'revenue_account_id' => '', 'tax_regime_id' => '',
            'description' => '', 'quantity' => '1', 'unit_price' => '', 'vat_rate' => '0',
            'margin_warning' => null,
        ];
    }

    private function resetForm(): void {
        $this->reset(['customer_id', 'invoice_no', 'notes', 'lines']);
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->addDays(30)->toDateString();
        $this->currency = 'TRY';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $invoices = []; $customers = []; $products = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$invoices, &$customers, &$products) {
                $invoices = FinArInvoice::with('customer')->orderByDesc('invoice_date')->orderByDesc('created_at')->limit(100)->get()->map(fn($i) => [
                    'id' => $i->id, 'invoice_no' => $i->invoice_no,
                    'invoice_date' => $i->invoice_date->toDateString(),
                    'due_date' => $i->due_date->toDateString(),
                    'customer' => $i->customer?->name ?? '—',
                    'grand_total' => (float) $i->grand_total, 'paid_total' => (float) $i->paid_total,
                    'status' => $i->status, 'currency' => $i->currency,
                ])->toArray();
                $customers = FinCustomer::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $products = InvProduct::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray();
            });
        }
        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.fin.ar-invoices', compact('invoices', 'customers', 'products', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Müşteri Faturaları']);
    }
}
