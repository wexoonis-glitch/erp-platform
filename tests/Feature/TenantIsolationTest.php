<?php
namespace Tests\Feature;

use App\Models\FinCustomer;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    public function test_tenant_cannot_see_other_tenant_customers(): void
    {
        $tenantA = $this->makeTenant('tenant-a');
        $tenantB = $this->makeTenant('tenant-b');

        FinCustomer::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantA->id,
            'code' => 'CUST-A1', 'name' => 'Customer A', 'currency' => 'TRY', 'status' => 'active',
        ]);
        FinCustomer::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantB->id,
            'code' => 'CUST-B1', 'name' => 'Customer B', 'currency' => 'TRY', 'status' => 'active',
        ]);

        $this->asTenant($tenantA->id);
        $resultA = TenantContext::runAs($tenantA->id, fn() => FinCustomer::all());
        $this->assertCount(1, $resultA);
        $this->assertEquals('CUST-A1', $resultA->first()->code);

        $this->asTenant($tenantB->id);
        $resultB = TenantContext::runAs($tenantB->id, fn() => FinCustomer::all());
        $this->assertCount(1, $resultB);
        $this->assertEquals('CUST-B1', $resultB->first()->code);
    }
}
