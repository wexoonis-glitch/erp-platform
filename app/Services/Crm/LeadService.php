<?php
namespace App\Services\Crm;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\FinCustomer;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LeadService {
    public function create(array $data): CrmLead {
        $tenantId = TenantContext::id();
        return CrmLead::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
            'name' => $data['name'], 'company' => $data['company'] ?? null,
            'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null,
            'source' => $data['source'] ?? null, 'status' => 'new', 'notes' => $data['notes'] ?? null,
        ]);
    }

    public function updateStatus(string $id, string $status): void {
        $lead = CrmLead::findOrFail($id);
        $lead->update(['status' => $status]);
    }

    public function convertToOpportunity(string $id, array $data): CrmOpportunity {
        $tenantId = TenantContext::id();
        $lead = CrmLead::findOrFail($id);
        
        return DB::transaction(function () use ($lead, $data, $tenantId) {
            $lead->update(['status' => 'qualified']);

            return CrmOpportunity::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenantId,
                'name' => $data['name'], 'amount' => $data['amount'] ?? 0,
                'expected_close_date' => $data['expected_close_date'] ?? null,
                'stage' => 'qualification', 'probability' => 10,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
