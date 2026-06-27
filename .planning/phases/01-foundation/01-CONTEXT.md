# Phase 1: Foundation - Context

**Gathered:** 2026-06-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 1 delivers four things: (1) working authentication with email+password login, logout, and password reset; (2) four-role access control (Administrador, Gestor da Obra, Financeiro, Cliente) with role-based redirects; (3) the complete database schema for the entire application (all tables created now, not incrementally); (4) two layout shells — a full admin sidebar layout and a minimal client layout — that all subsequent phases build on. User management (CRUD for the 4 roles) is also in scope.

This phase does NOT include: any project/client/phase content management (Phase 2), file uploads (Phase 3), client-facing portal views (Phase 4), or reports (Phase 5).

</domain>

<decisions>
## Implementation Decisions

### Auth Package
- **D-01:** Use Laravel Breeze with Blade scaffolding (`php artisan breeze:install blade`). Provides login, logout, password reset, and email verification out of the box. Do not write custom auth from scratch.
- **D-02:** After Breeze installs, remove or replace its default views with the Obra360 Portuguese-language equivalents. Do not keep Breeze's English placeholder text.

### Database Schema Strategy
- **D-03:** All application tables are created in Phase 1 — not incrementally per phase. The complete schema (users, clients, projects, project_phases, photos, expenses, documents, project_updates, messages, settings) is written as migrations now. Later phases add controllers, views, and business logic but do not alter the schema.
- **D-04:** Standard relational set: `users` (already exists, extend with `role` and `active` columns), `clients`, `projects`, `project_phases`, `photos`, `expenses`, `documents`, `project_updates`, `messages`, `settings`. All entities that have soft-delete per SEC-06 must include `deleted_at`.
- **D-05:** Roles are stored as a string enum/column (`role`) directly on the `users` table. Values: `admin`, `manager`, `financial`, `client`. No separate roles table, no Spatie permission package.

### Admin Layout Shell
- **D-06:** Admin sidebar is always visible and fixed-width on desktop. No collapse toggle. Mobile: hamburger button opens the sidebar as an overlay/drawer.
- **D-07:** Each sidebar item shows an icon on the left + Portuguese label text to the right. Items per UX-02: Dashboard, Clientes, Obras, Fases, Fotos, Despesas, Documentos, Relatórios, Usuários, Configurações, Sair.
- **D-08:** Active route is highlighted in the sidebar (different background or left accent border).

### Client Layout Shell
- **D-09:** Client layout has minimal top navigation per UX-03: Minhas Obras, Documentos, Mensagens, Meu Perfil, Sair. No sidebar — top nav bar only.
- **D-10:** Both layouts must be fully responsive (UX-01).

### User Management UI
- **D-11:** Standard Laravel resource CRUD pages: `/usuarios` (paginated list), `/usuarios/criar` (create form page), `/usuarios/{id}/editar` (edit form page). No modals for create/edit.
- **D-12:** User list table has inline row actions: Edit button (links to edit page), Activate/Deactivate toggle (POST with CSRF), Delete button (POST with confirmation — uses a small JS confirm dialog or a delete confirmation form). All actions visible per row.
- **D-13:** This dedicated-pages pattern (resource CRUD) is the standard for all admin CRUD throughout the application. Phase 2 will follow the same pattern for clients and projects.

### Login Screen Design
- **D-14:** Centered white card on a dark navy/blue full-page background. Card contains: "Obra360" app name + construction building icon at top, email field, password field, "Esqueci a senha" link, submit button ("Entrar"). Portuguese labels throughout.
- **D-15:** Company logo/name slot shows "Obra360" with an icon placeholder. Phase 5 system settings will allow replacing this with the real company logo and name. No dynamic config lookup in Phase 1.

### Role-Based Access Control
- **D-16:** Use Laravel Gates and Policies (no third-party package). Define a policy per entity as phases add entities. In Phase 1, the gate logic is: admin can access all admin routes; manager can access project-scoped routes; financial can access financial routes; client sees only client portal.
- **D-17:** A middleware group (or `auth` + role-check middleware) protects all admin routes. Inactive users (`active = false`) are blocked at login and shown a Portuguese error message.

