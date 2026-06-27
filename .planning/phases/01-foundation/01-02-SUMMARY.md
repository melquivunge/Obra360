---
phase: 01-foundation
plan: 2
subsystem: schema
tags: [schema, migrations, models, roles, soft-deletes, tdd]
dependency_graph:
  requires: [auth-scaffold]
  provides: [full-schema, user-role-system, clients-table, projects-table, project-phases-table, photos-table, expenses-table, documents-table, project-updates-table, messages-table, settings-table]
  affects: [01-03, 01-04, 01-05, 01-06]
tech_stack:
  added: []
  patterns:
    - Anonymous-class migrations (PHP 8 style, 4-space indent, return type declarations)
    - PHP 8 attribute style for Fillable/Hidden on Eloquent models
    - SoftDeletes trait for audit-safe logical deletion
    - TDD RED/GREEN cycle for User role system
key_files:
  created:
    - database/migrations/2026_06_27_100001_create_clients_table.php
    - database/migrations/2026_06_27_100002_create_projects_table.php
    - database/migrations/2026_06_27_100003_create_project_phases_table.php
    - database/migrations/2026_06_27_100004_create_photos_table.php
    - database/migrations/2026_06_27_100005_create_expenses_table.php
    - database/migrations/2026_06_27_100006_create_documents_table.php
    - database/migrations/2026_06_27_100007_create_project_updates_table.php
    - database/migrations/2026_06_27_100008_create_messages_table.php
    - database/migrations/2026_06_27_100009_create_settings_table.php
    - database/migrations/2026_06_27_100010_add_role_and_relations_to_users_table.php
    - tests/Unit/UserRoleTest.php
    - tests/Feature/SchemaTest.php
  modified:
    - app/Models/User.php (SoftDeletes, role constants, helpers, updated Fillable+casts)
    - database/factories/UserFactory.php (role, phone, active defaults + state methods)
    - database/seeders/DatabaseSeeder.php (initial admin user)
decisions:
  - D-03: Full schema created in one phase (10 tables) — no migration churn in later phases
  - D-04: Soft deletes on users/clients/projects/photos/expenses/documents only (per SEC-06)
  - D-05: Four-role system via string column with default 'client' (least privilege); helpers on User model
metrics:
  duration_minutes: 15
  completed_date: "2026-06-27T12:00:00Z"
  tasks_completed: 3
  files_created: 12
  files_modified: 3
  tests_passed: 11
---

# Phase 1 Plan 2: Complete Relational Schema + User Role System Summary

**One-liner:** Full 10-table Obra360 schema (clients, projects, phases, photos, expenses, documents, updates, messages, settings, users extension) with four-role system, soft deletes, and initial admin seed — deployed in a single migrate:fresh pass.

## What Was Built

### Schema Overview

| Table | Key Columns | FK Dependencies | Soft Deletes |
|-------|-------------|-----------------|--------------|
| `users` (extended) | role (default client), phone, active, client_id, deleted_at | clients | Yes |
| `clients` | name, email, phone, document, address, city, country, notes, active | — | Yes |
| `projects` | name, code, client_id, location, description, start_date, expected_end_date, budget decimal(15,2), status (planejada), progress tinyint, manager_id | clients, users | Yes |
| `project_phases` | project_id, name, description, status (pendente), percentage tinyint, sort_order uint, start/expected/real end dates | projects | No |
| `photos` | project_id, phase_id, title, description, path, thumbnail_path, taken_at, uploaded_by | projects, project_phases, users | Yes |
| `expenses` | project_id, category, description, amount decimal(15,2), spent_at, supplier, receipt_path, notes, created_by | projects, users | Yes |
| `documents` | project_id, name, type, visibility (interno), path, notes, uploaded_by | projects, users | Yes |
| `project_updates` | project_id, title, description, type, happened_at datetime, user_id | projects, users | No |
| `messages` | project_id, user_id, parent_id (self-ref), body, read_at | projects, users, messages | No |
| `settings` | key (unique), value text, type (default string) | — | No |

### User Model Additions

- `SoftDeletes` trait alongside `HasFactory` and `Notifiable`
- `#[Fillable([..., 'role', 'phone', 'active', 'client_id'])]` — mass-assignment whitelist extended (T-02-01 mitigation)
- `casts()`: added `'active' => 'boolean'`
- Constants: `ROLE_ADMIN = 'admin'`, `ROLE_MANAGER = 'manager'`, `ROLE_FINANCIAL = 'financial'`, `ROLE_CLIENT = 'client'`
- Helpers: `isAdmin()`, `isManager()`, `isFinancial()`, `isClient()` — all return `bool`

### Factory and Seeder

- `UserFactory::definition()`: now includes `role => ROLE_CLIENT`, `phone`, `active => true` defaults
- State methods: `admin()`, `manager()`, `financial()` for factory chaining
- `DatabaseSeeder::run()`: seeds `admin@obra360.test` with `ROLE_ADMIN` and `active=true`

### Tests

- `tests/Unit/UserRoleTest.php`: 6 tests — role constants, 4 role helper methods, soft-delete behavior (TDD RED/GREEN)
- `tests/Feature/SchemaTest.php`: 5 tests — all 10 tables exist, users role/soft-delete columns, projects.budget, documents.visibility, messages.body

