<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_redirected_to_admin_dashboard_after_login(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_dashboard_returns_200_for_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_manager_is_redirected_to_admin_dashboard_after_login(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->post('/login', [
            'email' => $manager->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_financial_is_redirected_to_admin_dashboard_after_login(): void
    {
        $financial = User::factory()->financial()->create();

        $response = $this->post('/login', [
            'email' => $financial->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_client_is_redirected_to_portal_dashboard_after_login(): void
    {
        $client = User::factory()->create(); // default role is client

        $response = $this->post('/login', [
            'email' => $client->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('portal.dashboard'));
    }

    public function test_client_dashboard_returns_200_for_client(): void
    {
        $client = User::factory()->create();

        $response = $this->actingAs($client)->get('/portal');

        $response->assertStatus(200);
    }

    public function test_client_hitting_admin_route_receives_403(): void
    {
        $client = User::factory()->create();

        $response = $this->actingAs($client)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_admin_hitting_portal_route_receives_403(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/portal');

        $response->assertStatus(403);
    }

    public function test_guest_hitting_admin_route_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_guest_hitting_portal_route_is_redirected_to_login(): void
    {
        $response = $this->get('/portal');

        $response->assertRedirect('/login');
    }
}
