<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordVisibilityToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders_the_password_visibility_toggle(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('aria-label="Mostrar senha"', false);
    }

    public function test_login_password_field_keeps_its_name_id_and_autocomplete(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('name="password"', false);
        $response->assertSee('id="password"', false);
        $response->assertSee('autocomplete="current-password"', false);
        $response->assertSee('type="password"', false);
    }
}
