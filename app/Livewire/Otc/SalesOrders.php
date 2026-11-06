<?php
namespace App\Livewire\Otc;

use App\Models\OtcSalesOrder;
use App\Models\FinCustomer;
use App\Models\FinSaleExpense;
use App\Models\InvProduct;
use App\Models\Tenant;
use App\Services\Otc\SalesOrderService;
use App\Support\Features;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SalesOrders extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';
    public bool $showForm = false;
    public string $customer_id = '';
    public string $order_date = '';
    public string $notes = '';
    public array $lines = [];

    /** Sərfiyyat */
    public array $sarfiyyatLines = [];
    public bool $sarfiyyat_charge_customer = false;

    public function mount()
    {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        $this->order_date = now()->toDateString();
        $this->sarfiyyat_charge_customer = $this->defaultChargeCustomer();
    }

    private function effectiveTenantId(): ?string
    {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    private function defaultChargeCustomer(): bool
    {
        try {
            return (bool) Features::value('otc.sarfiyyat.charge_customer_default');
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════
    // FORM
    // ═══════════════════════════════════════════════════════
    public function openCreate()
    {
        $this->resetForm();
        $this->lines = [$this->emptyLine()];
        $this->sarfiyyatLines = [];
        $this->showForm = true;
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

    public function productChanged(int $index)
    {
        $tid = $this->effectiveTenantId();
        $pid = $this->lines[$index]['product_id'] ?? null;
        if (!$tid || !$pid) return;

        TenantContext::runAs($tid, function () use ($index, $pid) {
            $p = InvProduct::find($pid);
            if ($p) {
                $this->lines[$index]['unit_price'] = (string) $p->sales_price;
                $this->lines[$index]['vat_rate']   = (string) $p->vat_rate;
            }
        });
    }

    // ═══════════════════════════════════════════════════════
    // SƏRFİYYAT
    // ═══════════════════════════════════════════════════════
    public function addSarfiyyatLine()
    {
        if (!Features::enabled('otc.sarfiyyat')) return;
        $this->sarfiyyatLines[] = ['name' => '', 'amount' => '0'];
    }

    public function removeSarfiyyatLine(int $i)
    {
        unset($this->sarfiyyatLines[$i]);
        $this->sarfiyyatLines = array_values($this->sarfiyyatLines);
    }

    public function toggleSarfiyyatChargeCustomer()
    {
        $this->sarfiyyat_charge_customer = !$this->sarfiyyat_charge_customer;
    }

    public function sarfiyyatTotal(): float
    {
        $sum = 0.0;
        foreach ($this->sarfiyyatLines as $l) {
            $sum += (float) ($l['amount'] ?? 0);
        }
        return $sum;
    }

    // ═══════════════════════════════════════════════════════
    // KAYDET
    // ═══════════════════════════════════════════════════════
    public function save()
    {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        $data = [
            'customer_id' => $this->customer_id,
            'order_date'  => $this->order_date,
            'notes'       => $this->notes,
        ];

        $lines = [];
        foreach ($this->lines as $l) {
            if (empty($l['product_id'])) continue;
            $lines[] = [
                'product_id' => $l['product_id'],
                'quantity'   => (float) $l['quantity'],
                'unit_price' => (float) $l['unit_price'],
                'vat_rate'   => (float) $l['vat_rate'],
                'description'=> null,
            ];
        }

        // ═══ SƏRFİYYAT VALİDASYON ═══
        $sarfEnabled  = Features::enabled('otc.sarfiyyat');
        $sarfRequired = Features::enabled('otc.sarfiyyat.required');

        $sarfLines = [];
        if ($sarfEnabled) {
            foreach ($this->sarfiyyatLines as $sl) {
                $name = trim((string) ($sl['name'] ?? ''));
                $amt  = (float) ($sl['amount'] ?? 0);
                if ($name !== '' && $amt > 0) {
                    $sarfLines[] = ['name' => $name, 'amount' => $amt];
                }
            }

            if ($sarfRequired && empty($sarfLines)) {
                $this->addError('sarfiyyat', 'Bu işletme için en az bir masraf (sərfiyyat) girmek zorunludur.');
                return;
            }
        }

        try {
            /** @var OtcSalesOrder|null $order */
            $order = TenantContext::runAs($tid, fn() => app(SalesOrderService::class)->create($data, $lines));

            // ═══ SƏRFİYYAT KAYDET ═══
            if ($sarfEnabled && !empty($sarfLines)) {
                if (!$order) {
                    \Log::warning('Sarfiyyat: SalesOrderService::create() dönen değer yok, masraflar kaydedilemedi');
                    $this->addError('sarfiyyat_save', 'Satış oluşturuldu ama masraflar kaydedilemedi. Servis dönüş değeri kontrol edin.');
                } else {
                    try {
                        FinSaleExpense::where('sale_order_id', $order->id)->delete();

                        $orderId = $order->id;
                        $sort = 0;
                        foreach ($sarfLines as $sl) {
                            FinSaleExpense::create([
                                'id'            => (string) Str::uuid(),
                                'tenant_id'     => $tid,
                                'sale_order_id' => $orderId,
                                'name'          => $sl['name'],
                                'amount'        => $sl['amount'],
                                'sort_order'    => $sort++,
                            ]);
                        }

                        // Sipariş toplamlarını güncelle (kolonlar varsa)
                        try {
                            $order->update([
                                'sarfiyyat_total'           => array_sum(array_column($sarfLines, 'amount')),
                                'sarfiyyat_charge_customer' => $this->sarfiyyat_charge_customer,
                            ]);
                        } catch (\Throwable $e) {
                            \Log::warning('Sarfiyyat totals update hata: ' . $e->getMessage());
                        }
                    } catch (\Throwable $e) {
                        \Log::error('Sarfiyyat save hata: ' . $e->getMessage());
                        $this->addError('sarfiyyat_save', 'Masraflar kaydedilemedi: ' . $e->getMessage());
                    }
                }
            }

            $this->cancel();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
        }
    }

    public function approve(string $id)
    {
        $tid = $this->effectiveTenantId();
        if ($tid) TenantContext::runAs($tid, fn() => app(SalesOrderService::class)->approve($id));
    }

    public function invoice(string $id)
    {
        $tid = $this->effectiveTenantId();
        if ($tid) {
            try {
                TenantContext::runAs($tid, fn() => app(SalesOrderService::class)->convertToInvoice($id));
            } catch (ValidationException $e) {
                foreach ($e->errors() as $f => $m) $this->addError($f, $m[0]);
            }
        }
    }

    // ═══════════════════════════════════════════════════════
    // YARDIMCI
    // ═══════════════════════════════════════════════════════
    private function emptyLine(): array
    {
        return ['product_id' => '', 'quantity' => '1', 'unit_price' => '0', 'vat_rate' => '20'];
    }

    private function resetForm(): void
    {
        $this->reset(['customer_id', 'notes', 'lines']);
        $this->order_date = now()->toDateString();
        $this->sarfiyyatLines = [];
        $this->sarfiyyat_charge_customer = $this->defaultChargeCustomer();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();

        $orders = []; $customers = []; $products = [];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$orders, &$customers, &$products) {
                $orders = OtcSalesOrder::with('customer')
                    ->orderByDesc('order_date')
                    ->limit(100)
                    ->get()
                    ->toArray();
                $customers = FinCustomer::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray();
                $products  = InvProduct::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray();
            });
        }

        $tenants = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(
                self::NULL_TENANT,
                fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(),
                true
            );
        }

        $sarfEnabled  = Features::enabled('otc.sarfiyyat');
        $sarfRequired = Features::enabled('otc.sarfiyyat.required');

        return view('livewire.otc.sales-orders', compact(
            'orders', 'customers', 'products', 'isSuper', 'tenants',
            'sarfEnabled', 'sarfRequired'
        ))->layout('layouts.app', ['title' => 'Satış Siparişleri']);
    }
}
