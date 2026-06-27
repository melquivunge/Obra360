---
phase: 01-foundation
plan: 1
subsystem: auth
tags: [auth, breeze, tailwind-v4, vite, scaffold]
dependency_graph:
  requires: []
  provides: [auth-scaffold, breeze-routes, breeze-controllers, breeze-views, tailwind-v4-pipeline]
  affects: [01-02, 01-03, 01-04]
tech_stack:
  added:
    - laravel/breeze v2.4.2 (require-dev)
    - alpinejs ^3.4.2 (Breeze navigation interactivity)
  patterns:
    - Breeze Blade scaffold with CSRF-protected auth forms
    - Tailwind v4 via @tailwindcss/vite plugin (no config file)
    - Bunny Fonts CDN for Instrument Sans (400/500/600)
key_files:
  created:
    - routes/auth.php
    - app/Http/Controllers/Auth/AuthenticatedSessionController.php
    - app/Http/Controllers/Auth/RegisteredUserController.php
    - app/Http/Controllers/Auth/PasswordResetLinkController.php
    - app/Http/Controllers/Auth/NewPasswordController.php
    - app/Http/Controllers/Auth/PasswordController.php
    - app/Http/Controllers/Auth/ConfirmablePasswordController.php
    - app/Http/Controllers/Auth/EmailVerificationPromptController.php
    - app/Http/Controllers/Auth/EmailVerificationNotificationController.php
    - app/Http/Controllers/Auth/VerifyEmailController.php
    - app/Http/Controllers/ProfileController.php
    - app/Http/Requests/Auth/LoginRequest.php
    - app/Http/Requests/ProfileUpdateRequest.php
    - app/View/Components/AppLayout.php
    - app/View/Components/GuestLayout.php
    - resources/views/auth/login.blade.php
    - resources/views/auth/register.blade.php
    - resources/views/auth/forgot-password.blade.php
    - resources/views/auth/reset-password.blade.php
    - resources/views/auth/confirm-password.blade.php
    - resources/views/auth/verify-email.blade.php
    - resources/views/layouts/app.blade.php
    - resources/views/layouts/guest.blade.php
    - resources/views/layouts/navigation.blade.php
    - resources/views/dashboard.blade.php
    - resources/views/profile/edit.blade.php
    - tests/Feature/Auth/AuthenticationTest.php
    - tests/Feature/Auth/RegistrationTest.php
    - tests/Feature/Auth/PasswordResetTest.php
    - tests/Feature/Auth/PasswordConfirmationTest.php
    - tests/Feature/Auth/PasswordUpdateTest.php
    - tests/Feature/Auth/EmailVerificationTest.php
    - tests/Feature/ProfileTest.php
  modified:
    - composer.json (added laravel/breeze ^2.4 to require-dev)
    - composer.lock
    - package.json (restored @tailwindcss/vite ^4, removed v3 packages)
    - package-lock.json
    - routes/web.php (added dashboard route + require auth.php)
    - vite.config.js (restored @tailwindcss/vite + Bunny Fonts Instrument Sans)
    - resources/css/app.css (restored @import "tailwindcss" v4 syntax)
  deleted:
    - tailwind.config.js (Breeze v3 artifact, removed)
    - postcss.config.js (Breeze v3 artifact, removed)
decisions:
  - D-01: Used laravel/breeze blade stack (v2.4.2) per project decision; no custom auth
  - D-02: Kept alpinejs from Breeze (navigation dropdown uses x-data); no other JS framework
  - D-03: Restored Tailwind v4 setup after Breeze scaffold; deleted v3 tailwind.config.js and postcss.config.js
metrics:
  duration_minutes: 10
  completed_date: "2026-06-27T11:16:58Z"
  tasks_completed: 2
  files_created: 33
  files_modified: 7
  files_deleted: 2
  tests_passed: 18
---

# Phase 1 Plan 1: Breeze Auth Scaffold + Tailwind v4 Reconciliation Summary

**One-liner:** Laravel Breeze v2.4.2 blade scaffold installed with CSRF-protected auth forms, bcrypt passwords, session regeneration on login/logout, and Tailwind v4 + Bunny Fonts pipeline fully restored after Breeze's v3 overwrite.

