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

## Scheduled maintenance

Sessions, cache and queues all live in the database (`SESSION_DRIVER`, `CACHE_STORE` and `QUEUE_CONNECTION` are all `database`), and `audit_logs` receives one row for every write to the nine models watched by `AuditObserver`. Production runs on shared hosting (HostGator, cPanel), where disk and database size are finite, so this data has to be swept. The schedule lives in `routes/console.php`.

| Task | Frequency (UTC) | Why |
| --- | --- | --- |
| `auth:clear-resets` | daily, 03:00 | Password reset tokens expire after 60 minutes but their rows stay behind. The table is tiny, so daily is more than enough — running it hourly would spend a process for nothing. |
| `queue:prune-failed --hours=168` | weekly, Sunday 03:30 | Failed queued jobs (project notifications, mostly). Seven days of retention keeps a full week of failures available for diagnosis; the table only grows when something breaks, so a weekly sweep is enough. |
| `obra360:audit:prune --commit` | daily, 04:00 | The fastest-growing table. Daily rather than weekly so each run deletes roughly one day's worth of rows and stays short, instead of accumulating a week and issuing one large `DELETE` against a shared MySQL server. |

Deliberately not scheduled: `cache:prune-stale-tags` (Redis only — this install uses the `database` cache store), `queue:prune-batches` (nothing in the application calls `Bus::batch()`, so `job_batches` is always empty). The `sessions` table needs no task: Laravel sweeps it by lottery on 2% of requests (`config/session.php`).

### Audit log retention

`obra360:audit:prune` deletes `audit_logs` rows older than the retention window. The default is **365 days**, and it is configurable:

```bash
# Dry run with the default window — counts, deletes nothing.
php artisan obra360:audit:prune

# Dry run with a different window.
php artisan obra360:audit:prune --days=180

# Actually delete.
php artisan obra360:audit:prune --days=180 --commit
```

The command is a dry run unless `--commit` is passed, and it refuses a `--days` value that is not a positive integer — a window of zero would wipe the entire audit trail.

One year was chosen because it covers a full project lifecycle plus the following year's account closing, which is the window in which "who changed this and when" is actually asked (client complaint, disputed amount, accounting audit). A shorter window would delete history while the project is still running. Raise it if a longer legal retention applies; the trade-off is table size on shared hosting.

### The cron entry (required — nothing above runs without it)

Laravel's scheduler is not a daemon. It only does anything when something calls `schedule:run`, and that call has to come from a cron entry created by hand in cPanel. **Until this entry exists in production, every task above is decorative: no failed job is pruned, no reset token is cleared, and `audit_logs` grows without limit.**

In cPanel, open **Advanced › Cron Jobs**, set the schedule to *Once Per Minute* (`* * * * *`) and use this command:

```
* * * * * /opt/cpanel/ea-php85/root/usr/bin/php $HOME/obra360app.com/artisan schedule:run >> /dev/null 2>&1
```

Notes:

- The PHP binary must be the full path `/opt/cpanel/ea-php85/root/usr/bin/php`. The bare `php` in a cron environment resolves to the system PHP, which is an older version and will fail to boot the application.
- If the account's cron shell does not expand `$HOME`, write the absolute path instead: `/home/<cpanel-user>/obra360app.com/artisan`.
- It runs every minute by design. `schedule:run` exits immediately when nothing is due; it is the individual task frequencies above that decide what actually executes.
- Times in the schedule are in the application timezone, which is UTC (`config/app.php`), not the server's local time.

Verify the schedule after deploying:

```bash
php artisan schedule:list
```

And check that cron is actually firing by watching `storage/logs/` after 04:00 UTC, or by running a single sweep by hand:

```bash
php artisan schedule:run
```

### Known gap

Expired rows in the `cache` table are only removed when the key is read again. Laravel ships no bulk prune for the database cache store (`cache:prune-stale-tags` is Redis-only), so a key that is written, expires and is never read again stays in the table. It is bounded in practice by the small, fixed set of cache keys the application uses (`ClientLayoutComposer`), but it is not swept by anything.
