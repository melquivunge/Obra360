<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['active' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_user_sees_portuguese_inactive_error_message(): void
    {
        $user = User::factory()->create(['active' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $errors = session('errors');
        $this->assertStringContainsString('Sua conta está inativa', $errors->first('email'));
    }

    public function test_active_user_can_login_successfully(): void
    {
        $user = User::factory()->create(['active' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_wrong_password_shows_credentials_error_and_remains_guest(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }
}
