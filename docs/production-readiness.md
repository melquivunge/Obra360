# Production Readiness Notes

## Docker/Vite build

The production `Dockerfile` builds frontend assets in a dedicated Node stage:

```bash
docker build .
```

The final PHP image receives `public/build` from that build stage and does not include `node_modules`.

## Legacy public media migration

New project covers and photos are stored on the private `local` disk and served through authenticated routes.

Files that were previously stored under `storage/app/public` — covers, photos, thumbnails and documents — are migrated by the command below. The public-disk fallback was removed on 2026-08-22: media is now read only from the private `local` disk, and a record whose file is missing answers 404 instead of 500. Run the migration in any environment that still has media on the public disk **before** deploying that change there.

Preview the migration:

```bash
php artisan obra360:media:migrate-public
```

Copy legacy media to private storage:

```bash
php artisan obra360:media:migrate-public --commit
```

After validating that media works through the application, remove the public source files:

```bash
php artisan obra360:media:migrate-public --commit --delete-source
```

Production was migrated on 2026-08-22. The dry-run found eight media records whose files were on neither disk — the production database had been copied from a local environment and the files never followed. They were uploaded by hand into `storage/app/private`; the dry-run now reports `already_private: 8`, `missing_public: 0`.

## Access and deletion decisions

The `financial` role now has a decided surface: it owns the money across the whole portfolio and never touches project structure.

A financial user **can** read every project regardless of who manages it (project list, project page, cover and photo views, messages), open the dashboard and every report including the PDF export, and create, edit and delete expenses and payments on **any** project.

A financial user **cannot** create, edit or delete projects, phases, photos, documents or project updates, and cannot manage users or open the settings page — those stay with `admin` and `manager`.

The scope is enforced by the `role:` middleware groups in `routes/web.php`, not per project. `AuthorizesProjectAccess` deliberately restricts only the `manager` role to its own projects: a financial user is never assigned as a project manager, so scoping them by `manager_id` produced a 403 on every route the role was explicitly granted. The `manage-projects` and `access-financial` gates mirror those middleware groups so views hide write affordances that the routes would reject; the routes remain the enforcement point. `tests/Feature/FinancialRoleTest.php` locks this in from both directions, including a non-regression check that the manager remains scoped to their own projects.

One consequence is unresolved: a financial user reads all project data (photos, documents, messages) for context, so the role is not a money-only silo. If a future requirement needs per-project financial assignment, it requires a new column on `projects` and a corresponding scope — the current model has no way to express it.

Project deletion remains a soft delete. Media files are not removed during soft delete so future restore remains possible. Permanent media cleanup should be implemented with an explicit force-delete lifecycle or queued cleanup job.
