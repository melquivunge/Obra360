# Codebase Structure

**Analysis Date:** 2026-06-27

## Directory Layout

```
Obra360/                          # Project root
├── app/                          # Core application PHP code (PSR-4: App\)
│   ├── Http/
│   │   └── Controllers/
│   │       └── Controller.php    # Abstract base controller
│   ├── Models/
│   │   └── User.php              # Eloquent User model
│   └── Providers/
│       └── AppServiceProvider.php
├── artisan                       # CLI entry point
├── bootstrap/                    # Application bootstrap and cache
│   ├── app.php                   # Application factory and configuration
│   ├── providers.php             # Registered service providers list
│   └── cache/                    # Auto-generated config/route caches (not committed)
├── composer.json                 # PHP dependency manifest
├── composer.lock                 # Locked PHP dependency versions
├── config/                       # All application configuration files
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database/                     # Database schema and seed data
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   └── 0001_01_01_000002_create_jobs_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── docker/                       # Docker service configuration
│   ├── nginx/
│   │   └── default.conf          # Nginx virtual host config
│   └── php/
│       └── local.ini             # PHP ini overrides
├── docker-compose.yml            # Multi-container orchestration
├── Dockerfile                    # PHP-FPM 8.4 image definition
├── package.json                  # Node/frontend dependency manifest
├── phpunit.xml                   # PHPUnit test suite configuration
├── public/                       # Web server document root (only directory exposed)
│   ├── index.php                 # HTTP entry point
│   ├── .htaccess                 # Apache rewrite rules
│   ├── favicon.ico
│   └── robots.txt
├── resources/                    # Uncompiled frontend and view assets
│   ├── css/
│   │   └── app.css               # Tailwind CSS entry point
│   ├── js/
│   │   └── app.js                # JavaScript entry point
│   └── views/
│       └── welcome.blade.php     # Default welcome page Blade template
├── routes/                       # Route definitions
│   ├── web.php                   # HTTP routes (web middleware group)
│   └── console.php               # Artisan command routes
├── storage/                      # Runtime-generated files (not committed)
│   ├── app/
│   │   ├── private/              # Private file uploads
│   │   └── public/               # Publicly accessible uploaded files
│   ├── framework/
│   │   ├── cache/data/           # Application cache
│   │   ├── sessions/             # File-based session storage
│   │   ├── testing/              # Test fixtures
│   │   └── views/                # Compiled Blade templates
│   └── logs/
│       └── laravel.log           # Application log output
├── tests/                        # Automated test suite (PSR-4: Tests\)
│   ├── Feature/
│   │   └── ExampleTest.php
│   └── Unit/
│       └── ExampleTest.php
├── vendor/                       # Composer-managed dependencies (not committed)
└── vite.config.js                # Vite asset bundler configuration
```

## Directory Purposes

