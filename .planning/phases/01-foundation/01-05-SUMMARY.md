---
phase: 01-foundation
plan: 5
subsystem: user-management
tags: [crud, controller, policy, form-requests, blade-views, tdd, admin-only, portuguese]
dependency_graph:
  requires: [user-role-system, admin-layout, badge-component, data-table-component, role-middleware]
  provides: [user-crud, user-policy, store-user-request, update-user-request, admin-user-routes]
  affects: [02-all, 03-all, 04-all, 05-all]
tech_stack:
  added: []
  patterns:
    - AuthorizesRequests trait on controller (not via base class — Laravel 13 base is bare)
    - Explicit per-method authorize() calls instead of authorizeResource() (avoids middleware() dependency)
    - Form request authorize() + Rule::in() for role whitelist validation
    - Rule::unique()->ignore() for update email uniqueness
    - SoftDeletes cascade: delete() sets deleted_at, User::withTrashed() retrieves
    - paginate(15) on index — canonical pattern for all Phase 2+ resource lists
key_files:
  created:
    - app/Http/Controllers/UserController.php
    - app/Http/Requests/StoreUserRequest.php
    - app/Http/Requests/UpdateUserRequest.php
    - app/Policies/UserPolicy.php
    - resources/views/users/index.blade.php
    - resources/views/users/create.blade.php
    - resources/views/users/edit.blade.php
    - tests/Feature/UserManagementTest.php
  modified:
    - routes/web.php (added role:admin group with resource route + toggle-active)
decisions:
  - D-16: Used explicit per-method authorize() instead of authorizeResource() — Laravel 13 base Controller no longer includes middleware() method that authorizeResource() depends on
  - D-17: Form requests (StoreUserRequest/UpdateUserRequest) carry their own authorize() checks, complementing the policy for defense in depth
  - D-18: toggleActive uses authorize('update', $user) — reuses the update policy check rather than a new policy method, keeping policy surface minimal
metrics:
  duration_minutes: 20
  completed_date: "2026-06-27"
  tasks_completed: 2
  files_created: 8
  files_modified: 1
  tests_passed: 9
---

# Phase 1 Plan 5: User Management CRUD Summary

**One-liner:** Admin-only user CRUD with four-role whitelist validation, hashed passwords, activate/deactivate toggles, confirmed soft-delete with JS dialog, and paginated Portuguese views — establishing the canonical resource-CRUD pattern for Phase 2.

## What Was Built

### Routes (`routes/web.php`)

```
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('usuarios', UserController::class)
        ->parameters(['usuarios' => 'user'])
        ->names('users');
    Route::post('usuarios/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->name('users.toggle-active');
});
```

| Route Name | Verb | Path | Action |
|---|---|---|---|
| `users.index` | GET | `/usuarios` | `index` |
| `users.create` | GET | `/usuarios/criar` | `create` |
| `users.store` | POST | `/usuarios` | `store` |
| `users.edit` | GET | `/usuarios/{user}/editar` | `edit` |
| `users.update` | PUT/PATCH | `/usuarios/{user}` | `update` |
| `users.destroy` | DELETE | `/usuarios/{user}` | `destroy` |
| `users.toggle-active` | POST | `/usuarios/{user}/toggle-active` | `toggleActive` |

All routes protected by `auth` + `role:admin` middleware.

### UserPolicy (`app/Policies/UserPolicy.php`)

All six policy methods (`viewAny`, `view`, `create`, `update`, `delete`, `restore`) return `$user->isAdmin()`. Auto-discovered by Laravel (App\Models\User → App\Policies\UserPolicy naming convention).

### StoreUserRequest (`app/Http/Requests/StoreUserRequest.php`)

| Field | Rules |
|---|---|
| name | required, string, max:255 |
| email | required, email, unique:users |
| phone | nullable, string, max:30 |
| role | required, Rule::in([admin, manager, financial, client]) |
| password | required, confirmed, min:8 |

PT messages for all fields. `authorize()` returns `$this->user()->isAdmin()`.

### UpdateUserRequest (`app/Http/Requests/UpdateUserRequest.php`)

Same as Store, but:
- email: `Rule::unique('users')->ignore($this->route('user'))` — allows editing own email
- password: nullable (blank = no change)

### UserController (`app/Http/Controllers/UserController.php`)

Uses `AuthorizesRequests` trait directly (not via base Controller). Explicit `$this->authorize()` calls in each method:

| Method | Authorization | Behavior |
|---|---|---|
| `index` | `viewAny` User::class | Paginated list, 15/page |
| `create` | `create` User::class | Return create view |
| `store` | StoreUserRequest::authorize() | Create + active=true, redirect with flash |
| `edit` | `update` $user | Return edit view |
| `update` | UpdateUserRequest::authorize() | Update, skip password if blank |
| `destroy` | `delete` $user | Soft-delete, redirect with flash |
| `toggleActive` | `update` $user | Flip active flag, flash "ativado/desativado" |

