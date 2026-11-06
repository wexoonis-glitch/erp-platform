<?php
namespace App\Livewire\Prc;
use App\Models\FinApInvoice;
use App\Models\PrcGoodsReceipt;
use App\Models\Tenant;
use App\Services\Fin\ApInvoiceService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ThreeWayMatch extends Component {
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';
    public string $selectedTenantId = '';
    public string $message = '';
    public string $messageType = 'success';
    public bool $showWarnings = false;
    public array $matchWarnings = [];
    public ?string $createdInvoiceNo = null;

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

    public function closeWarnings() {
        $this->showWarnings = false;
        $this->matchWarnings = [];
        $this->createdInvoiceNo = null;
    }

    public function convert(string $grId) {
        $tid = $this->effectiveTenantId();
        if (!$tid) return;

        $this->message = '';
        $this->messageType = 'success';
        $this->matchWarnings = [];
        $this->createdInvoiceNo = null;

        try {
            $result = null;
            TenantContext::runAs($tid, function () use ($grId, &$result) {
                $result = app(ApInvoiceService::class)->createFromGoodsReceipt($grId);
            });

            $invoice = $result['invoice'];
            $issues = $result['match_issues'] ?? [];

            $this->createdInvoiceNo = $invoice->invoice_no;
            $this->message = "AP Faturası oluşturuldu: {$invoice->invoice_no} (draft). Finans → Tedarikçi Faturaları'ndan post edebilirsiniz.";

            if (!empty($issues)) {
                $this->matchWarnings = $issues;
                $this->showWarnings = true;
            }
        } catch (ValidationException $e) {
            $this->messageType = 'error';
            $this->message = collect($e->errors())->flatten()->first() ?? 'İşlem başarısız.';
        }
    }

    #[Layout('layouts.app')]
    public function render() {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $receipts = [];
        if ($tid) {
            TenantContext::runAs($tid, function () use (&$receipts) {
                $rows = PrcGoodsReceipt::with(['purchaseOrder.supplier', 'lines'])
                    ->where('status', 'posted')
                    ->orderByDesc('receipt_date')
                    ->limit(100)
                    ->get();

                $invoicedGrIds = FinApInvoice::whereNotNull('goods_receipt_id')->pluck('goods_receipt_id')->toArray();

                foreach ($rows as $gr) {
                    $totalQty = $gr->lines->sum(fn($l) => (float) $l->quantity);
                    $totalCost = $gr->lines->sum(fn($l) => (float) $l->quantity * (float) $l->unit_cost);
                    $receipts[] = [
                        'id' => $gr->id,
                        'gr_no' => $gr->gr_no,
                        'po_no' => $gr->purchaseOrder?->po_no ?? '—',
                        'supplier' => $gr->purchaseOrder?->supplier?->name ?? '—',
                        'receipt_date' => $gr->receipt_date?->toDateString() ?? '—',
                        'total_qty' => $totalQty,
                        'total_cost' => $totalCost,
                        'invoiced' => in_array($gr->id, $invoicedGrIds, true),
                    ];
                }
            });
        }

        $tenants = [];
        if ($isSuper) $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);

        return view('livewire.prc.three-way-match', compact('receipts', 'isSuper', 'tenants'))->layout('layouts.app', ['title' => '3-Way Match']);
    }
}
