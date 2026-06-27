# Codebase Concerns

**Analysis Date:** 2026-06-27

---

## Known Bugs

**Sessions table does not exist in MySQL (active error):**
- Symptoms: Every HTTP request throws `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'obra360.sessions' doesn't exist`
- Files: `storage/logs/laravel.log`, `database/migrations/0001_01_01_000000_create_users_table.php`
- Trigger: Starting the app against the Docker MySQL database without running migrations
- Workaround: Run `php artisan migrate` inside the `app` container after `docker compose up`
- Root cause: The migration that creates the `sessions` table exists but has never been applied to the MySQL database. The Docker Compose setup has no entrypoint that auto-migrates.

---

## Security Considerations

**APP_DEBUG defaults to true in .env.example:**
- Risk: If `.env.example` is copied as-is to `.env` (which the `setup` script does), `APP_DEBUG=true` will leak stack traces, environment variables, and internal paths to users on errors
- Files: `.env.example` (line 4), `composer.json` setup script (line 37)
- Current mitigation: None
- Recommendation: Change `APP_DEBUG=false` in `.env.example`; force debug off in production via a separate `.env.production` or CI deployment step

**Session encryption is disabled:**
- Risk: `SESSION_ENCRYPT=false` in `.env.example` means session payloads stored in the database are plaintext
- Files: `.env.example` (line 33), `config/session.php`
- Recommendation: Set `SESSION_ENCRYPT=true` in `.env.example` as a safe default; it has no meaningful performance cost

**LOG_LEVEL=debug in .env.example:**
- Risk: Debug-level logging can capture sensitive request data, user input, and SQL queries in `storage/logs/laravel.log`
- Files: `.env.example` (line 18)
- Recommendation: Default to `LOG_LEVEL=warning` or `LOG_LEVEL=error` in `.env.example`

**Email verification is disabled on the User model:**
- Risk: Users can access the application without verifying their email address
- Files: `app/Models/User.php` (line 5 — `MustVerifyEmail` is commented out)
- Recommendation: Implement `MustVerifyEmail` before building authenticated routes

**No authentication package installed:**
- Risk: There is no session-based auth scaffolding (Breeze/Jetstream) and no API token auth (Sanctum/Passport). Any authenticated routes built without a proper auth layer will be insecure.
- Files: `composer.json`, `routes/web.php`
- Recommendation: Install `laravel/breeze` for web auth or `laravel/sanctum` for API auth before writing authenticated features

**No rate limiting on routes:**
- Files: `routes/web.php`, `bootstrap/app.php`
- Risk: No `throttle` middleware applied globally or per route — login/registration brute-force attacks, API abuse
- Recommendation: Apply `throttle:api` or custom rate limiters in `AppServiceProvider` or middleware registration

**Nginx has no HTTPS and no security headers:**
- Risk: All traffic is HTTP only; no `X-Frame-Options`, `X-Content-Type-Options`, `Strict-Transport-Security`, or `Content-Security-Policy` headers are set
- Files: `docker/nginx/default.conf`
- Recommendation: Add security headers to the nginx config; add TLS termination (or handle via a reverse proxy/load balancer in production)

**phpMyAdmin exposed in Docker Compose:**
- Risk: phpMyAdmin is accessible on port 8080 with root MySQL credentials. If this compose file is ever used in a staging or production environment, the full database is exposed.
- Files: `docker-compose.yml` (lines 48–63)
- Recommendation: Remove phpMyAdmin from `docker-compose.yml` entirely; add it only in a `docker-compose.override.yml` (which is already in `.gitignore`) for local dev use

**MySQL port 3306 exposed to host in Docker Compose:**
- Risk: The database port is mapped to the host (`"3306:3306"`), allowing direct database connections from outside the container network
- Files: `docker-compose.yml` (line 43)
- Recommendation: Remove the port mapping for `db`; keep the database accessible only within `obra360_network`

**Weak fallback credentials in docker-compose.yml:**
- Risk: `DB_ROOT_PASSWORD:-root_secret` and `DB_PASSWORD:-obra360_pass` are committed fallback values. If `.env` is missing, the database starts with predictable credentials.
- Files: `docker-compose.yml` (lines 37–40)
- Recommendation: Remove fallback values; fail loudly if env vars are not set

---

## Tech Debt

**Dockerfile copies devDependencies into the production image:**
- Issue: `composer install --no-interaction --prefer-dist --optimize-autoloader` in the Dockerfile installs all `require-dev` packages (PHPUnit, Faker, Mockery, Pint, Pail, Pao) into the production image
- Files: `Dockerfile` (line 36)
- Impact: Larger image size; test tools and dev utilities available in production
- Fix approach: Use `--no-dev` flag: `composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev`

**No .dockerignore file:**
- Issue: `COPY . .` in the Dockerfile copies the entire project directory into the image, including `.env`, `database/database.sqlite`, `storage/logs/`, `.git/`, and local development artifacts
- Files: `Dockerfile` (line 33)
- Impact: Secrets in `.env` may be baked into the image; image is unnecessarily large; log files and SQLite database are included
- Fix approach: Create `.dockerignore` at project root excluding `.env`, `.git`, `node_modules`, `storage/logs`, `database/database.sqlite`, `tests/`, `docker/`

**PHP version mismatch between Dockerfile and CI:**
- Issue: `Dockerfile` uses `php:8.4-fpm` (line 1) but the GitHub Actions workflow uses PHP `8.3` (`.github/workflows/laravel.yml` line 24)
- Impact: Code that works in CI may fail in Docker (or vice versa) due to PHP 8.3 vs 8.4 behavioral differences
- Fix approach: Align both to the same version; PHP 8.3 is the current LTS target, so update Dockerfile to `php:8.3-fpm`

