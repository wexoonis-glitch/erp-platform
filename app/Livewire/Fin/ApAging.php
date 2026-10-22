<?php

namespace App\Livewire\Fin;

use App\Models\FinApInvoice;
use App\Models\FinSupplier;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ApAging extends Component
{
    private const NULL_TENANT = '00000000-0000-0000-0000-000000000000';

    public string $selectedTenantId = '';

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
    }

    private function effectiveTenantId(): ?string
    {
        if (TenantContext::isSuperadmin()) return $this->selectedTenantId ?: null;
        return Auth::user()->tenant_id;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $actor = Auth::user();
        $isSuper = $actor->isSuperadmin();
        $tid = $this->effectiveTenantId();

        $rows = [];
        $totals = ['current' => 0, 'd1_30' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd90_plus' => 0, 'total' => 0];

        if ($tid) {
            TenantContext::runAs($tid, function () use (&$rows, &$totals) {
                $suppliers = FinSupplier::orderBy('code')->get();
                $today = now()->startOfDay();

                foreach ($suppliers as $s) {
                    $invoices = FinApInvoice::where('supplier_id', $s->id)
                        ->whereIn('status', ['posted', 'partially_paid'])
                        ->get();

                    if ($invoices->count() === 0) continue;

                    $b = ['current' => 0, 'd1_30' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd90_plus' => 0];

                    foreach ($invoices as $inv) {
                        $remaining = (float) $inv->grand_total - (float) $inv->paid_total;
                        if ($remaining <= 0) continue;

                        $daysOverdue = $inv->due_date->diffInDays($today, false);
                        // daysOverdue > 0 = vadesi geçmiş
                        if ($daysOverdue <= 0) {
                            $b['current'] += $remaining;
                        } elseif ($daysOverdue <= 30) {
                            $b['d1_30'] += $remaining;
                        } elseif ($daysOverdue <= 60) {
                            $b['d31_60'] += $remaining;
                        } elseif ($daysOverdue <= 90) {
                            $b['d61_90'] += $remaining;
                        } else {
                            $b['d90_plus'] += $remaining;
                        }
                    }

                    $total = array_sum($b);
                    if ($total > 0.0001) {
                        $rows[] = [
                            'supplier_id'   => $s->id,
                            'code'          => $s->code,
                            'name'          => $s->name,
                            'current'       => $b['current'],
                            'd1_30'         => $b['d1_30'],
                            'd31_60'        => $b['d31_60'],
                            'd61_90'        => $b['d61_90'],
                            'd90_plus'      => $b['d90_plus'],
                            'total'         => $total,
                        ];
                        foreach ($b as $k => $v) $totals[$k] += $v;
                        $totals['total'] += $total;
                    }
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

        return view('livewire.fin.ap-aging', [
            'title'   => 'AP Aging (Vade Analizi)',
            'rows'    => $rows,
            'totals'  => $totals,
            'isSuper' => $isSuper,
            'tenants' => $tenants,
        ]);
    }
}
