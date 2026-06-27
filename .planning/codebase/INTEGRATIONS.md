# External Integrations

**Analysis Date:** 2026-06-27

## APIs & External Services

**Font Delivery:**
- Bunny Fonts CDN - Serves `Instrument Sans` typeface (weights 400, 500, 600)
  - SDK/Client: `laravel-vite-plugin/fonts` (the `bunny` helper imported in `vite.config.js`)
  - Auth: None (public CDN)
  - Config: `vite.config.js` lines 3 and 11–15

**Transactional Email (configured but not active):**
- Postmark - Email delivery
  - SDK: Laravel built-in transport (`postmark` driver in `config/mail.php`)
  - Auth: `POSTMARK_API_KEY` env var
  - Status: Defined in `config/services.php`; not active — current `MAIL_MAILER=log`

- Resend - Email delivery
  - SDK: Laravel built-in transport (`resend` driver in `config/mail.php`)
  - Auth: `RESEND_API_KEY` env var
  - Status: Defined in `config/services.php`; not active

- AWS SES - Email delivery
  - SDK: Laravel built-in transport (`ses` driver in `config/mail.php`)
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`
  - Status: Defined in `config/services.php`; not active

**Notifications (configured but not active):**
- Slack - Bot notifications
  - Auth: `SLACK_BOT_USER_OAUTH_TOKEN`, `SLACK_BOT_USER_DEFAULT_CHANNEL`
  - Config: `config/services.php`; also available as a log channel via `LOG_SLACK_WEBHOOK_URL` in `config/logging.php`
  - Status: Config present; not wired to any active notification

## Data Storage

**Databases:**
- MySQL 8.0 (active — Docker service `obra360_db`, image `mysql:8.0`)
  - Connection env vars: `DB_HOST=db`, `DB_PORT=3306`, `DB_DATABASE=obra360`, `DB_USERNAME=obra360_user`, `DB_PASSWORD=obra360_pass`
  - Client: Laravel Eloquent ORM via `pdo_mysql` extension
  - Config: `config/database.php`, connection name `mysql`
  - Default connection set in `.env.example`: `DB_CONNECTION=mysql`
  - Migrations table: `migrations`; jobs table: `jobs`; cache table: `cache`; sessions table: implied by `SESSION_DRIVER=database`

- SQLite (fallback/local dev without Docker)
  - File: `database/database.sqlite` (present in repo root under `database/`)
  - Config: `config/database.php`, connection name `sqlite` (framework default when `DB_CONNECTION` not set)

**File Storage:**
- Local filesystem (active default)
  - Private: `storage/app/private`
  - Public: `storage/app/public` (symlinked to `public/storage`)
  - Config: `config/filesystems.php`, disk `local` and `public`

- AWS S3 (configured, not active)
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`
  - Config: `config/filesystems.php`, disk `s3`
  - Status: Env vars present in `.env.example` but empty; `FILESYSTEM_DISK=local` is active

**Caching:**
- Database cache (active default)
  - Table: `cache` (created by migration `0001_01_01_000001_create_cache_table.php`)
  - Config: `config/cache.php`, store `database`; env var `CACHE_STORE=database`

- Redis (configured, not active)
  - Client: `phpredis` (env `REDIS_CLIENT=phpredis`)
  - Connection: `REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379`
  - Config: `config/database.php` redis section, `config/cache.php` redis store
  - Note: `phpredis` PHP extension is NOT installed in `Dockerfile` — activation requires extension install

- Memcached (configured, not active)
  - Config: `config/cache.php`, `MEMCACHED_HOST=127.0.0.1`

- AWS DynamoDB (configured, not active)
  - Auth: reuses `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`
  - Table: `DYNAMODB_CACHE_TABLE=cache`

## Authentication & Identity

**Auth Provider:**
- Laravel built-in session-based auth (custom/framework)
  - Guard: `web` (session driver)
  - Provider: Eloquent — `App\Models\User` (`app/Models/User.php`)
  - Password resets: database table `password_reset_tokens`
  - Bcrypt rounds: 12 (env `BCRYPT_ROUNDS=12`)
  - Config: `config/auth.php`
  - No OAuth, no Socialite, no Sanctum/Passport detected at this stage

## Queue Processing

**Queue Driver:**
- Database queue (active default)
  - Table: `jobs` (migration `0001_01_01_000002_create_jobs_table.php`)
  - Failed jobs table: `failed_jobs`
  - Env: `QUEUE_CONNECTION=database`
  - Config: `config/queue.php`

- Redis queue (configured, not active)
  - Config: `config/queue.php`, connection `redis`

- AWS SQS (configured, not active)
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`
  - Config: `config/queue.php`, connection `sqs`

## Session Storage

- Database driver (active)
  - Env: `SESSION_DRIVER=database`
  - Lifetime: 120 minutes
  - Config: `config/session.php`

## Monitoring & Observability

**Error Tracking:**
- Not detected (no Sentry, Bugsnag, Flare, or similar package)

**Logs:**
- Monolog 3.10 via Laravel's logging system
- Default channel: `stack` → `single` (writes to `storage/logs/laravel.log`)
- Available channels: `single`, `daily`, `slack`, `papertrail`, `stderr`, `syslog`, `errorlog`
- Papertrail (configured, not active): `PAPERTRAIL_URL`, `PAPERTRAIL_PORT` env vars
- Slack log channel (configured, not active): `LOG_SLACK_WEBHOOK_URL`
- Config: `config/logging.php`

**Dev Log Tailing:**
- Laravel Pail 1.2.5 — real-time log output during `composer run dev`

## CI/CD & Deployment

**Hosting:**
- Docker Compose stack (`docker-compose.yml`):
  - `obra360_app` — PHP-FPM 8.4 app container (port 9000 internal)
  - `obra360_nginx` — Nginx Alpine web server (port 8000 exposed)
  - `obra360_db` — MySQL 8.0 database (port 3306 exposed)
  - `obra360_phpmyadmin` — phpMyAdmin (port 8080 exposed, upload limit 64MB)
- All services on isolated `obra360_network` bridge network
- Database data persisted via Docker volume `db_data`

**CI Pipeline:**
- GitHub Actions pipeline referenced in commit history (`Add Laravel CI pipeline`)
- Pipeline definition file not read; likely at `.github/workflows/`

## Environment Configuration

**Required env vars (from `.env.example`):**
- `APP_KEY` — Laravel encryption key (generate with `php artisan key:generate`)
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — Database
- `APP_NAME`, `APP_ENV`, `APP_URL`, `APP_DEBUG` — Application identity

**Optional env vars (for future integrations):**
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` — S3 / SES / SQS / DynamoDB
- `POSTMARK_API_KEY`, `RESEND_API_KEY` — Transactional email
- `SLACK_BOT_USER_OAUTH_TOKEN`, `SLACK_BOT_USER_DEFAULT_CHANNEL` — Slack notifications
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` — Redis cache/queue
- `PAPERTRAIL_URL`, `PAPERTRAIL_PORT` — Remote log aggregation
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` — SMTP mail

**Secrets location:**
- `.env` file at project root (not committed); `.env.example` committed as template

## Webhooks & Callbacks

**Incoming:**
- None detected (no webhook routes, no Stripe/GitHub/Slack callback endpoints registered in `routes/web.php`)

**Outgoing:**
- None detected at current stage

## Database Administration

- phpMyAdmin — Web-based MySQL admin UI
  - URL: `http://localhost:8080` (via Docker)
  - Container: `obra360_phpmyadmin`
  - Connects to `obra360_db` service using root credentials

---

*Integration audit: 2026-06-27*
