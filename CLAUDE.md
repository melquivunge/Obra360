<!-- GSD:project-start source:PROJECT.md -->
## Project

**Portal de Acompanhamento de Obras (Obra360)**

A web portal for construction, renovation, and inspection companies to share project progress with their clients in a transparent, visual, and professional way. Companies manage clients, projects, phases, photos, expenses, documents, and updates; clients log in and see only their own projects with real-time progress, financials, and photo galleries. The goal is to replace repetitive WhatsApp messages with a structured, client-facing portal that builds trust and saves the company administrative time.

**Core Value:** A client logs in, immediately sees the current state of their construction project (phase, progress %, recent photos, money spent), and feels informed — without needing to contact the company.

### Constraints

- **Tech stack**: Laravel 13 + Blade + TailwindCSS v4 + MySQL — no SPA framework, server-side rendering
- **Auth**: Laravel Breeze or Fortify (built-in, not custom auth)
- **PDF**: DomPDF (barryvdh/laravel-dompdf) for report exports
- **Storage**: Local disk initially, prepared for S3 via Flysystem abstraction
- **PHP**: 8.4 (Docker), 8.3+ minimum per composer.json
- **Security**: CSRF, XSS, SQL injection protection built-in via Laravel; upload validation required; policies on all sensitive routes
<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->
## Technology Stack

## Languages
- PHP 8.3+ (required by `composer.json`), PHP 8.4 used in `Dockerfile` (`FROM php:8.4-fpm`), PHP 8.5.6 on host - Server-side application logic, routing, ORM, queue processing
- JavaScript (ES Modules) - Frontend asset entry point at `resources/js/app.js`
- CSS via Tailwind v4 - Styling entry point at `resources/css/app.css`
## Runtime
- PHP-FPM 8.4 (Docker container, image `php:8.4-fpm`)
- Node.js v26.0.0 (host, used for build tooling only — not a runtime server)
- Composer 2 (latest, copied into Docker image via `COPY --from=composer:latest`)
- npm (Node package manager, lockfile: not committed — only `package.json` present)
- Lockfile: `composer.lock` present and committed; `package-lock.json` not detected
## Frameworks
- Laravel v13.17.0 (`laravel/framework`) - Full-stack MVC PHP framework; handles routing, ORM, queues, events, mail, broadcasting
- PHPUnit 12.5.12 (`phpunit/phpunit`) - PHP unit/feature test runner; config at `phpunit.xml`
- Mockery 1.6 (`mockery/mockery`) - Mock object library for PHPUnit
- FakerPHP 1.23 (`fakerphp/faker`) - Test data generation
- Vite 8.0 (`vite`) - Frontend asset bundler; config at `vite.config.js`
- `laravel-vite-plugin` 3.1 - Bridges Vite with Laravel's asset manifest
- Tailwind CSS v4 (`tailwindcss`, `@tailwindcss/vite`) - Utility-first CSS framework integrated via Vite plugin
- Concurrently 9.0.1 (`concurrently`) - Runs multiple dev processes in parallel (artisan serve, queue, pail, vite)
- Laravel Pint 1.27 (`laravel/pint`) - PHP code style fixer (based on PHP-CS-Fixer)
- Laravel Pail 1.2.5 (`laravel/pail`) - Real-time log tailing in terminal
- Laravel Pao 1.0.6 (`laravel/pao`) - Dev utility (process orchestration companion)
- Nunomaduro Collision 8.6 (`nunomaduro/collision`) - CLI error reporting
## Key Dependencies
- `laravel/framework` v13.17.0 - Core framework; provides everything (Eloquent ORM, Blade templates, HTTP kernel, queues, events)
- `laravel/tinker` v3.0.2 - REPL for interactive shell access to the application
- `guzzlehttp/guzzle` 7.12.3 - HTTP client (bundled as Laravel dependency; available for outbound HTTP calls)
- `monolog/monolog` 3.10.0 - PSR-3 logging library used by Laravel's logging system
- `nesbot/carbon` 3.13.0 - Date/time library (Laravel datetime standard)
- `league/flysystem` 3.35.1 + `league/flysystem-local` 3.31.0 - Filesystem abstraction (local and S3 disks)
- `vlucas/phpdotenv` 5.6.3 - `.env` file parsing
- `symfony/routing`, `symfony/process`, `symfony/translation`, `symfony/uid` v8.1.0 - Symfony components bundled with Laravel
- `psy/psysh` v0.12.23 - Shell used by `artisan tinker`
## PHP Extensions (installed in Docker)
- `pdo_mysql` - MySQL database driver
- `mbstring` - Multibyte string handling
- `exif` - Image metadata
- `pcntl` - Process control (needed for queue workers)
- `bcmath` - Arbitrary precision math
- `gd` - Image processing
- `zip` - Archive handling
## Configuration
- Configured via `.env` file (see `.env.example` for required variables)
- Key variables: `APP_NAME`, `APP_KEY`, `APP_ENV`, `APP_URL`, `DB_*`, `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`, `MAIL_MAILER`, `FILESYSTEM_DISK`
- Vite exposes `VITE_APP_NAME` to frontend via `VITE_` prefix convention
- Vite config: `vite.config.js` — entry points `resources/css/app.css` and `resources/js/app.js`; hot reload enabled; Bunny Fonts CDN used for `Instrument Sans`
- PHP runtime config: `docker/php/local.ini` — upload limit 64MB, max execution 300s, memory 256MB
- `upload_max_filesize = 64M`
- `post_max_size = 64M`
- `max_execution_time = 300`
- `memory_limit = 256M`
## Platform Requirements
- Docker + Docker Compose (recommended path via `docker-compose.yml`)
- PHP 8.3+ and Composer 2 (for host-level artisan commands)
- Node.js v20+ and npm (for frontend build)
- Run via `composer run dev` (starts artisan serve, queue worker, pail log tail, and Vite in parallel)
- Docker: PHP-FPM 8.4 container (`obra360_app`) behind Nginx Alpine (`obra360_nginx`) on port 8000
- Nginx config: `docker/nginx/default.conf` — serves `public/` as document root, proxies PHP to `app:9000`
- No dedicated production Dockerfile variant detected; same `Dockerfile` used for all environments
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

