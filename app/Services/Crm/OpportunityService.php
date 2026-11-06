<?php
namespace App\Services\Crm;
use App\Models\CrmOpportunity;
use App\Models\FinCustomer;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class OpportunityService {
    public function create(array $data): CrmOpportunity {
        $tenantId = TenantContext::id();
        return CrmOpportunity::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'name' => $data['name'], 'amount' => $data['amount'] ?? 0,
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'stage' => $data['stage'] ?? 'qualification', 'probability' => $data['probability'] ?? 10,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function updateStage(string $id, string $stage): void {
        $opp = CrmOpportunity::findOrFail($id);
        $probabilities = [
            'qualification' => 10, 'proposal' => 30, 'negotiation' => 60, 'closed_won' => 100, 'closed_lost' => 0
        ];
        $opp->update(['stage' => $stage, 'probability' => $probabilities[$stage] ?? $opp->probability]);
    }
}
