# Technology Stack

**Analysis Date:** 2026-06-27

## Languages

**Primary:**
- PHP 8.3+ (required by `composer.json`), PHP 8.4 used in `Dockerfile` (`FROM php:8.4-fpm`), PHP 8.5.6 on host - Server-side application logic, routing, ORM, queue processing

**Secondary:**
- JavaScript (ES Modules) - Frontend asset entry point at `resources/js/app.js`
- CSS via Tailwind v4 - Styling entry point at `resources/css/app.css`

## Runtime

**Environment:**
- PHP-FPM 8.4 (Docker container, image `php:8.4-fpm`)
- Node.js v26.0.0 (host, used for build tooling only — not a runtime server)

**Package Manager:**
- Composer 2 (latest, copied into Docker image via `COPY --from=composer:latest`)
- npm (Node package manager, lockfile: not committed — only `package.json` present)
- Lockfile: `composer.lock` present and committed; `package-lock.json` not detected

## Frameworks

**Core:**
- Laravel v13.17.0 (`laravel/framework`) - Full-stack MVC PHP framework; handles routing, ORM, queues, events, mail, broadcasting

**Testing:**
- PHPUnit 12.5.12 (`phpunit/phpunit`) - PHP unit/feature test runner; config at `phpunit.xml`
- Mockery 1.6 (`mockery/mockery`) - Mock object library for PHPUnit
- FakerPHP 1.23 (`fakerphp/faker`) - Test data generation

**Build/Dev:**
- Vite 8.0 (`vite`) - Frontend asset bundler; config at `vite.config.js`
- `laravel-vite-plugin` 3.1 - Bridges Vite with Laravel's asset manifest
- Tailwind CSS v4 (`tailwindcss`, `@tailwindcss/vite`) - Utility-first CSS framework integrated via Vite plugin
- Concurrently 9.0.1 (`concurrently`) - Runs multiple dev processes in parallel (artisan serve, queue, pail, vite)

**Dev Tooling:**
- Laravel Pint 1.27 (`laravel/pint`) - PHP code style fixer (based on PHP-CS-Fixer)
- Laravel Pail 1.2.5 (`laravel/pail`) - Real-time log tailing in terminal
- Laravel Pao 1.0.6 (`laravel/pao`) - Dev utility (process orchestration companion)
- Nunomaduro Collision 8.6 (`nunomaduro/collision`) - CLI error reporting

## Key Dependencies

**Critical:**
- `laravel/framework` v13.17.0 - Core framework; provides everything (Eloquent ORM, Blade templates, HTTP kernel, queues, events)
- `laravel/tinker` v3.0.2 - REPL for interactive shell access to the application
- `guzzlehttp/guzzle` 7.12.3 - HTTP client (bundled as Laravel dependency; available for outbound HTTP calls)
- `monolog/monolog` 3.10.0 - PSR-3 logging library used by Laravel's logging system
- `nesbot/carbon` 3.13.0 - Date/time library (Laravel datetime standard)

**Infrastructure:**
- `league/flysystem` 3.35.1 + `league/flysystem-local` 3.31.0 - Filesystem abstraction (local and S3 disks)
- `vlucas/phpdotenv` 5.6.3 - `.env` file parsing
- `symfony/routing`, `symfony/process`, `symfony/translation`, `symfony/uid` v8.1.0 - Symfony components bundled with Laravel
- `psy/psysh` v0.12.23 - Shell used by `artisan tinker`

## PHP Extensions (installed in Docker)

Installed via `docker-php-ext-install` in `Dockerfile`:
- `pdo_mysql` - MySQL database driver
- `mbstring` - Multibyte string handling
- `exif` - Image metadata
- `pcntl` - Process control (needed for queue workers)
- `bcmath` - Arbitrary precision math
- `gd` - Image processing
- `zip` - Archive handling

## Configuration

**Environment:**
- Configured via `.env` file (see `.env.example` for required variables)
- Key variables: `APP_NAME`, `APP_KEY`, `APP_ENV`, `APP_URL`, `DB_*`, `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`, `MAIL_MAILER`, `FILESYSTEM_DISK`
- Vite exposes `VITE_APP_NAME` to frontend via `VITE_` prefix convention

**Build:**
- Vite config: `vite.config.js` — entry points `resources/css/app.css` and `resources/js/app.js`; hot reload enabled; Bunny Fonts CDN used for `Instrument Sans`
- PHP runtime config: `docker/php/local.ini` — upload limit 64MB, max execution 300s, memory 256MB

**PHP ini (Docker):**
- `upload_max_filesize = 64M`
- `post_max_size = 64M`
- `max_execution_time = 300`
- `memory_limit = 256M`

## Platform Requirements

**Development:**
- Docker + Docker Compose (recommended path via `docker-compose.yml`)
- PHP 8.3+ and Composer 2 (for host-level artisan commands)
- Node.js v20+ and npm (for frontend build)
- Run via `composer run dev` (starts artisan serve, queue worker, pail log tail, and Vite in parallel)

**Production:**
- Docker: PHP-FPM 8.4 container (`obra360_app`) behind Nginx Alpine (`obra360_nginx`) on port 8000
- Nginx config: `docker/nginx/default.conf` — serves `public/` as document root, proxies PHP to `app:9000`
- No dedicated production Dockerfile variant detected; same `Dockerfile` used for all environments

---

*Stack analysis: 2026-06-27*
