<?php
namespace App\Services\Fin;
use App\Models\FinAccountingPeriod;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PeriodClosingService {
    public function toggleStatus(string $year, string $month): void {
        $tenantId = TenantContext::id();
        
        $period = FinAccountingPeriod::where('year', $year)->where('month', $month)->first();

        if ($period) {
            if ($period->status === 'closed') {
                // Geri açma işlemi (Sadece superadmin veya yetkili bunu yapabilmeli, şimdilik herkes)
                $period->update(['status' => 'open', 'closed_at' => null]);
            } else {
                $period->update(['status' => 'closed', 'closed_at' => now()]);
            }
        } else {
            FinAccountingPeriod::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'year' => $year, 'month' => $month, 'status' => 'closed', 'closed_at' => now(),
            ]);
        }
    }
}
