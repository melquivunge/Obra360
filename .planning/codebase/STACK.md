# Technology Stack

**Analysis Date:** 2026-08-20

## Languages

**Primary:**
- PHP 8.3+ (required by `composer.json`), PHP 8.4 (Docker), PHP 8.5 (HostGator production) - Server-side application logic, routing, ORM, queue processing, WebSocket handling
- JavaScript ES Modules - Frontend asset entry point at `resources/js/app.js`
- CSS via Tailwind v4 - Styling entry point at `resources/css/app.css`

## Runtime

**Environment:**
- PHP-FPM 8.4 (Docker container `obra360_app`, image `php:8.4-fpm`)
- Node.js v24 (Docker build stage), v24-26 (host development)
- Composer 2 (latest, COPY from composer official image in Dockerfile)
- npm (host only, used for build tooling — not a runtime server)

**Package Manager:**
- Composer 2 - PHP dependency manager
  - Lockfile: `composer.lock` (present and committed)
  - Vendor directory included in production deployment (`docker-compose.yml`, GitHub Actions workflow)
- npm - Node dependency manager
  - Lockfile: NOT committed (only `package.json` present)
  - `package-lock.json` excluded from deployment tar

## Frameworks

**Core:**
- Laravel v13.8+ (`laravel/framework`) - Full-stack MVC PHP framework; routing, ORM (Eloquent), queues, events, broadcasting, mail, validation
- Laravel Reverb ^1.10 (`laravel/reverb`) - First-party WebSocket server for real-time broadcasting; replaces Pusher as primary
- Laravel Breeze ^2.4 (`laravel/breeze`) - Scaffolded authentication (dev dependency only)

**Frontend:**
- Vite 8.0 (`vite`) - Frontend asset bundler; config at `vite.config.js`
- laravel-vite-plugin 3.1 (`laravel-vite-plugin`) - Bridges Vite with Laravel's asset manifest
- Tailwind CSS v4 (`tailwindcss`, `@tailwindcss/vite`) - Utility-first CSS framework integrated via Vite plugin
- Alpine.js v3.4.2 (`alpinejs`) - Minimal JavaScript framework for DOM manipulation; used for modals, toggles, form validation

**Broadcasting/WebSockets (Client-side):**
- Laravel-Echo ^2.3.7 (`laravel-echo`) - WebSocket event broadcasting client
- Pusher.js ^8.5.0 (`pusher-js`) - Pusher-protocol client that Laravel Echo uses to talk to Reverb (Reverb speaks the Pusher protocol) — not a fallback transport

**Testing:**
- PHPUnit 12.5.12 (`phpunit/phpunit`) - PHP unit/feature test runner; config at `phpunit.xml`
- Mockery 1.6 (`mockery/mockery`) - Mock object library for PHPUnit
- FakerPHP 1.23 (`fakerphp/faker`) - Test data generation

**Build/Dev:**
- Laravel Pint 1.27 (`laravel/pint`) - PHP code style fixer (PSR-12); run via `./vendor/bin/pint`
- Laravel Pail 1.2.5 (`laravel/pail`) - Real-time log tailing in terminal
- Laravel Pao 1.0.6 (`laravel/pao`) - Process orchestration companion
- Concurrently 9.0.1 (`concurrently`) - Runs multiple dev processes in parallel; orchestrates artisan serve, queue, pail, vite, reverb
- Nunomaduro Collision 8.6 (`nunomaduro/collision`) - CLI error reporting and formatting

## Key Dependencies

**Critical:**
- `laravel/framework` v13.8+ - Core framework; provides everything (Eloquent, Blade, HTTP kernel, queues, events, broadcasting)
- `laravel/reverb` ^1.10 - WebSocket server for real-time features (notifications, live updates)
- `guzzlehttp/guzzle` 7.12.3 - HTTP client; used for WhatsApp Business API integration via Meta Graph API

**Infrastructure & Logging:**
- `monolog/monolog` 3.10.0 - PSR-3 logging library used by Laravel's logging system
- `laravel/tinker` v3.0.2 - REPL for interactive shell access
- `vlucas/phpdotenv` 5.6.3 - `.env` file parsing

**Filesystem & Storage:**
- `league/flysystem` 3.35.1 - Filesystem abstraction layer
- `league/flysystem-local` 3.31.0 - Local disk driver
- (S3 not currently in composer.json but available via flysystem-aws-s3-v3)

