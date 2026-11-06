<?php
namespace App\Livewire\Inv;

use App\Models\CustomField;
use App\Models\FinAccount;
use App\Models\InvProduct;
use App\Models\Tenant;
use App\Services\Fin\TaxResolverService;
use App\Services\Inv\PricingService;
use App\Services\Inv\ProductService;
use App\Services\Inv\StockService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Products extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';
    public bool $showForm = false;
    public ?string $editingId = null;

    public string $code = '', $name = '', $unit = 'Adet', $sales_price = '0', $purchase_price = '0', $vat_rate = '20';
    public string $replacement_cost = '0', $target_margin_pct = '30', $min_margin_pct = '10', $overhead_per_unit = '0';
    public string $revenue_account_id = '', $inventory_account_id = '', $cogs_account_id = '', $status = 'active';
    public bool $track_stock = true;
    public string $opening_qty = '0';
    public string $tax_regime_id = '';
    public string $cost_method = 'standard';
    public array $customFields = [];

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

    public function openEdit(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () use ($id) {
            $p = InvProduct::findOrFail($id);
            $this->editingId = $p->id;
            $this->code = $p->code; $this->name = $p->name; $this->unit = $p->unit;
            $this->track_stock = (bool) $p->track_stock;
            $this->sales_price = (string) $p->sales_price;
            $this->purchase_price = (string) $p->purchase_price;
            $this->replacement_cost = (string) ($p->replacement_cost ?? 0);
            $this->target_margin_pct = (string) ($p->target_margin_pct ?? 30);
            $this->min_margin_pct = (string) ($p->min_margin_pct ?? 10);
            $this->overhead_per_unit = (string) ($p->overhead_per_unit ?? 0);
            $this->vat_rate = (string) $p->vat_rate;
            $this->tax_regime_id = $p->tax_regime_id ?? '';
            $this->cost_method = $p->cost_method ?? 'standard';
            $this->revenue_account_id = $p->revenue_account_id ?? '';
            $this->inventory_account_id = $p->inventory_account_id ?? '';
            $this->cogs_account_id = $p->cogs_account_id ?? '';
            $this->status = $p->status;
            $this->customFields = $p->custom_fields ?? [];
        });
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function updatedTaxRegimeId() {
        if (!$this->tax_regime_id) return;
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, function () {
            $regime = \App\Models\SysTaxRegime::find($this->tax_regime_id);
            if ($regime) $this->vat_rate = (string) $regime->rate;
        });
    }

    public function cancel() { $this->resetForm(); $this->showForm = false; $this->resetErrorBag(); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        $rules = ['code' => 'required|string|max:50', 'name' => 'required|string|max:255'];
        $rules = array_merge($rules, CustomField::validateCustomFields('inv_product'));
        $this->validate($rules);

        $data = [
            'code' => $this->code, 'name' => $this->name, 'unit' => $this->unit,
            'track_stock' => $this->track_stock, 'sales_price' => (float)$this->sales_price,
            'purchase_price' => (float)$this->purchase_price,
            'replacement_cost' => (float)$this->replacement_cost,
            'target_margin_pct' => (float)$this->target_margin_pct,
            'min_margin_pct' => (float)$this->min_margin_pct,
            'overhead_per_unit' => (float)$this->overhead_per_unit,
            'vat_rate' => (float)$this->vat_rate,
            'tax_regime_id' => $this->tax_regime_id ?: null,
            'cost_method' => $this->cost_method,
            'revenue_account_id' => $this->revenue_account_id,
            'inventory_account_id' => $this->track_stock ? $this->inventory_account_id : null,
            'cogs_account_id' => $this->track_stock ? $this->cogs_account_id : null,
            'status' => $this->status,
            'custom_fields' => $this->customFields,
        ];
        try {
            TenantContext::runAs($tid, function () use ($data) {
                if ($this->editingId) {
                    $product = InvProduct::findOrFail($this->editingId);
                    if (InvProduct::where('code', $this->code)->where('id', '!=', $product->id)->exists()) {
                        throw ValidationException::withMessages(['code' => 'Kod zaten kullanımda.']);
                    }
                    $product->update($data);
                } else {
                    if ((float)$data['replacement_cost'] === 0.0 && (float)$data['purchase_price'] > 0) {
                        $data['replacement_cost'] = (float) $data['purchase_price'];
                    }
                    $product = app(ProductService::class)->create($data);
                    if ($this->track_stock && (float)$this->opening_qty > 0) {
                        $svc = app(StockService::class);
                        $warehouse = $svc->getDefaultWarehouse();
                        $svc->recordIn($product->id, $warehouse->id, now()->toDateString(), (float)$this->opening_qty, (float)$this->purchase_price, 'OPENING', null, 'Açılış Stoğu');
                    }
                }
            });
            $this->cancel();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, fn() => InvProduct::findOrFail($id)->delete());
    }

    private function resetForm() {
        $this->reset(['editingId', 'code', 'name', 'revenue_account_id', 'inventory_account_id', 'cogs_account_id', 'opening_qty', 'tax_regime_id', 'customFields']);
        $this->unit = 'Adet'; $this->track_stock = true;
        $this->sales_price = '0'; $this->purchase_price = '0'; $this->vat_rate = '20'; $this->status = 'active';
        $this->cost_method = 'standard';
        $this->replacement_cost = '0'; $this->target_margin_pct = '30'; $this->min_margin_pct = '10'; $this->overhead_per_unit = '0';
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $products = []; $revenueAccounts = []; $assetAccounts = []; $expenseAccounts = []; $taxRegimes = [];
        $suggestedById = []; $minimumById = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$products, &$revenueAccounts, &$assetAccounts, &$expenseAccounts, &$taxRegimes, &$suggestedById, &$minimumById) {
                $rows = InvProduct::orderBy('code')->get();
                $products = $rows->toArray();
                $revenueAccounts = FinAccount::where('type', 'revenue')->where('is_postable', true)->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $assetAccounts = FinAccount::where('type', 'asset')->where('is_postable', true)->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $expenseAccounts = FinAccount::where('type', 'expense')->where('is_postable', true)->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $taxRegimes = app(TaxResolverService::class)->availableForTenant();

                $pricing = app(PricingService::class);
                foreach ($rows as $p) {
                    $suggestedById[$p->id] = $pricing->suggestedPrice($p);
                    $minimumById[$p->id] = $pricing->minimumPrice($p);
                }
            });
        }

        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);

        return view('livewire.inv.products', compact('products', 'revenueAccounts', 'assetAccounts', 'expenseAccounts', 'taxRegimes', 'suggestedById', 'minimumById', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Ürünler']);
    }
}
