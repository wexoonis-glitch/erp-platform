<?php
namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asSuperadmin();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        $this->app->forgetInstance(\App\Services\Platform\FeatureFlagService::class);
        parent::tearDown();
    }

    protected function asSuperadmin(): void
    {
        DB::statement("SELECT set_config('app.is_superadmin', 'true', false)");
        DB::statement("SELECT set_config('app.tenant_id', '', false)");
    }

    protected function asTenant(string $tenantId): void
    {
        DB::statement("SELECT set_config('app.is_superadmin', 'false', false)");
        DB::statement("SELECT set_config('app.tenant_id', ?, false)", [$tenantId]);
    }

    protected function makeTenant(?string $code = null): Tenant
    {
        return Tenant::create([
            'id'     => (string) Str::uuid(),
            'code'   => $code ?? 't-' . Str::random(6),
            'name'   => 'Test Tenant ' . Str::random(4),
            'status' => 'active',
        ]);
    }

    protected function makeUser(?string $tenantId, string $role = 'user'): User
    {
        return User::create([
            'name'      => 'Test User ' . Str::random(4),
            'email'     => 'u-' . Str::random(8) . '@test.local',
            'password'  => Hash::make('password'),
            'role'      => $role,
            'tenant_id' => $tenantId,
            'status'    => 'active',
        ]);
    }
}
