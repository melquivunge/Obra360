<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Defines application-level Gates for role-based access (SEC-01 / D-16).
     */
    public function boot(): void
    {
        // Grants access to the admin panel (admin, manager, financial roles).
        Gate::define('access-admin', fn (User $u) => in_array($u->role, [
            User::ROLE_ADMIN,
            User::ROLE_MANAGER,
            User::ROLE_FINANCIAL,
        ], true));

        // Grants user management capabilities (admin only).
        Gate::define('manage-users', fn (User $u) => $u->isAdmin());

        // Grants access to financial data (admin and financial roles).
        Gate::define('access-financial', fn (User $u) => in_array($u->role, [
            User::ROLE_ADMIN,
            User::ROLE_FINANCIAL,
        ], true));
    }
}