**Overly permissive CI permissions:**
- Issue: `chmod -R 777 storage bootstrap/cache` in the CI workflow
- Files: `.github/workflows/laravel.yml` (line 38)
- Impact: World-writable permissions are a security smell; production servers should use `755` as the Dockerfile already does
- Fix approach: Use `chmod -R 775` consistent with the Dockerfile

**database/database.sqlite committed to git:**
- Issue: A binary SQLite database file is tracked in version control
- Files: `database/database.sqlite`
- Impact: Pollutes git history with binary diffs; anyone who clones the repo gets a copy of the database file; CI creates a fresh one anyway
- Fix approach: Add `database/database.sqlite` to `.gitignore` (note: `database/.gitignore` currently only ignores `*.sqlite` pattern — verify it covers this file)

**composer.json package metadata not updated:**
- Issue: `"name": "laravel/laravel"` and `"description": "The skeleton application for the Laravel framework."` are still the default skeleton values
- Files: `composer.json` (lines 2–5)
- Impact: Low priority but creates confusion for tooling; the package name should be project-specific
- Fix approach: Update to `"name": "camacam/obra360"` and a meaningful description

**No queue worker in Docker Compose:**
- Issue: `QUEUE_CONNECTION=database` is configured but there is no `queue` service in `docker-compose.yml` running `php artisan queue:work`
- Files: `docker-compose.yml`, `.env.example` (line 38)
- Impact: Queued jobs will never be processed when running via Docker Compose
- Fix approach: Add a `queue` service to `docker-compose.yml` using the same `app` image with command `php artisan queue:work --tries=3`

**No OPcache configuration in PHP Docker container:**
- Issue: `docker/php/local.ini` only configures upload and memory limits; OPcache is not enabled or tuned
- Files: `docker/php/local.ini`
- Impact: PHP performance in the Docker container is lower than it should be for any load
- Fix approach: Add OPcache settings to `docker/php/local.ini` or a separate `docker/php/opcache.ini`

---

## Missing Critical Features

**No authorization layer:**
- Problem: No role/permission system exists. For a construction project portal (Obra360), role-based access (admin, engineer, client, contractor) is fundamental.
- Blocks: Any multi-tenant or role-specific feature cannot be safely built without this
- Recommendation: Install `spatie/laravel-permission` early; define roles before building feature routes

**No API routes file:**
- Problem: `routes/api.php` does not exist. The `bootstrap/app.php` only registers `web.php` and `console.php`. There is a JSON exception renderer for `api/*` requests in `bootstrap/app.php` (line 18) but no route file to back it up.
- Files: `bootstrap/app.php`
- Blocks: Any mobile client or SPA frontend integration

**No error tracking:**
- Problem: Errors only go to `storage/logs/laravel.log`. There is no external error tracking (Sentry, Flare, Bugsnag).
- Impact: Production errors are invisible unless someone manually tails log files
- Recommendation: Install `sentry/sentry-laravel` or `spatie/laravel-ignition` before going to production

**Mail is configured to `log` driver only:**
- Problem: `MAIL_MAILER=log` in `.env.example` — all mail is logged instead of sent. No real SMTP or transactional email provider is configured.
- Files: `.env.example` (line 51)
- Blocks: Password reset emails, email verification, any notification requiring email delivery

---

## Test Coverage Gaps

**Only stub tests exist:**
- What's not tested: Everything. The only tests are the default Laravel skeleton `ExampleTest` files that check if the homepage returns HTTP 200.
- Files: `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`
- Risk: Any future application code can break silently
- Priority: High — establish testing patterns before writing application code

**No code coverage threshold enforced:**
- What's not tested: Coverage is explicitly disabled in CI (`coverage: none` in `.github/workflows/laravel.yml` line 25)
- Risk: Coverage can drop to 0% with no CI gate
- Priority: Medium — at minimum, enable coverage reporting even without a hard threshold initially

**No static analysis in CI:**
- What's missing: No PHPStan or Larastan step in `.github/workflows/laravel.yml`
- Impact: Type errors, undefined variables, and logic bugs not caught before merge
- Priority: Medium — `laravel/pint` is installed for style; add PHPStan level 5+ before significant code lands

---

## Scaling Limits

**Database session driver under load:**
- Current capacity: The `sessions` table in MySQL with `SESSION_DRIVER=database` will become a hotspot under concurrent user load (every request reads and writes a session row)
- Limit: Degrades at high concurrency; no indexes beyond the primary key on `id`
- Scaling path: Switch to Redis (`SESSION_DRIVER=redis`) once a Redis container is added to Docker Compose

**Single log file (single driver):**
- Current capacity: All logs go to a single `storage/logs/laravel.log` file
- Limit: File can grow unbounded; no rotation configured for Docker environment
- Scaling path: Switch to `daily` log channel or configure log shipping (stdout/stderr in Docker) via `LOG_CHANNEL=stderr`

---

## Dependencies at Risk

**`laravel/pao` is an experimental/new package:**
- Risk: `laravel/pao` (`^1.0.6`) is an AI-agent-optimized test output package; it is very new and may have breaking changes or be abandoned
- Impact: Breaks `composer dev` script if removed or renamed
- Migration plan: It is a dev dependency only; can be removed without affecting production

**`image: phpmyadmin:latest` in Docker Compose:**
- Risk: Unpinned `latest` tag means the container image can change on `docker compose pull`, introducing unexpected breaking changes
- Files: `docker-compose.yml` (line 50)
- Fix approach: Pin to a specific version tag (e.g., `phpmyadmin:5.2`)

---

*Concerns audit: 2026-06-27*