### Views

| View | Key Features |
|---|---|
| `users/index.blade.php` | x-admin-layout, x-data-table, x-badge for role+status, inline toggle/delete forms, JS confirm, `$users->links()` pagination, empty state |
| `users/create.blade.php` | White card, all fields, password+confirmation, x-primary-button "Salvar", cancel link |
| `users/edit.blade.php` | Pre-filled fields via `old() ?? $user->*`, blank password = no change hint, @method('PUT') |

**Role badge colors (matching UI-SPEC Screen 4):**

| Role | PT Label | Badge Color |
|---|---|---|
| admin | Administrador | purple |
| manager | Gestor da Obra | blue |
| financial | Financeiro | yellow |
| client | Cliente | gray |

Status: active=true → green "Ativo"; active=false → red "Inativo"

### Tests (`tests/Feature/UserManagementTest.php`)

9 tests, all passing:

| Test | What it verifies |
|---|---|
| `test_admin_can_create_user_with_valid_data` | POST store, assertDatabaseHas email+role |
| `test_created_user_password_is_hashed` | password != plain, Hash::check passes |
| `test_create_with_invalid_role_fails_validation` | role not in set → session errors |
| `test_create_with_duplicate_email_fails_validation` | unique:users rule enforced |
| `test_admin_can_update_user_name_and_role` | PUT update, assertDatabaseHas new values |
| `test_toggle_active_flips_active_flag` | POST toggle, active flips false then true |
| `test_delete_soft_deletes_user` | DELETE → withTrashed has deleted_at set |
| `test_non_admin_manager_gets_403_on_user_list` | manager → 403 on GET /usuarios |
| `test_guest_is_redirected_to_login_from_user_list` | guest → redirect to /login |

## Security Coverage (Threat Model)

| Threat ID | Category | Status |
|---|---|---|
| T-05-01: Elevation of Privilege (role assignment) | mitigate | StoreUserRequest validates role via Rule::in(four constants); UserPolicy + role:admin restrict all actions to admins |
| T-05-02: Tampering (mass assignment) | mitigate | Form requests pass only validated() data; User Fillable whitelist; active set server-side |
| T-05-03: Information Disclosure (password) | mitigate | Password stored via 'hashed' cast; never echoed back in edit form; password field is blank by default |
| T-05-04: Repudiation (deletion) | mitigate | Soft delete keeps deleted_at record; JS confirm prevents accidental deletes |
| T-05-05: Tampering (state-changing GETs) | mitigate | toggle-active and destroy use POST/DELETE forms with @csrf, never GET links |

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] `authorizeResource()` calls `middleware()` which is absent on Laravel 13 base Controller**

- **Found during:** Task 1 GREEN phase (500 error: `Call to undefined method UserController::middleware()`)
- **Issue:** `AuthorizesRequests::authorizeResource()` internally calls `$this->middleware()` to register per-action can:* middleware. In Laravel 13, the base `Controller` class is a bare abstract class without the `middleware()` method. This pattern was valid in Laravel 10/11 but is broken in 13.
- **Fix:** Removed `authorizeResource()` constructor pattern. Added `AuthorizesRequests` trait to `UserController` and replaced with explicit `$this->authorize('viewAny', ...)`, `$this->authorize('create', ...)`, etc. per-method. Form requests retain their own `authorize()` checks for defense in depth.
- **Files modified:** `app/Http/Controllers/UserController.php`
- **Commit:** 4cc74e6

## Known Stubs

None. All CRUD operations are fully wired: routes → middleware → controller → policy → form requests → views. No placeholder data or TODO comments remain.

## Threat Flags

None — no new network endpoints or trust boundaries beyond what the plan's threat model already covers.

## Self-Check: PASSED

- app/Http/Controllers/UserController.php contains `paginate(15)` and `toggleActive`: FOUND
- app/Http/Requests/StoreUserRequest.php contains `Rule::in(` and `'confirmed'`: FOUND
- app/Http/Requests/UpdateUserRequest.php contains `->ignore(`: FOUND
- app/Policies/UserPolicy.php contains `isAdmin`: FOUND
- routes/web.php contains `role:admin` and `UserController` and `users.toggle-active`: FOUND
- resources/views/users/index.blade.php contains `x-admin-layout`, `x-data-table`, `Novo Usuário`, `$users->links()`, `Tem certeza que deseja excluir este usuário`: FOUND
- resources/views/users/create.blade.php contains `users.store` and `password_confirmation`: FOUND
- resources/views/users/edit.blade.php contains `@method('PUT')` and `users.update`: FOUND
- `php artisan test --filter=UserManagementTest`: 9/9 PASSED
- `npm run build`: 0 exit code PASSED
- Commits ae1c08d (RED tests), 4cc74e6 (GREEN implementation+views): FOUND