**`app/`:**
- Purpose: All custom PHP application code following PSR-4 under the `App\` namespace
- Contains: Controllers, Models, Providers, and any future Services, Middleware, Jobs, Events, Listeners, Mail classes
- Key files: `app/Models/User.php`, `app/Http/Controllers/Controller.php`, `app/Providers/AppServiceProvider.php`

**`app/Http/Controllers/`:**
- Purpose: HTTP request handlers
- Contains: Controller classes. `Controller.php` is the abstract base all controllers should extend.
- Key files: `app/Http/Controllers/Controller.php`

**`app/Models/`:**
- Purpose: Eloquent ORM models representing database tables
- Contains: One class per table/entity
- Key files: `app/Models/User.php`

**`app/Providers/`:**
- Purpose: Service providers that register bindings and perform boot setup
- Contains: `AppServiceProvider.php` (the primary extension point for custom bindings)
- Key files: `app/Providers/AppServiceProvider.php`

**`bootstrap/`:**
- Purpose: Application bootstrapping — not application code
- Contains: `app.php` (the application factory), `providers.php` (provider list), `cache/` (generated cache, not committed)
- Key files: `bootstrap/app.php`, `bootstrap/providers.php`

**`config/`:**
- Purpose: Static application configuration, all read via `config()` helper
- Contains: One file per concern (app, auth, cache, database, filesystems, logging, mail, queue, services, session)
- Key files: `config/database.php`, `config/auth.php`, `config/app.php`

**`database/`:**
- Purpose: Database schema version control and test data
- Contains: Migrations (one class per schema change), Factories (fake data generators), Seeders (data population scripts)
- Key files: `database/migrations/0001_01_01_000000_create_users_table.php`, `database/factories/UserFactory.php`, `database/seeders/DatabaseSeeder.php`

**`docker/`:**
- Purpose: Docker service-specific configuration files mounted into containers
- Contains: Nginx virtual host config, PHP ini overrides
- Key files: `docker/nginx/default.conf`, `docker/php/local.ini`

**`public/`:**
- Purpose: The only directory exposed to the web — the Nginx document root
- Contains: `index.php` (entry point), compiled assets (generated by Vite into `public/build/`), `robots.txt`, `favicon.ico`
- Key files: `public/index.php`

**`resources/`:**
- Purpose: Source assets and views before compilation
- Contains: Blade templates (`views/`), CSS source (`css/`), JavaScript source (`js/`)
- Key files: `resources/views/welcome.blade.php`, `resources/css/app.css`, `resources/js/app.js`

**`routes/`:**
- Purpose: All application route definitions
- Contains: `web.php` (browser routes with session/CSRF middleware), `console.php` (Artisan command definitions)
- Key files: `routes/web.php`, `routes/console.php`

**`storage/`:**
- Purpose: Runtime-writable files — generated by the framework, not committed to git
- Contains: Compiled views, cache, sessions, logs, uploaded files
- Key files: `storage/logs/laravel.log`

**`tests/`:**
- Purpose: Automated test suite
- Contains: `Feature/` (HTTP/integration tests), `Unit/` (pure unit tests)
- Key files: `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`

## Key File Locations

**Entry Points:**
- `public/index.php`: HTTP entry point for all web requests
- `artisan`: CLI entry point for all Artisan commands

**Configuration:**
- `bootstrap/app.php`: Application factory — routing, middleware, exceptions wired here
- `bootstrap/providers.php`: Service provider registration list
- `config/app.php`: App name, environment, debug, timezone, locale
- `config/database.php`: Database connection definitions
- `config/auth.php`: Auth guard and provider definitions
- `.env`: Local environment values (not committed)
- `.env.example`: Template for required environment variables

**Core Logic:**
- `routes/web.php`: All HTTP route definitions
- `routes/console.php`: All Artisan command definitions
- `app/Http/Controllers/Controller.php`: Base controller

**Database:**
- `database/migrations/`: Schema migration files
- `database/seeders/DatabaseSeeder.php`: Root seeder (calls other seeders)
- `database/factories/UserFactory.php`: User model factory for tests/seeds

**Frontend:**
- `vite.config.js`: Vite build configuration (entry points, plugins)
- `resources/css/app.css`: CSS entry point (Tailwind)
- `resources/js/app.js`: JS entry point

**Infrastructure:**
- `Dockerfile`: PHP-FPM 8.4 container definition
- `docker-compose.yml`: Multi-service orchestration (app + nginx + db)
- `docker/nginx/default.conf`: Nginx virtual host

## Naming Conventions

**Files:**
- PHP classes: `PascalCase.php` matching the class name (e.g., `UserFactory.php`, `AppServiceProvider.php`)
- Blade templates: `kebab-case.blade.php` or `snake_case.blade.php` (e.g., `welcome.blade.php`)
- Config files: `snake_case.php` matching Laravel convention (e.g., `filesystems.php`)
- Migrations: `YYYY_MM_DD_HHMMSS_verb_noun_table.php` (e.g., `0001_01_01_000000_create_users_table.php`)

**Directories:**
- PHP namespaces map directly to directory names in `PascalCase` under `app/`
- All other directories use `snake_case` or `lowercase` (e.g., `resources/views/`, `database/factories/`)

**Classes:**
- Controllers: `PascalCaseController` (e.g., `UserController`)
- Models: `PascalCase` singular noun (e.g., `User`, `Project`)
- Providers: `PascalCaseServiceProvider`
- Factories: `PascalCaseFactory` (e.g., `UserFactory`)
- Seeders: `PascalCaseSeeder` (e.g., `DatabaseSeeder`)

**Namespaces:**
- App code: `App\Http\Controllers\`, `App\Models\`, `App\Providers\`
- Tests: `Tests\Feature\`, `Tests\Unit\`
- Factories: `Database\Factories\`
- Seeders: `Database\Seeders\`

## Where to Add New Code

**New HTTP Controller:**
- Implementation: `app/Http/Controllers/YourNameController.php`
- Register route: `routes/web.php` or a new `routes/api.php` file
- Must extend: `App\Http\Controllers\Controller`

**New Eloquent Model:**
- Implementation: `app/Models/YourModel.php`
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_your_models_table.php`
- Factory: `database/factories/YourModelFactory.php`

**New Service / Business Logic Class:**
- Implementation: `app/Services/YourService.php` (create the `Services/` directory)
- Register in container: `app/Providers/AppServiceProvider.php` → `register()` method

**New Middleware:**
- Implementation: `app/Http/Middleware/YourMiddleware.php`
- Register: `bootstrap/app.php` → `->withMiddleware()` closure

**New Artisan Command:**
- Implementation: `app/Console/Commands/YourCommand.php`
- Register: `routes/console.php` via `Artisan::command()` or auto-discovered

**New Blade View:**
- Implementation: `resources/views/your-view.blade.php`
- For feature groups, create subdirectories: `resources/views/obras/index.blade.php`

**New Feature Test:**
- Implementation: `tests/Feature/YourFeatureTest.php`

**New Unit Test:**
- Implementation: `tests/Unit/YourClassTest.php`

**New Configuration Value:**
- Add to: the relevant file in `config/` (e.g., `config/services.php` for third-party APIs)
- Reference via: `config('services.your_key')`
- Environment variable: add to `.env.example`

## Special Directories

**`vendor/`:**
- Purpose: Composer-managed PHP dependencies
- Generated: Yes (by `composer install`)
- Committed: No

**`node_modules/`:**
- Purpose: npm-managed frontend dependencies
- Generated: Yes (by `npm install`)
- Committed: No

**`storage/`:**
- Purpose: Runtime-writable files (cache, sessions, logs, compiled views, uploads)
- Generated: Partially (framework creates subdirs; some files are committed as `.gitkeep`)
- Committed: Only `.gitkeep` placeholders; actual content is not committed

**`bootstrap/cache/`:**
- Purpose: Cached config, routes, services for production performance
- Generated: Yes (by `php artisan config:cache`, `route:cache`, etc.)
- Committed: No

**`public/build/`:**
- Purpose: Compiled frontend assets output by Vite
- Generated: Yes (by `npm run build`)
- Committed: No (excluded by `.gitignore`)

**`.planning/`:**
- Purpose: GSD planning documents for development workflow
- Generated: No (manually maintained)
- Committed: Yes

---

*Structure analysis: 2026-06-27*
