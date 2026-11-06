<?php
namespace App\Services\Fin;
use App\Models\FinBudget;
use App\Models\FinCostCenter;
use App\Models\HrExpenseClaim;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BudgetService {
    /**
     * Bir masraf merkezinin belirli bir yıl için aylık bütçesini kaydeder.
     * $months = ['01' => 10000, '02' => 12000, ...]
     */
    public function setBudget(string $costCenterId, string $year, array $months): void {
        $tenantId = TenantContext::id();
        if (!$tenantId) return;

        DB::transaction(function () use ($tenantId, $costCenterId, $year, $months) {
            foreach ($months as $month => $amount) {
                $month = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
                if ($amount === null || $amount === '') continue;

                FinBudget::updateOrCreate(
                    ['tenant_id' => $tenantId, 'cost_center_id' => $costCenterId, 'year' => $year, 'month' => $month],
                    ['id' => (string) Str::uuid(), 'planned_amount' => (float) $amount]
                );
            }
        });
    }

    /**
     * Yıl için planlanan bütçeyi döner: [cost_center_id => [month => amount, ..., 'total' => X]]
     */
    public function getPlannedGrid(string $year): array {
        $tenantId = TenantContext::id();
        $rows = FinBudget::where('year', $year)->get();

        $grid = [];
        foreach ($rows as $r) {
            $grid[$r->cost_center_id][$r->month] = (float) $r->planned_amount;
            $grid[$r->cost_center_id]['total'] = ($grid[$r->cost_center_id]['total'] ?? 0) + (float) $r->planned_amount;
        }
        return $grid;
    }

    /**
     * Yıl için gerçekleşen harcamayı döner: [cost_center_id => [month => amount, ..., 'total' => X]]
     * Kaynak: ödenmiş İK masraf talepleri (personel.masraf_merkezi üzerinden)
     */
    public function getActualGrid(string $year): array {
        $tenantId = TenantContext::id();
        if (!$tenantId) return [];

        $rows = DB::table('hr_expense_claim as c')
            ->join('hr_employee as e', 'e.id', '=', 'c.employee_id')
            ->where('c.tenant_id', $tenantId)
            ->where('c.status', 'paid')
            ->whereRaw("EXTRACT(YEAR FROM c.expense_date) = ?", [$year])
            ->whereNotNull('e.cost_center_id')
            ->selectRaw("e.cost_center_id, LPAD(EXTRACT(MONTH FROM c.expense_date)::text, 2, '0') as month, SUM(c.amount) as total")
            ->groupBy('e.cost_center_id', DB::raw("LPAD(EXTRACT(MONTH FROM c.expense_date)::text, 2, '0')"))
            ->get();

        $grid = [];
        foreach ($rows as $r) {
            $grid[$r->cost_center_id][$r->month] = (float) $r->total;
            $grid[$r->cost_center_id]['total'] = ($grid[$r->cost_center_id]['total'] ?? 0) + (float) $r->total;
        }
        return $grid;
    }

    /**
     * Bütçe vs Gerçekleşen karşılaştırma raporu.
     */
    public function getVarianceReport(string $year): array {
        $centers = FinCostCenter::where('status', 'active')->orderBy('code')->get();
        $planned = $this->getPlannedGrid($year);
        $actual = $this->getActualGrid($year);

        $result = [];
        $grandPlanned = 0; $grandActual = 0;

        foreach ($centers as $cc) {
            $p = (float) ($planned[$cc->id]['total'] ?? 0);
            $a = (float) ($actual[$cc->id]['total'] ?? 0);
            $variance = $p - $a;
            $pct = $p > 0 ? ($a / $p) * 100 : ($a > 0 ? 100 : 0);

            if ($p == 0 && $a == 0) continue; // Boş satırları atla

            $result[] = [
                'cost_center_id' => $cc->id,
                'code' => $cc->code,
                'name' => $cc->name,
                'planned' => $p,
                'actual' => $a,
                'variance' => $variance,
                'variance_pct' => round($pct, 1),
                'status' => $variance >= 0 ? 'under' : 'over',
            ];
            $grandPlanned += $p;
            $grandActual += $a;
        }

        return [
            'rows' => $result,
            'grand_planned' => $grandPlanned,
            'grand_actual' => $grandActual,
            'grand_variance' => $grandPlanned - $grandActual,
            'year' => $year,
        ];
    }
}
