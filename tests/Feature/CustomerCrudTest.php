<?php
namespace Tests\Feature;

use App\Models\FinCustomer;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    public function test_customer_full_crud_lifecycle(): void
    {
        $tenant = $this->makeTenant();

        // CREATE (tenant context)
        $this->asTenant($tenant->id);
        $created = TenantContext::runAs($tenant->id, fn() => FinCustomer::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenant->id,
            'code' => 'TEST-001', 'name' => 'Initial Name',
            'email' => 'initial@test.local', 'currency' => 'TRY', 'status' => 'active',
        ]));

        // Assertion için superadmin context geri yükle
        $this->asSuperadmin();
        $this->assertDatabaseHas('fin_customer', ['id' => $created->id, 'name' => 'Initial Name']);

        // READ (tenant context)
        $this->asTenant($tenant->id);
        $found = TenantContext::runAs($tenant->id, fn() => FinCustomer::find($created->id));
        $this->assertNotNull($found);
        $this->assertEquals('TEST-001', $found->code);

        // UPDATE
        TenantContext::runAs($tenant->id, function () use ($created) {
            $c = FinCustomer::find($created->id);
            $c->name = 'Updated Name';
            $c->save();
        });
        $this->assertEquals(
            'Updated Name',
            TenantContext::runAs($tenant->id, fn() => FinCustomer::find($created->id))->name
        );

        // DELETE
        TenantContext::runAs($tenant->id, fn() => FinCustomer::find($created->id)->delete());

        // Assertion için superadmin context
        $this->asSuperadmin();
        $this->assertDatabaseMissing('fin_customer', ['id' => $created->id]);
    }
}
