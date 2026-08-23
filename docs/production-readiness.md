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

Plans follow the same split. Creating a plan, uploading a version, sending a version to the client and deleting a plan are project structure, so they live in the `role:admin,manager` groups; downloading a version is reading, so it sits with the photo and cover routes that `financial` may reach. Deleting a plan is a soft delete that deliberately leaves `plan_versions` untouched (PLAN-09): the history survives the delete and comes back whole on restore.

`Plan` and `PlanVersion` are both watched by `AuditObserver`, for the same reason `Document` is — a plan is a contractual artefact, and "which drawing was current, who uploaded it, when it was sent, when it was superseded" is exactly what the trail exists to answer. Auditing only `Plan` would record the label and lose the facts, because those columns live on `plan_versions`. It is also where PORT-05 will read from once the client can approve a version in the portal.

Project deletion remains a soft delete. Media files are not removed during soft delete so future restore remains possible. Permanent media cleanup should be implemented with an explicit force-delete lifecycle or queued cleanup job.

## Upload size limit (plans)

Plan versions (`plan_versions`) accept the file formats architects actually hand over: PDF, JPG, JPEG, PNG, WEBP, DWG, DXF, SKP, RVT, IFC, GLB, GLTF and ZIP. Revit and SketchUp files routinely pass 100 MB, so the server ceiling is a product constraint, not a detail.

### What the ceiling actually is

The effective limit is the **smaller** of two PHP directives, because either one alone will reject the request:

| Directive | Docker (`docker/php/local.ini`) | Effect when exceeded |
| --- | --- | --- |
| `upload_max_filesize` | 64M | The file arrives with `UPLOAD_ERR_INI_SIZE` and no content. Caught in `ProjectPlanVersionController::store`, which answers with the limit and the external-address alternative. |
| `post_max_size` | 64M | PHP discards the whole request body before the application sees it. |

`PlanVersion::maxUploadKilobytes()` reads both directives at runtime and returns the smaller. That one number feeds three places: the `max:` validation rule, the sentence printed in the upload form before anyone picks a file, and every error message. A misconfigured server therefore tells the truth about what it accepts rather than repeating a value someone typed once.

### What the user sees when a file is too big

Three distinct failures, three distinct answers:

1. **File over `upload_max_filesize`, request under `post_max_size`** — the controller intercepts `UPLOAD_ERR_INI_SIZE` before validation and returns `PlanVersion::overLimitMessage()`. Without that intercept, Laravel's `file` rule says "must be a file", which is true and useless.
2. **Request over `post_max_size`, with a `Content-Length`** — Laravel's global `ValidatePostSize` middleware throws before the session starts, so no error can be flashed back to the form. `resources/views/errors/413.blade.php` replaces the generic "413 Payload Too Large" with the limit, the two ways out, and a back link.
3. **Request truncated with no `Content-Length`** (chunked transfer encoding) — escapes `ValidatePostSize`, and PHP hands the application an empty `$_POST` and `$_FILES`. The CSRF token is gone with them, so the default answer is "419 — page expired". `App\Http\Middleware\ExplainTruncatedUpload` takes the CSRF middleware's slot in the `web` group (see `bootstrap/app.php`) and answers with the truth instead.

In every case the documented way out is the same and is a first-class feature, not a workaround: a plan version may store an **external address** (Google Drive, WeTransfer, OneDrive) instead of a file. The version number, the change note, the supersede history and the audit trail are recorded exactly as they would be for an uploaded file — only the bytes live elsewhere.

### Raising the limit in cPanel

Production runs on HostGator shared hosting. To raise the ceiling:

1. Log into cPanel and open **Software › Select PHP Version**, then the **Options** tab (on some cPanel builds this is **MultiPHP INI Editor › Editor mode**).
2. Set `upload_max_filesize` and `post_max_size` together. **`post_max_size` must be at least as large as `upload_max_filesize`**, and ideally a little larger, because it covers the whole request — the file plus the other form fields. Setting only `upload_max_filesize` changes nothing.
3. `max_execution_time` and `memory_limit` usually need to go up with them. A 200 MB upload over a slow connection can exceed a 300-second execution limit even though the file itself is fine.
4. Verify from the application, not from the ini file — the value the app enforces and advertises is whatever `PlanVersion::maxUploadKilobytes()` reads:

```bash
php artisan tinker --execute="echo App\Models\PlanVersion::maxUploadLabel();"
```

The upload form on the project page prints the same value, so a wrong setting is visible without a shell.

### The cost and the risk of raising it

- **Shared hosting enforces its own ceilings.** HostGator caps what a shared account may set; above that cap the directive is silently ignored, and the application will then advertise a limit the server never honours. Always verify with the command above after changing it.
- **Concurrency is the real cost, not disk.** Each in-flight upload holds a PHP-FPM worker and buffers the request in memory or in the temp directory for its whole duration. Three simultaneous 200 MB uploads on a shared account is enough to exhaust the process pool and make the whole portal unresponsive to everyone, including clients who are only reading their project.
- **Long uploads collide with the execution timeout.** A request that dies at `max_execution_time` leaves nothing behind but a failed upload and a confused user; the ceiling and the timeout have to be raised together or not at all.
- **Storage grows fastest here.** Plan versions are never deleted — that is the point of the feature (PLAN-04). A plan revised ten times keeps ten files. Raising the per-file limit multiplies the account's disk growth by the same factor, on hosting where disk is finite and shared.

The deliberate position is to leave the limit at 64 MB and treat the external address as the normal path for native Revit, SketchUp and large IFC models, rather than to raise the ceiling and inherit the concurrency problem. Raise it only if a specific client workflow makes the external address unworkable, and raise `max_execution_time` and `memory_limit` in the same change.

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
