<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function adminUser(): User
    {
        return User::factory()->admin()->create();
    }

    private function managerUser(): User
    {
        return User::factory()->manager()->create();
    }

    private function validCreatePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'phone' => '11999990000',
            'role' => User::ROLE_MANAGER,
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // 1. Admin can create a user
    // -------------------------------------------------------------------------

    public function test_admin_can_create_user_with_valid_data(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->validCreatePayload())
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'joao@example.com',
            'role' => User::ROLE_MANAGER,
        ]);
    }

    // -------------------------------------------------------------------------
    // 2. Created user's password is hashed
    // -------------------------------------------------------------------------

    public function test_created_user_password_is_hashed(): void
    {
        $admin = $this->adminUser();
        $plain = 'secret1234';

        $this->actingAs($admin)->post(route('users.store'), $this->validCreatePayload([
            'password' => $plain,
            'password_confirmation' => $plain,
        ]));

        $user = User::where('email', 'joao@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEquals($plain, $user->password);
        $this->assertTrue(Hash::check($plain, $user->password));
    }

    // -------------------------------------------------------------------------
    // 3. Invalid role is rejected
    // -------------------------------------------------------------------------

    public function test_create_with_invalid_role_fails_validation(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->validCreatePayload(['role' => 'superuser']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'joao@example.com']);
    }

    // -------------------------------------------------------------------------
    // 4. Duplicate email is rejected
    // -------------------------------------------------------------------------

    public function test_create_with_duplicate_email_fails_validation(): void
    {
        $admin = $this->adminUser();
        User::factory()->create(['email' => 'joao@example.com']);

        $this->actingAs($admin)
            ->post(route('users.store'), $this->validCreatePayload())
            ->assertSessionHasErrors('email');
    }

    // -------------------------------------------------------------------------
    // 5. Admin can update a user's name and role
    // -------------------------------------------------------------------------

    public function test_admin_can_update_user_name_and_role(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['name' => 'Old Name', 'role' => User::ROLE_CLIENT]);

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'name' => 'New Name',
                'email' => $user->email,
                'role' => User::ROLE_FINANCIAL,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'role' => User::ROLE_FINANCIAL,
        ]);
    }

    // -------------------------------------------------------------------------
    // 6. Toggle-active flips the active flag
    // -------------------------------------------------------------------------

    public function test_toggle_active_flips_active_flag(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($admin)
            ->post(route('users.toggle-active', $user))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'active' => false]);

        // Flip back
        $this->actingAs($admin)
            ->post(route('users.toggle-active', $user));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'active' => true]);
    }

    // -------------------------------------------------------------------------
    // 7. Delete soft-deletes the user
    // -------------------------------------------------------------------------

    public function test_delete_soft_deletes_user(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $found = User::withTrashed()->find($user->id);
        $this->assertNotNull($found);
        $this->assertNotNull($found->deleted_at);
    }

    // -------------------------------------------------------------------------
    // 8. Access control: non-admin gets 403, guest redirected to /login
    // -------------------------------------------------------------------------

    public function test_non_admin_manager_gets_403_on_user_list(): void
    {
        $manager = $this->managerUser();

        $this->actingAs($manager)
            ->get(route('users.index'))
            ->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_from_user_list(): void
    {
        $this->get(route('users.index'))
            ->assertRedirect(route('login'));
    }
}
