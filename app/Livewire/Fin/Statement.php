<?php

namespace App\Livewire\Fin;

use App\Models\FinCustomer;
use App\Models\FinSupplier;
use App\Models\FinArInvoice;
use App\Models\FinApInvoice;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class Statement extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    #[Url]
    public string $selectedTenantId = '';

    #[Url]
    public string $type = 'customer'; // customer|supplier

    #[Url]
    public string $entityId = '';

    #[Url]
    public string $startDate = '';

    #[Url]
    public string $endDate = '';

    public function mount()
    {
        $actor = Auth::user();
        if ($actor->isSuperadmin()) {
            $first = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->first(), true);
            $this->selectedTenantId = $first?->id ?? '';
        } else {
            $this->selectedTenantId = $actor->tenant_id ?? '';
        }
        if (!$this->startDate) $this->startDate = now()->startOfYear()->toDateString();
        if (!$this->endDate) $this->endDate = now()->toDateString();
    }

    private function effectiveTenantId(): ?string
    {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $isSuper = Auth::user()->isSuperadmin();
        $tid = $this->effectiveTenantId();
        $entities = [];
        $rows = [];
        $balance = 0;

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$entities, &$rows, &$balance) {
                if ($this->type === 'customer') {
                    $entities = FinCustomer::orderBy('code')->get(['id', 'code', 'name'])->toArray();
                    if ($this->entityId) {
                        $invoices = FinArInvoice::where('customer_id', $this->entityId)
                            ->whereBetween('invoice_date', [$this->startDate, $this->endDate])
                            ->where('status', '!=', 'cancelled')
                            ->orderBy('invoice_date')
                            ->get();
                        foreach ($invoices as $inv) {
                            $rows[] = ['date' => $inv->invoice_date->format('d.m.Y'), 'desc' => "Satış Faturası: {$inv->invoice_no}", 'debit' => (float) $inv->grand_total, 'credit' => 0];
                            $balance += (float) $inv->grand_total;
                            if ($inv->paid_total > 0) {
                                $rows[] = ['date' => $inv->invoice_date->format('d.m.Y'), 'desc' => "Tahsilat: {$inv->invoice_no}", 'debit' => 0, 'credit' => (float) $inv->paid_total];
                                $balance -= (float) $inv->paid_total;
                            }
                        }
                    }
                } else {
                    $entities = FinSupplier::orderBy('code')->get(['id', 'code', 'name'])->toArray();
                    if ($this->entityId) {
                        $invoices = FinApInvoice::where('supplier_id', $this->entityId)
                            ->whereBetween('invoice_date', [$this->startDate, $this->endDate])
                            ->where('status', '!=', 'cancelled')
                            ->orderBy('invoice_date')
                            ->get();
                        foreach ($invoices as $inv) {
                            $rows[] = ['date' => $inv->invoice_date->format('d.m.Y'), 'desc' => "Alış Faturası: {$inv->invoice_no}", 'debit' => 0, 'credit' => (float) $inv->grand_total];
                            $balance += (float) $inv->grand_total;
                            if ($inv->paid_total > 0) {
                                $rows[] = ['date' => $inv->invoice_date->format('d.m.Y'), 'desc' => "Ödeme: {$inv->invoice_no}", 'debit' => (float) $inv->paid_total, 'credit' => 0];
                                $balance -= (float) $inv->paid_total;
                            }
                        }
                    }
                }
            });
        }

        $tenants = [];
        if ($isSuper) {
            $tenants = TenantContext::runAs(self::NULL_TENANT, fn() => Tenant::where('status', 'active')->orderBy('code')->get(['id', 'code', 'name'])->toArray(), true);
        }

        return view('livewire.fin.statement', compact('entities', 'rows', 'balance', 'isSuper', 'tenants'))
            ->layout('layouts.app', ['title' => 'Cari Ekstre']);
    }
}
