---
phase: 01-foundation
plan: 4
subsystem: auth
tags: [auth, roles, redirect, inactive-block, middleware, gates, portuguese, security]
dependency_graph:
  requires: [auth-scaffold, user-role-system, admin-layout, client-layout, guest-layout]
  provides: [portuguese-auth-views, role-redirect, inactive-block, role-middleware, role-gates, role-dashboards]
  affects: [01-05, 01-06, 02-all, 03-all, 04-all, 05-all]
tech_stack:
  added: []
  patterns:
    - EnsureUserRole variadic middleware (string ...$roles) registered as 'role' alias
    - Gate::define for access-admin / manage-users / access-financial
    - Auth::logout() on inactive user before session is established (T-04-02 mitigation)
    - Class-based Blade components (AdminLayout, ClientLayout) bridging layouts/admin.blade.php naming
    - lang/pt_BR.json JSON language file for PT auth string overrides
key_files:
  created:
    - resources/views/auth/login.blade.php (replaced — Portuguese card per UI-SPEC)
    - resources/views/auth/forgot-password.blade.php (replaced — PT version)
    - resources/views/auth/reset-password.blade.php (replaced — PT version)
    - resources/views/auth/confirm-password.blade.php (replaced — PT version)
    - lang/pt_BR.json
    - app/Http/Middleware/EnsureUserRole.php
    - app/View/Components/AdminLayout.php
    - app/View/Components/ClientLayout.php
    - resources/views/admin/dashboard.blade.php
    - resources/views/client/dashboard.blade.php
    - tests/Feature/Auth/RoleRedirectTest.php
    - tests/Feature/Auth/InactiveLoginTest.php
  modified:
    - app/Http/Requests/Auth/LoginRequest.php (inactive block + PT error messages)
    - app/Http/Controllers/Auth/AuthenticatedSessionController.php (role-based redirect)
    - app/Providers/AppServiceProvider.php (Gates)
    - bootstrap/app.php (role middleware alias)
    - routes/web.php (admin/portal route groups with role middleware)
    - app/View/Components/GuestLayout.php (now renders Plan 03 navy shell)
    - tests/Feature/Auth/AuthenticationTest.php (redirect assertion updated for role redirect)