## What Was Built

Laravel Breeze v2.4.2 (blade stack) scaffolded the complete authentication foundation for Obra360:

- **Auth routes** (`routes/auth.php`): login, logout, register, password reset (request + confirm), email verification, profile management — all included in `routes/web.php` via `require __DIR__.'/auth.php';`
- **9 Auth controllers** in `app/Http/Controllers/Auth/`: handles login/logout (with session regeneration), registration, password reset link, new password, password update, password confirmation, email verification prompt/notification/confirm
- **ProfileController** for profile view/update/delete
- **LoginRequest** with rate limiting (throttle 5 attempts/minute) — satisfies T-01-01 threat mitigation
- **6 auth views** under `resources/views/auth/`: all include `@csrf` — satisfies T-01-02
- **Layouts**: `app.blade.php` (authenticated), `guest.blade.php`, `navigation.blade.php` with Alpine.js dropdown
- **18 Breeze auth feature tests** — all passing against in-memory SQLite

## Tailwind v4 Reconciliation

Breeze's blade scaffold ships a Tailwind v3 setup. The following reconciliation was applied:

| Item | Action |
|------|--------|
| `tailwind.config.js` | Deleted (v3 artifact, v4 needs none) |
| `postcss.config.js` | Deleted (v4 uses Vite plugin, not PostCSS) |
| `tailwindcss@^3.1.0` | Downgraded → `^4.0.0` in package.json |
| `autoprefixer@^10.4.2` | Removed (v4 internal) |
| `postcss@^8.4.31` | Removed (not needed with Vite plugin) |
| `@tailwindcss/forms@^0.5.2` | Removed (v3 plugin) |
| `resources/css/app.css` | Restored: `@import "tailwindcss"` + `@source` + `@theme` |
| `vite.config.js` | Restored: `@tailwindcss/vite` plugin + Bunny Fonts Instrument Sans |

**alpinejs** (`^3.4.2`) was retained — Breeze's navigation component uses Alpine.js `x-data` for dropdown menus.

## Security Coverage (Threat Model)

| Threat | Status |
|--------|--------|
| T-01-01: Credential stuffing | Mitigated — LoginRequest throttles to 5 attempts/minute via `RateLimiter::for('login')` |
| T-01-02: CSRF tampering | Mitigated — all POST forms include `@csrf`; VerifyCsrfToken on web middleware group |
| T-01-03: Plain-text passwords | Mitigated — User model `password => hashed` cast (bcrypt); confirmed untouched by Breeze |
| T-01-04: Session fixation | Mitigated — `$request->session()->regenerate()` on login, `invalidate()` on logout |

## Verification Results

```
php artisan route:list → login, logout, password.reset routes present
npm run build          → exit 0 (51.45kB CSS, 45.25kB JS, Instrument Sans fonts)
php artisan test --filter=Auth → 18 tests, 18 passed, 38 assertions (2.2s)
tailwind.config.js     → absent
postcss.config.js      → absent
resources/css/app.css  → @import 'tailwindcss' (v4 only)
vite.config.js         → @tailwindcss/vite + bunny('Instrument Sans')
```

## Deviations from Plan

### Auto-fixed Issues

None — Breeze's v3 pipeline overwrite was anticipated in the plan. The reconciliation in Task 2 is as-specified.

**Note:** `.env` was created in the worktree (from `.env.example`) with SQLite substituted for MySQL to allow `php artisan migrate` to run without the Docker MySQL container. The .env is gitignored and does not affect the main environment.

## Known Stubs

None. Breeze scaffold is complete — views render real auth flows, not placeholder content.

## Threat Flags

None — no new network endpoints, auth paths, or trust boundaries beyond what the plan's threat model already covers.

## Self-Check: PASSED

- routes/auth.php: FOUND
- app/Http/Controllers/Auth/AuthenticatedSessionController.php: FOUND
- resources/views/auth/login.blade.php: FOUND
- tailwind.config.js: ABSENT (correct)
- postcss.config.js: ABSENT (correct)
- Commits 2762847 (Task 1) and 300231a (Task 2): FOUND
- 18/18 auth tests: PASSED