**Utilities:**
- `nesbot/carbon` 3.13.0 - Date/time library; Laravel datetime standard
- `symfony/routing`, `symfony/process`, `symfony/translation`, `symfony/uid` v8.1.0 - Symfony components bundled with Laravel
- `psy/psysh` v0.12.23 - Shell REPL used by `artisan tinker`

**Web Push:**
- `minishlink/web-push` — **NOT installed**. `app/Channels/WebPushChannel.php` guards on `class_exists(WebPush::class)` and logs "WebPush skipped", so **no push notification is ever delivered** until `composer require minishlink/web-push` is run. Subscriptions are still captured and stored.

## PHP Extensions (Installed in Docker)

- `pdo_mysql` - MySQL database driver
- `mbstring` - Multibyte string handling (UTF-8)
- `exif` - Image metadata reading
- `pcntl` - Process control (required for queue workers)
- `bcmath` - Arbitrary precision math
- `gd` - Image processing (photo manipulation, thumbnails)
- `zip` - Archive handling (export/import)

## Configuration

**Environment:**
- Configured via `.env` file; template at `.env.example`
- Key variables: `APP_NAME`, `APP_KEY`, `APP_ENV`, `APP_URL`, `APP_DEBUG`, `APP_LOCALE`
- Database: `DB_CONNECTION` (mysql), `DB_HOST` (db), `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Queue: `QUEUE_CONNECTION` (database)
- Cache: `CACHE_STORE` (database)
- Session: `SESSION_DRIVER` (database), `SESSION_LIFETIME` (120 min), `SESSION_ENCRYPT` (true)
- Broadcasting: `BROADCAST_CONNECTION` (log in dev, reverb in production)
- Filesystem: `FILESYSTEM_DISK` (local)
- Mail: `MAIL_MAILER` (log in dev, configurable in production)
- Reverb: `REVERB_ALLOWED_ORIGINS`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_APP_ID`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`
- WebSockets (frontend): `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`
- WhatsApp: `WHATSAPP_PHONE_ID`, `WHATSAPP_TOKEN`
- Web Push VAPID: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`

**Frontend Build:**
- Vite config: `vite.config.js`
  - Entry points: `resources/css/app.css`, `resources/js/app.js`
  - Tailwind CSS v4 plugin enabled
  - Font: Bunny Fonts CDN (Instrument Sans, weights 400/500/600)
  - Hot reload enabled
  - Excludes view cache from watch

**PHP Runtime Config (Docker):**
- File: `docker/php/local.ini`
- Upload limit: `upload_max_filesize = 64M`
- Post data limit: `post_max_size = 64M`
- Execution: `max_execution_time = 300s`
- Memory: `memory_limit = 256M`

**Docker Compose:**
- File: `docker-compose.yml`
- Services: `app` (PHP-FPM), `webserver` (Nginx Alpine), `db` (MySQL 8.0)
- Network: `obra360_network` (bridge)
- Volume: `db_data` (persistent MySQL data)
- Port: 8000 (Nginx)

**Dockerfile (Multi-stage):**
- Frontend build stage: Node.js 24-Alpine, builds CSS/JS via Vite
- App stage: PHP 8.4-FPM, copies built frontend, installs PHP deps with `--no-dev`
- Exposes: Port 9000 (PHP-FPM)
- Entrypoint: `php-fpm`

## Platform Requirements

**Development:**
- Docker + Docker Compose (recommended via `docker-compose.yml`)
- PHP 8.3+ and Composer 2 (for host-level artisan commands)
- Node.js v24+ and npm (for frontend build)
- Run via `composer run dev` (starts artisan serve, queue worker, pail, vite, reverb in parallel via concurrently)

**Production (HostGator/cPanel):**
- PHP 8.5 (ea-php85 via cPanel API)
- SSH access (for GitHub Actions deployment)
- Deployed to: `obra360app.com` (via HostGator shared hosting)
- Deployment mechanism: GitHub Actions workflow builds assets, compresses files, uploads via SCP, extracts, and runs artisan commands

**CI/CD:**
- GitHub Actions (`.github/workflows/laravel.yml`)
- Test job: PHP 8.4, Node 24, SQLite database, runs Pint, tests, audits
- Deploy job: Builds production assets, deploys to HostGator via SSH (only on main branch push)

---

*Stack analysis: 2026-08-20*