decisions:
  - D-11: Inactive user check runs after Auth::attempt succeeds — Auth::logout() fires immediately so no session is persisted (T-04-02)
  - D-12: PT error messages hardcoded in LoginRequest instead of translation keys — avoids trans() key fallback to English on fresh installs
  - D-13: AdminLayout/ClientLayout PHP classes bridge naming gap between components/layouts/*.blade.php (x-layouts.admin) and x-admin-layout convention
  - D-14: GuestLayout class updated to render Plan 03 navy shell (components.layouts.guest) replacing the Breeze default layout
  - D-15: AuthenticationTest updated — default factory user is client role, so redirect is /portal not /dashboard
metrics:
  duration_minutes: 25
  completed_date: "2026-06-27"
  tasks_completed: 3
  files_created: 12
  files_modified: 7
  tests_passed: 32
---

# Phase 1 Plan 4: Auth Experience — Portuguese Views, Role Redirect, Inactive Block, Role Gates Summary

**One-liner:** Portuguese auth views (navy/white card per UI-SPEC), role-based post-login redirect (admin/manager/financial -> /admin, client -> /portal), inactive-user blocking before session creation, EnsureUserRole middleware with `role` alias, three Gates (access-admin, manage-users, access-financial), and 14 new feature tests proving all paths (32 total passing).

## What Was Built

### Task 1: Portuguese Auth Views

All four Breeze auth views replaced with Portuguese versions inside `<x-guest-layout>` (Plan 03 navy shell):

| View | Key PT Copy | Card Styling |
|------|-------------|--------------|
| `auth/login.blade.php` | "Entrar", "Esqueci a senha", "Acesse sua conta" | `w-full max-w-sm bg-white rounded-2xl shadow-2xl p-8` |
| `auth/forgot-password.blade.php` | "Redefinir senha", "Enviar link de redefinição", "Link enviado! Verifique seu e-mail." | Same card |
| `auth/reset-password.blade.php` | "Nova senha", "Confirmar senha", "Redefinir senha" | Same card |
| `auth/confirm-password.blade.php` | "Confirme sua senha", "Confirmar" | Same card |

`lang/pt_BR.json` created with overrides for Laravel auth/validation string keys including:
- `"These credentials do not match our records."` -> `"E-mail ou senha incorretos."`
- `"Too many login attempts..."` -> PT throttle message
- Validation required/email/min/confirmation messages in Portuguese

### Task 2: Role-Based Redirect and Inactive-User Blocking

**Role redirect map (AUTH-01):**

| Role | Post-login destination | Route name |
|------|----------------------|------------|
| `admin` | `/admin` | `admin.dashboard` |
| `manager` | `/admin` | `admin.dashboard` |
| `financial` | `/admin` | `admin.dashboard` |
| `client` | `/portal` | `portal.dashboard` |

**Inactive-user blocking flow (AUTH-04 / T-04-02):**

`LoginRequest::authenticate()` sequence:
1. `ensureIsNotRateLimited()` — throttle guard
2. `Auth::attempt(credentials)` — try credentials
3. Fail -> `RateLimiter::hit()` + throw `"E-mail ou senha incorretos."`
4. Succeed but `active === false` -> `Auth::logout()` + `RateLimiter::hit()` + throw `"Sua conta está inativa. Entre em contato com o administrador."`
5. Succeed and active -> `RateLimiter::clear()` -> controller proceeds

No session is ever established for an inactive user — `Auth::logout()` fires before the controller returns, and `assertGuest()` in `InactiveLoginTest` confirms this.

**Dashboard views:**
- `resources/views/admin/dashboard.blade.php` — `<x-admin-layout>` wrapper, "Dashboard" heading (Phase 2 fills KPIs)
- `resources/views/client/dashboard.blade.php` — `<x-client-layout>` wrapper, "Minhas Obras" heading (Phase 4 fills projects)

**Legacy `/dashboard` route:** Redirects by role to keep Breeze profile links working.

### Task 3: EnsureUserRole Middleware + Gates + Route Groups (SEC-01)

**EnsureUserRole middleware** (`app/Http/Middleware/EnsureUserRole.php`):

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (! auth()->check() || ! in_array(auth()->user()->role, $roles, true)) {
        abort(403);
    }
    return $next($request);
}
```

Registered as `'role'` alias in `bootstrap/app.php` `->withMiddleware()`.

**Route groups:**

| Group | Middleware | Route |
|-------|-----------|-------|
| Admin | `auth`, `role:admin,manager,financial` | `GET /admin` -> `admin.dashboard` |
| Portal | `auth`, `role:client` | `GET /portal` -> `portal.dashboard` |

Guest hitting either route redirects to `/login`. Wrong role returns 403.

**Gates defined in `AppServiceProvider::boot()`:**

| Gate | Allowed roles | Use case |
|------|--------------|----------|
| `access-admin` | admin, manager, financial | Can enter admin panel |
| `manage-users` | admin only | Can create/edit/delete users (Plan 05) |
| `access-financial` | admin, financial | Can view financial reports (Phase 5) |

### Test Coverage

| Test class | Tests | Coverage |
|-----------|-------|----------|
| `RoleRedirectTest` | 10 | Admin/manager/financial -> /admin, client -> /portal, 403 cross-role, guest -> /login |
| `InactiveLoginTest` | 4 | Inactive blocked (assertGuest), PT error message, active login, wrong password |
| `AuthenticationTest` (Breeze, updated) | 4 | Login renders, portal.dashboard redirect, wrong password, logout |
| Other Breeze tests | 14 | Password reset, email verification, profile, etc. |
| **Total** | **32** | **32/32 passed** |

## Security Coverage (Threat Model)

| Threat ID | Category | Status |
|-----------|----------|--------|
| T-04-01 | Elevation of Privilege (cross-role access) | Mitigated — `role:admin,manager,financial` on /admin; client -> 403 (tested) |
| T-04-02 | Spoofing (inactive account login) | Mitigated — `Auth::logout()` before controller return; `assertGuest()` confirmed |
| T-04-03 | Information Disclosure (auth errors) | Mitigated — generic "E-mail ou senha incorretos." hides whether email exists |
| T-04-04 | Denial of Service (brute force) | Mitigated — Breeze RateLimiter retained; inactive block also hits rate limiter |
| T-04-05 | Tampering (reset token) | Mitigated — Breeze signed expiring tokens unchanged |

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] AdminLayout/ClientLayout class-based components required for x-admin-layout naming**

- **Found during:** Task 2 GREEN phase (500 error in tests: "Unable to locate a class or view for component [admin-layout]")
- **Issue:** `resources/views/components/layouts/admin.blade.php` resolves as `<x-layouts.admin>` in Laravel anonymous component naming — not `<x-admin-layout>`. The plan specified `<x-admin-layout>`.
- **Fix:** Created `app/View/Components/AdminLayout.php` and `app/View/Components/ClientLayout.php`, mirroring how Breeze created `GuestLayout.php`. Both delegate rendering to the Plan 03 layout files.
- **Files modified:** `app/View/Components/AdminLayout.php` (new), `app/View/Components/ClientLayout.php` (new)
- **Commit:** aca00c6

**2. [Rule 1 - Bug] Updated AuthenticationTest redirect assertion for role-based routing**

- **Found during:** Task 2 GREEN verification (1 test failure: expected `/dashboard`, got `/portal`)
- **Issue:** Breeze's `test_users_can_authenticate_using_the_login_screen` asserted redirect to `/dashboard`. Default factory user has `role=client`, so after role-redirect implementation they go to `/portal`.
- **Fix:** Updated assertion to `route('portal.dashboard')` with explanatory comment.
- **Files modified:** `tests/Feature/Auth/AuthenticationTest.php`
- **Commit:** aca00c6

**3. [Rule 1 - Bug] GuestLayout updated to render Plan 03 navy shell**

- **Found during:** Task 1 implementation review — `<x-guest-layout>` resolves to `App\View\Components\GuestLayout` class which still pointed to `layouts/guest.blade.php` (old Breeze layout), not the Plan 03 navy shell at `components/layouts/guest.blade.php`.
- **Fix:** Updated `GuestLayout::render()` to return `view('components.layouts.guest')`.
- **Files modified:** `app/View/Components/GuestLayout.php`
- **Commit:** aca00c6

## Known Stubs

| View | Stub | Resolved In |
|------|------|-------------|
| `resources/views/admin/dashboard.blade.php` | Placeholder "Dashboard" heading — no KPI data | Phase 2 plans |
| `resources/views/client/dashboard.blade.php` | Placeholder "Minhas Obras" heading — no project list | Phase 4 plans |

These stubs do not prevent the plan's goal (auth + role routing) from being achieved. The dashboards serve as valid redirect landing targets.

## Threat Flags

None — no new network endpoints or trust boundaries beyond what the plan's threat model already covers. The `/admin` and `/portal` routes are explicitly included in T-04-01.

## Self-Check: PASSED

- resources/views/auth/login.blade.php contains `x-guest-layout`, `Entrar`, `Esqueci a senha`, `Acesse sua conta`, `bg-green-600`, `rounded-2xl`: FOUND
- resources/views/auth/forgot-password.blade.php contains `Enviar link de redefinição`: FOUND
- resources/views/auth/reset-password.blade.php contains `Redefinir senha`: FOUND
- lang/pt_BR.json contains `E-mail ou senha incorretos.`: FOUND
- app/Http/Requests/Auth/LoginRequest.php contains `active`, `Sua conta está inativa`: FOUND
- app/Http/Controllers/Auth/AuthenticatedSessionController.php contains `portal.dashboard`, `admin.dashboard`: FOUND
- app/Http/Middleware/EnsureUserRole.php contains `abort(403`, `string ...`: FOUND
- bootstrap/app.php contains `EnsureUserRole::class` alias: FOUND
- app/Providers/AppServiceProvider.php contains `Gate::define('manage-users'`: FOUND
- routes/web.php contains `role:admin`, `role:client`, `admin.dashboard`, `portal.dashboard`: FOUND
- resources/views/admin/dashboard.blade.php contains `x-admin-layout`: FOUND
- resources/views/client/dashboard.blade.php contains `x-client-layout`: FOUND
- Commits 453b956 (Task 1 views), 7abb9bf (RED tests), aca00c6 (Task 2 GREEN), 03ada79 (Task 3 Gates): ALL FOUND
- 32/32 auth tests passed: VERIFIED
