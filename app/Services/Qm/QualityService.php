<?php
namespace App\Services\Qm;
use App\Models\QmInspectionPlan;
use App\Models\QmInspectionCriteria;
use App\Models\QmNcr;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class QualityService {
    public function createPlan(array $data, array $criteria): QmInspectionPlan {
        $tenantId = TenantContext::id();
        if (QmInspectionPlan::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages(['code' => 'Bu plan kodu zaten kullanımda.']);
        }

        return DB::transaction(function () use ($data, $criteria, $tenantId) {
            $plan = QmInspectionPlan::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'product_id' => $data['product_id'], 'code' => $data['code'], 'name' => $data['name'],
                'description' => $data['description'] ?? null, 'status' => 'active',
            ]);
            foreach ($criteria as $i => $c) {
                QmInspectionCriteria::create([
                    'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'plan_id' => $plan->id,
                    'name' => $c['name'], 'expected_value' => $c['expected_value'], 'line_no' => $i + 1,
                ]);
            }
            return $plan->fresh();
        });
    }

    public function createNcr(array $data): QmNcr {
        $tenantId = TenantContext::id();
        return QmNcr::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'product_id' => $data['product_id'], 'ncr_no' => $this->nextNcrNo($tenantId),
            'report_date' => $data['report_date'], 'source' => $data['source'],
            'reference_no' => $data['reference_no'] ?? null, 'quantity' => $data['quantity'],
            'status' => 'open', 'description' => $data['description'] ?? null,
        ]);
    }

    public function updateNcrStatus(string $id, string $status): void {
        QmNcr::findOrFail($id)->update(['status' => $status]);
    }

    private function nextNcrNo(string $tenantId): string {
        $year = date('Y'); $prefix = "NCR-{$year}-";
        $last = QmNcr::where('tenant_id', $tenantId)->where('ncr_no', 'like', $prefix . '%')->orderByDesc('ncr_no')->value('ncr_no');
        $seq = 1;
        if ($last) { $parts = explode('-', $last); $seq = (int) end($parts) + 1; }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
