<?php
namespace App\Livewire\Otc;
use App\Models\FinArInvoice;
use App\Models\OtcSalesOrder;
use App\Models\Tenant;
use App\Services\Fin\ArInvoiceService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ConvertToInvoice extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
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

    public function convert(string $soId, bool $postAfter = false) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        $this->message = '';
        try {
            $invoice = null;
            TenantContext::runAs($tid, function () use ($soId, $postAfter, &$invoice) {
                $svc = app(ArInvoiceService::class);
                $invoice = $svc->createFromSalesOrder($soId);
                if ($postAfter) $svc->post($invoice->id);
            });
            $this->messageType = 'success';
            $this->message = "Fatura oluşturuldu: {$invoice->invoice_no}" . ($postAfter ? ' (post edildi)' : ' (draft)');
        } catch (ValidationException $e) {
            $this->messageType = 'error';
            $this->message = $e->errors() ? collect($e->errors())->flatten()->first() : 'İşlem başarısız.';
        }
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $orders = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$orders) {
                $invoicedSoIds = FinArInvoice::whereNotNull('sales_order_id')->pluck('sales_order_id')->toArray();

                $orders = OtcSalesOrder::with('customer')
                    ->whereIn('status', ['approved', 'confirmed', 'processing'])
                    ->whereNotIn('id', $invoicedSoIds)
                    ->orderByDesc('order_date')
                    ->limit(100)
                    ->get()
                    ->map(fn($o) => [
                        'id'          => $o->id,
                        'so_no'       => $o->so_no,
                        'customer'    => $o->customer?->name ?? '—',
                        'order_date'  => $o->order_date?->toDateString() ?? '—',
                        'grand_total' => (float) $o->grand_total,
                        'currency'    => $o->currency ?? 'TRY',
                        'status'      => $o->status,
                    ])
                    ->toArray();
            });
        }
        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        return view('livewire.otc.convert-to-invoice', compact('orders', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => 'Siparişten Faturaya']);
    }
}
