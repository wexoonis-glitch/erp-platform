<?php
namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_authenticated_user_session_works(): void
    {
        $tenant = $this->makeTenant();
        $user   = $this->makeUser($tenant->id, 'admin');

        $this->actingAs($user);

        $this->assertAuthenticatedAs($user);
        $this->assertEquals($user->id, auth()->id());
        $this->assertEquals($tenant->id, auth()->user()->tenant_id);
    }

    public function test_logout_route_redirects_to_login(): void
    {
        $tenant = $this->makeTenant();
        $user   = $this->makeUser($tenant->id, 'user');

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect();
    }
}