### Claude's Discretion
- Column naming and migration file organization (timestamps, ordering).
- Specific Heroicons or similar icon set for the sidebar (choose one consistent set).
- Tailwind CSS color palette specifics within the stated "dark blue / white / green" direction.
- Pagination page size (default 15 items per page is fine).
- Seeder content for the initial admin user.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Requirements
- `.planning/REQUIREMENTS.md` §Authentication (AUTH-01 to AUTH-05) — Login, logout, password reset, inactive user blocking, bcrypt
- `.planning/REQUIREMENTS.md` §User Management (USR-01 to USR-05) — 4-role CRUD
- `.planning/REQUIREMENTS.md` §Security & Cross-Cutting (SEC-01, SEC-03, SEC-06) — Route protection, CSRF, soft deletes
- `.planning/REQUIREMENTS.md` §UI/UX (UX-01 to UX-05) — Responsiveness, sidebar items, client nav items, Portuguese UI

### Project Context
- `.planning/PROJECT.md` §Constraints — Laravel Breeze, Blade, Tailwind v4, local storage, PHP 8.4
- `.planning/PROJECT.md` §Key Decisions — Confirmed decisions from project init
- `.planning/STATE.md` §Decisions — Project-init decisions (Breeze, Blade, Portuguese, soft deletes, separate layout shells)
- `.planning/ROADMAP.md` §Phase 1 — Success criteria and full requirement list for this phase

### Existing Code
- `app/Models/User.php` — Existing User model using PHP 8 attributes (#[Fillable], #[Hidden]); must be extended, not replaced
- `database/migrations/0001_01_01_000000_create_users_table.php` — Existing users table; Phase 1 adds `role`, `phone`, `active`, `deleted_at` columns via a new migration (do not modify the existing migration file)
- `CLAUDE.md` — Code style, naming conventions, PHP 8 attribute pattern, Docker setup

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `app/Models/User.php`: Uses PHP 8 attribute syntax (`#[Fillable([...])]`, `#[Hidden([...])]`). All new models in this phase MUST follow the same attribute pattern — no `$fillable`/`$hidden` array properties.
- `app/Http/Controllers/Controller.php`: Empty base controller. All new controllers extend this.
- `database/migrations/0001_01_01_000000_create_users_table.php`: Existing migration creates `users` with `id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `timestamps`. Do NOT alter this file; add columns in a new migration.

### Established Patterns
- PHP 8 attributes for model properties (not the old array syntax).
- Anonymous migration classes (no class name, just `return new class extends Migration {...}`).
- 4-space indentation, LF line endings, trailing newline (see `.editorconfig`).
- Return type declarations required on all methods.

### Integration Points
- Breeze scaffolding installs into `routes/auth.php` (included from `routes/web.php`) and `resources/views/auth/`. Phase 1 customizes these Blade views.
- Vite + Tailwind v4 pipeline is already active (`resources/css/app.css`, `resources/js/app.js`). New layouts extend the Vite-compiled assets.
- Docker: PHP-FPM 8.4 + Nginx on port 8000. Commands run with `php artisan` inside the container or via `composer run dev` on host.

</code_context>

<specifics>
## Specific Ideas

- The admin sidebar design should feel like a professional construction management tool: dark sidebar (navy/dark blue) with white text and icons, a light main content area (white/light grey). Not overly colorful.
- The dark blue login background uses the same navy tone as the sidebar — consistent brand feel from first login.
- Construction building icon (could use Heroicons `BuildingOffice2` or similar) for the Obra360 logo placeholder.
- Portuguese error message for inactive users: "Sua conta está inativa. Entre em contato com o administrador."
- Password reset email subject should be in Portuguese: "Redefinição de senha — Obra360"

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within Phase 1 scope.

</deferred>

---

*Phase: 01-foundation*
*Context gathered: 2026-06-27*
