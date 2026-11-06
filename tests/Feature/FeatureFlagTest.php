<?php
namespace Tests\Feature;

use App\Models\SysTenantFeature;
use App\Support\Features;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    public function test_flag_defaults_to_true_for_new_tenant(): void
    {
        $tenant = $this->makeTenant();
        $this->asTenant($tenant->id);
        $result = TenantContext::runAs($tenant->id, fn() => Features::enabled('otc.sarfiyyat'));
        $this->assertTrue($result);
    }

    public function test_tenant_override_disables_flag(): void
    {
        $tenant = $this->makeTenant();

        SysTenantFeature::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenant->id,
            'feature_key' => 'otc.sarfiyyat', 'enabled' => false, 'config' => null,
        ]);

        $this->app->forgetInstance(\App\Services\Platform\FeatureFlagService::class);

        $this->asTenant($tenant->id);
        $result = TenantContext::runAs($tenant->id, fn() => Features::enabled('otc.sarfiyyat'));
        $this->assertFalse($result);
    }
}
