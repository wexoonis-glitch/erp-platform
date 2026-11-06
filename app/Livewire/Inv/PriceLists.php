<?php
namespace App\Livewire\Inv;
use App\Models\InvPriceList;
use App\Models\InvProduct;
use App\Models\Tenant;
use App\Services\Inv\PriceListService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PriceLists extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public bool $showForm = false;

    public string $code = '', $name = '', $currency = 'TRY', $valid_from = '', $valid_to = '';
    public string $priority = '100';
    public bool $is_default = false;

    public array $items = [];

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

    public function openCreate() {
        $this->resetForm();
        $this->items = [$this->emptyItem()];
        $this->showForm = true;
    }

    public function cancel() { $this->resetForm(); $this->showForm = false; $this->resetErrorBag(); }

    public function addItem() { $this->items[] = $this->emptyItem(); }
    public function removeItem(int $i) { unset($this->items[$i]); $this->items = array_values($this->items); }

    public function save() {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        $this->validate(['code' => 'required', 'name' => 'required']);

        try {
            TenantContext::runAs($tid, fn() => app(PriceListService::class)->createList([
                'code' => $this->code, 'name' => $this->name,
                'currency' => $this->currency,
                'valid_from' => $this->valid_from,
                'valid_to' => $this->valid_to,
                'priority' => $this->priority,
                'is_default' => $this->is_default,
            ], $this->items));
            $this->cancel();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function delete(string $id) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;
        TenantContext::runAs($tid, fn() => app(PriceListService::class)->delete($id));
    }

    private function emptyItem(): array {
        return ['product_id' => '', 'unit_price' => '0', 'min_quantity' => '1', 'discount_rate' => '0'];
    }

    private function resetForm() {
        $this->reset(['code', 'name', 'valid_from', 'valid_to', 'items']);
        $this->currency = 'TRY';
        $this->priority = '100';
        $this->is_default = false;
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $lists = []; $products = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$lists, &$products) {
                $lists = InvPriceList::withCount('items')->orderByDesc('is_default')->orderBy('code')->get()->toArray();
                $products = InvProduct::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name', 'sales_price'])->toArray();
            });
        }
        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.inv.price-lists', compact('lists', 'products', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Fiyat Listeleri']);
    }
}
