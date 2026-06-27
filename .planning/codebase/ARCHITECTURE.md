# Architecture

**Analysis Date:** 2026-06-27

## Pattern Overview

**Overall:** Laravel MVC (Model-View-Controller) — standard Laravel 13 skeleton with server-side rendering via Blade templates and a Vite-powered frontend asset pipeline.

**Key Characteristics:**
- Request handling is fully server-side; no SPA or API-first architecture yet
- Blade templating engine renders all HTML on the server
- Frontend assets (CSS/JS) are compiled by Vite with Tailwind CSS
- Application is containerized via Docker (PHP-FPM + Nginx)
- Only one custom route and no custom controllers exist yet — this is a fresh skeleton

## Layers

**HTTP Layer:**
- Purpose: Receives and routes HTTP requests
- Location: `public/index.php`, `routes/web.php`, `app/Http/Controllers/`
- Contains: Entry point, route definitions, controller base class
- Depends on: Bootstrap, Middleware, Models
- Used by: Browser / HTTP clients

**Bootstrap / Application Kernel:**
- Purpose: Configures the application, registers service providers, middleware, and exception handlers
- Location: `bootstrap/app.php`, `bootstrap/providers.php`
- Contains: Application factory, routing bindings, middleware config, exception rendering rules
- Depends on: `vendor/laravel/framework`
- Used by: `public/index.php` (web), `artisan` (CLI)

**Service Provider Layer:**
- Purpose: Binds services and performs boot-time setup
- Location: `app/Providers/AppServiceProvider.php`, `bootstrap/providers.php`
- Contains: `AppServiceProvider` (currently empty register/boot)
- Depends on: Framework service container
- Used by: Bootstrap process

**Domain / Model Layer:**
- Purpose: Represents database entities and business rules
- Location: `app/Models/`
- Contains: Eloquent models. Currently only `User.php`
- Depends on: Database connection, Eloquent ORM
- Used by: Controllers, Seeders, Factories

**View Layer:**
- Purpose: Renders HTML responses
- Location: `resources/views/`
- Contains: Blade templates. Currently only `welcome.blade.php`
- Depends on: Controllers passing data via `view()`
- Used by: Controllers / closures in `routes/web.php`

**Database Layer:**
- Purpose: Schema definition and seed data
- Location: `database/migrations/`, `database/factories/`, `database/seeders/`
- Contains: Migrations for `users`, `cache`, `jobs`, `sessions` tables; `UserFactory`; `DatabaseSeeder`
- Depends on: Configured database connection (MySQL via Docker or SQLite locally)
- Used by: Artisan migrate/seed commands, tests

**Console / CLI Layer:**
- Purpose: Artisan commands and scheduled tasks
- Location: `routes/console.php`, `artisan`
- Contains: `inspire` example command
- Depends on: Bootstrap
- Used by: `artisan` CLI entry point

**Frontend Asset Layer:**
- Purpose: Compiles CSS and JavaScript
- Location: `resources/css/app.css`, `resources/js/app.js`, `vite.config.js`
- Contains: Tailwind CSS, vanilla JS entry
- Depends on: Vite, `laravel-vite-plugin`, `@tailwindcss/vite`
- Used by: Blade templates via `@vite()` directive

## Data Flow

**HTTP Web Request:**

1. Browser sends request to Nginx (`docker/nginx/default.conf`, port 80)
2. Nginx routes all PHP requests via FastCGI to PHP-FPM at `app:9000`
3. `public/index.php` registers the Composer autoloader and bootstraps the app via `bootstrap/app.php`
4. `bootstrap/app.php` configures routing (web routes from `routes/web.php`), middleware, and exceptions, then calls `$app->handleRequest()`
5. Laravel Router matches the request against routes in `routes/web.php`
6. Matched route handler (closure or controller method) executes, optionally querying models
7. Handler returns a `view()` response, which compiles the Blade template from `resources/views/`
8. Response is sent back through Nginx to the browser

**CLI / Artisan Command:**

1. `artisan` script loads autoloader and bootstraps app via `bootstrap/app.php`
2. `$app->handleCommand()` dispatches to the Artisan kernel
3. Commands registered in `routes/console.php` (or discovered via `app/Console/`) are executed

**State Management:**
- Session state is stored in the `sessions` database table (default Laravel session driver)
- No client-side state management framework is present

## Key Abstractions

**Eloquent Model:**
- Purpose: Active Record ORM pattern — maps PHP objects to database rows
- Examples: `app/Models/User.php`
- Pattern: Extend `Illuminate\Foundation\Auth\User` (for authenticatable) or `Illuminate\Database\Eloquent\Model`. PHP 8 attributes (`#[Fillable]`, `#[Hidden]`) are used instead of `$fillable`/`$hidden` arrays — this is Laravel 13 style.

**Service Provider:**
- Purpose: Register bindings into the IoC container and perform boot-time configuration
- Examples: `app/Providers/AppServiceProvider.php`
- Pattern: Extend `Illuminate\Support\ServiceProvider`, implement `register()` and `boot()`

**Migration:**
- Purpose: Version-controlled schema changes
- Examples: `database/migrations/0001_01_01_000000_create_users_table.php`
- Pattern: Anonymous class extending `Migration` with `up()` / `down()` methods

**Controller:**
- Purpose: Handle HTTP requests and return responses
- Examples: `app/Http/Controllers/Controller.php` (base, abstract, currently empty)
- Pattern: Extend the base `Controller`. No resource controllers exist yet.

## Entry Points

**HTTP Entry Point:**
- Location: `public/index.php`
- Triggers: Every web request via Nginx/PHP-FPM
- Responsibilities: Loads autoloader, bootstraps app, dispatches HTTP request

**CLI Entry Point:**
- Location: `artisan`
- Triggers: `php artisan <command>` invocation
- Responsibilities: Loads autoloader, bootstraps app, dispatches Artisan command

**Health Check Endpoint:**
- Location: Registered in `bootstrap/app.php` as `health: '/up'`
- Triggers: GET `/up`
- Responsibilities: Returns 200 OK if application is running (used by Docker/load balancer health checks)

## Error Handling

**Strategy:** Laravel's built-in exception handler with a single customization — API requests (matching `api/*`) receive JSON error responses instead of HTML.

**Patterns:**
- Configured in `bootstrap/app.php` via `->withExceptions(fn(Exceptions $e) => $e->shouldRenderJsonWhen(fn(Request $r) => $r->is('api/*')))`
- All other exceptions render HTML via the default Laravel error views (from the framework's vendor package)
- No custom exception classes or handlers exist yet

## Cross-Cutting Concerns

**Logging:** Laravel's default stack driver — writes to `storage/logs/laravel.log`. Configured in `config/logging.php`.

**Validation:** No custom validation rules exist. Standard Laravel `$request->validate()` pattern will be used when controllers are added.

**Authentication:** `User` model extends `Authenticatable` and uses the `Notifiable` trait. Auth guards/providers are configured in `config/auth.php`. No auth routes or middleware are wired yet.

---

*Architecture analysis: 2026-06-27*
