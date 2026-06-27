<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_constants_are_defined(): void
    {
        $this->assertSame('admin', User::ROLE_ADMIN);
        $this->assertSame('manager', User::ROLE_MANAGER);
        $this->assertSame('financial', User::ROLE_FINANCIAL);
        $this->assertSame('client', User::ROLE_CLIENT);
    }

    public function test_user_with_admin_role_returns_true_from_is_admin_and_false_from_is_client(): void
    {
        $user = new User;
        $user->role = 'admin';

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isClient());
    }

    public function test_user_with_manager_role_returns_true_from_is_manager(): void
    {
        $user = new User;
        $user->role = 'manager';

        $this->assertTrue($user->isManager());
    }

    public function test_user_with_financial_role_returns_true_from_is_financial(): void
    {
        $user = new User;
        $user->role = 'financial';

        $this->assertTrue($user->isFinancial());
    }

    public function test_user_with_client_role_returns_true_from_is_client(): void
    {
        $user = new User;
        $user->role = 'client';

        $this->assertTrue($user->isClient());
    }

    public function test_deleting_a_user_soft_deletes_it(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertNull(User::find($userId));
        $this->assertNotNull(User::withTrashed()->find($userId));
        $this->assertNotNull(User::withTrashed()->find($userId)->deleted_at);
    }
}