## Naming Patterns
- PascalCase for all class names: `UserFactory`, `DatabaseSeeder`, `AppServiceProvider`, `Controller`
- Suffix classes with their role: `*Factory`, `*Seeder`, `*Controller`, `*ServiceProvider`, `*Migration`
- camelCase for method names: `definition()`, `unverified()`, `register()`, `boot()`
- Return type declarations are mandatory on all methods: `void`, `array`, `static`, `array<string, mixed>`
- snake_case with `test_` prefix: `test_that_true_is_true`, `test_the_application_returns_a_successful_response`
- Names describe expected behavior, not implementation
- camelCase for local variables
- `$password`, `$attributes`, `$request` follow standard Laravel naming
- PascalCase for PHP class files: `User.php`, `UserFactory.php`, `Controller.php`
- Migration files use timestamp prefix: `0001_01_01_000000_create_users_table.php`
- `App\` → `app/`
- `App\Models\` → `app/Models/`
- `App\Http\Controllers\` → `app/Http/Controllers/`
- `App\Providers\` → `app/Providers/`
- `Database\Factories\` → `database/factories/`
- `Database\Seeders\` → `database/seeders/`
- `Tests\` → `tests/`
## Code Style
- Laravel Pint (`laravel/pint` ^1.27) is installed as the code formatter
- Run: `./vendor/bin/pint`
- 4 spaces (no tabs), enforced via `.editorconfig`
- YAML/YML files use 2 spaces, except Docker Compose files which use 4
- LF (Unix-style), enforced via `.editorconfig`
- UTF-8
- Trimmed from all files except Markdown
- All files end with a final newline
## PHP Version Features
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
## Import Organization
## PHPDoc Blocks
## Model Design
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
## Controller Design
## Service Provider Design
## Application Bootstrap
## Error Handling
## Logging
## Comments
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

## Pattern Overview
- Request handling is fully server-side; no SPA or API-first architecture yet
- Blade templating engine renders all HTML on the server
- Frontend assets (CSS/JS) are compiled by Vite with Tailwind CSS
- Application is containerized via Docker (PHP-FPM + Nginx)
- Only one custom route and no custom controllers exist yet — this is a fresh skeleton
## Layers
- Purpose: Receives and routes HTTP requests
- Location: `public/index.php`, `routes/web.php`, `app/Http/Controllers/`
- Contains: Entry point, route definitions, controller base class
- Depends on: Bootstrap, Middleware, Models
- Used by: Browser / HTTP clients
- Purpose: Configures the application, registers service providers, middleware, and exception handlers
- Location: `bootstrap/app.php`, `bootstrap/providers.php`
- Contains: Application factory, routing bindings, middleware config, exception rendering rules
- Depends on: `vendor/laravel/framework`
- Used by: `public/index.php` (web), `artisan` (CLI)
- Purpose: Binds services and performs boot-time setup
- Location: `app/Providers/AppServiceProvider.php`, `bootstrap/providers.php`
- Contains: `AppServiceProvider` (currently empty register/boot)
- Depends on: Framework service container
- Used by: Bootstrap process
- Purpose: Represents database entities and business rules
- Location: `app/Models/`
- Contains: Eloquent models. Currently only `User.php`
- Depends on: Database connection, Eloquent ORM
- Used by: Controllers, Seeders, Factories
- Purpose: Renders HTML responses
- Location: `resources/views/`
- Contains: Blade templates. Currently only `welcome.blade.php`
- Depends on: Controllers passing data via `view()`
- Used by: Controllers / closures in `routes/web.php`
- Purpose: Schema definition and seed data
- Location: `database/migrations/`, `database/factories/`, `database/seeders/`
- Contains: Migrations for `users`, `cache`, `jobs`, `sessions` tables; `UserFactory`; `DatabaseSeeder`
- Depends on: Configured database connection (MySQL via Docker or SQLite locally)
- Used by: Artisan migrate/seed commands, tests
- Purpose: Artisan commands and scheduled tasks
- Location: `routes/console.php`, `artisan`
- Contains: `inspire` example command
- Depends on: Bootstrap
- Used by: `artisan` CLI entry point
- Purpose: Compiles CSS and JavaScript
- Location: `resources/css/app.css`, `resources/js/app.js`, `vite.config.js`
- Contains: Tailwind CSS, vanilla JS entry
- Depends on: Vite, `laravel-vite-plugin`, `@tailwindcss/vite`
- Used by: Blade templates via `@vite()` directive
## Data Flow
- Session state is stored in the `sessions` database table (default Laravel session driver)
- No client-side state management framework is present
## Key Abstractions
- Purpose: Active Record ORM pattern — maps PHP objects to database rows
- Examples: `app/Models/User.php`
- Pattern: Extend `Illuminate\Foundation\Auth\User` (for authenticatable) or `Illuminate\Database\Eloquent\Model`. PHP 8 attributes (`#[Fillable]`, `#[Hidden]`) are used instead of `$fillable`/`$hidden` arrays — this is Laravel 13 style.
- Purpose: Register bindings into the IoC container and perform boot-time configuration
- Examples: `app/Providers/AppServiceProvider.php`
- Pattern: Extend `Illuminate\Support\ServiceProvider`, implement `register()` and `boot()`
- Purpose: Version-controlled schema changes
- Examples: `database/migrations/0001_01_01_000000_create_users_table.php`
- Pattern: Anonymous class extending `Migration` with `up()` / `down()` methods
- Purpose: Handle HTTP requests and return responses
- Examples: `app/Http/Controllers/Controller.php` (base, abstract, currently empty)
- Pattern: Extend the base `Controller`. No resource controllers exist yet.
## Entry Points
- Location: `public/index.php`
- Triggers: Every web request via Nginx/PHP-FPM
- Responsibilities: Loads autoloader, bootstraps app, dispatches HTTP request
- Location: `artisan`
- Triggers: `php artisan <command>` invocation
- Responsibilities: Loads autoloader, bootstraps app, dispatches Artisan command
- Location: Registered in `bootstrap/app.php` as `health: '/up'`
- Triggers: GET `/up`
- Responsibilities: Returns 200 OK if application is running (used by Docker/load balancer health checks)
## Error Handling
- Configured in `bootstrap/app.php` via `->withExceptions(fn(Exceptions $e) => $e->shouldRenderJsonWhen(fn(Request $r) => $r->is('api/*')))`
- All other exceptions render HTML via the default Laravel error views (from the framework's vendor package)
- No custom exception classes or handlers exist yet
## Cross-Cutting Concerns
<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->
## Project Skills

No project skills found. Add skills to any of: `.claude/skills/`, `.agents/skills/`, `.cursor/skills/`, or `.github/skills/` with a `SKILL.md` index file.
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->



<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