## Security Coverage (Threat Model)

| Threat | Status |
|--------|--------|
| T-02-01: Mass assignment on role | Mitigated — `#[Fillable]` whitelist includes role; admin-only assignment enforced in Plan 05 request validation |
| T-02-02: Hard-delete data loss | Mitigated — SoftDeletes on users/clients/projects/photos/expenses/documents keeps deleted_at audit trail |
| T-02-03: Privilege escalation via role default | Accepted — default is `client` (least privilege); admin must explicitly set higher roles |
| T-02-04: Document information disclosure | Mitigated — visibility defaults to `interno`; client filtering enforced in Phase 4 |

## Migration Order

```
0001_01_01_000000  create_users_table (+ password_reset_tokens, sessions)
0001_01_01_000001  create_cache_table
0001_01_01_000002  create_jobs_table
2026_06_27_100001  create_clients_table
2026_06_27_100002  create_projects_table
2026_06_27_100003  create_project_phases_table
2026_06_27_100004  create_photos_table
2026_06_27_100005  create_expenses_table
2026_06_27_100006  create_documents_table
2026_06_27_100007  create_project_updates_table
2026_06_27_100008  create_messages_table
2026_06_27_100009  create_settings_table
2026_06_27_100010  add_role_and_relations_to_users_table
```

Timestamps are intentionally ordered so all FK dependencies resolve: clients before projects, projects before phases/photos/expenses/documents/updates/messages, users before the role alteration that adds client_id FK.

## Verification Results

```
php artisan migrate:fresh --seed  → 13 migrations + seeder run cleanly (SQLite in-memory)
php artisan test --filter=UserRoleTest  → 6 tests, 6 passed, 12 assertions
php artisan test --filter=SchemaTest    → 5 tests, 5 passed, 15 assertions
Total                                   → 11 tests, 11 passed, 27 assertions
```

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Created clients migration in Task 1 instead of Task 2**

- **Found during:** Task 1 TDD GREEN phase
- **Issue:** Migration 100010 (Task 1) references `constrained('clients')`. The clients migration (100001) is listed in Task 2. Without 100001 existing when 100010 runs, migrate:fresh fails with a foreign key error.
- **Fix:** Created the full clients migration (100001) during Task 1's GREEN phase to unblock the FK constraint. Task 2 therefore focused on projects and project_phases only.
- **Files modified:** `database/migrations/2026_06_27_100001_create_clients_table.php` (created in Task 1, not Task 2)
- **Commit:** 357ae69

**2. [Rule 3 - Blocking] Composer install needed in worktree**

- **Found during:** Task 1 TDD GREEN verification
- **Issue:** The worktree lacked a local vendor directory. A vendor symlink to the main repo caused the autoloader's `$baseDir` to resolve to the main repo's `app/` instead of the worktree's `app/`, so User.php changes were invisible to PHP.
- **Fix:** Removed the symlink, ran `composer install --quiet --no-scripts` in the worktree to produce a correct autoloader.
- **Files modified:** vendor/ (local, gitignored)
- **Commit:** N/A (build environment, not committed)

**3. [Rule 3 - Blocking] .env required SQLite override**

- **Found during:** Task 2 migrate:fresh verification
- **Issue:** The .env from .env.example points to MySQL host `db` (Docker); running migrate:fresh without the Docker container fails with a network error.
- **Fix:** Updated the worktree .env to use `DB_CONNECTION=sqlite` with a local file path, matching the phpunit.xml configuration for local development. The .env is gitignored.
- **Files modified:** .env (gitignored, not committed)

## Known Stubs

None. All migrations define complete, production-ready schemas with correct column types, defaults, nullable flags, and FK constraints. No placeholder values in model or seeder output.

## Threat Flags

None — no new network endpoints, auth paths, file access patterns, or schema changes beyond what the plan's threat model already covers.

## Self-Check: PASSED

- database/migrations/2026_06_27_100001_create_clients_table.php: FOUND
- database/migrations/2026_06_27_100002_create_projects_table.php: FOUND
- database/migrations/2026_06_27_100003_create_project_phases_table.php: FOUND
- database/migrations/2026_06_27_100004_create_photos_table.php: FOUND
- database/migrations/2026_06_27_100005_create_expenses_table.php: FOUND
- database/migrations/2026_06_27_100006_create_documents_table.php: FOUND
- database/migrations/2026_06_27_100007_create_project_updates_table.php: FOUND
- database/migrations/2026_06_27_100008_create_messages_table.php: FOUND
- database/migrations/2026_06_27_100009_create_settings_table.php: FOUND
- database/migrations/2026_06_27_100010_add_role_and_relations_to_users_table.php: FOUND
- app/Models/User.php: FOUND (SoftDeletes + role constants + helpers)
- database/factories/UserFactory.php: FOUND (role/phone/active + state methods)
- database/seeders/DatabaseSeeder.php: FOUND (admin@obra360.test with ROLE_ADMIN)
- tests/Unit/UserRoleTest.php: FOUND
- tests/Feature/SchemaTest.php: FOUND
- Commits 624b35c (RED), 357ae69 (GREEN/Task1), cf2fe81 (Task2), fbdc745 (Task3): ALL FOUND
- 11/11 tests: PASSED
