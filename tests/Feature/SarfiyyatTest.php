<?php
namespace Tests\Feature;

use App\Models\FinSaleExpense;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Tests\TestCase;

class SarfiyyatTest extends TestCase
{
    public function test_sale_expense_can_be_created_for_a_tenant(): void
    {
        $tenant = $this->makeTenant();
        $saleOrderId = (string) Str::uuid();

        $expense = FinSaleExpense::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenant->id,
            'sale_order_id' => $saleOrderId, 'name' => 'Kargo Masrafı',
            'amount' => 150.00, 'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('fin_sale_expense', [
            'id' => $expense->id, 'tenant_id' => $tenant->id,
            'sale_order_id' => $saleOrderId, 'name' => 'Kargo Masrafı',
        ]);

        $this->asTenant($tenant->id);
        $loaded = TenantContext::runAs(
            $tenant->id,
            fn() => FinSaleExpense::where('sale_order_id', $saleOrderId)->first()
        );
        $this->assertNotNull($loaded);
        $this->assertEquals(150.00, (float) $loaded->amount);
    }
}
