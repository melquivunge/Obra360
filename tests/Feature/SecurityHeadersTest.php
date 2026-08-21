<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_headers_are_present_on_an_authenticated_page(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
    }

    public function test_baseline_headers_are_present_on_the_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_hsts_is_not_sent_over_plain_http(): void
    {
        $this->get(route('login'))->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_sent_over_https(): void
    {
        $response = $this->get('https://localhost/login');

        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
    }

    public function test_client_message_posting_is_rate_limited(): void
    {
        $route = app('router')->getRoutes()->getByName('portal.projects.messages.store');

        $this->assertContains('throttle:20,1', $route->gatherMiddleware());
    }
}
