<!-- GSD:project-start source:PROJECT.md -->
## Project

**Portal de Acompanhamento de Obras (Obra360)**

A web portal for construction, renovation, and inspection companies to share project progress with their clients in a transparent, visual, and professional way. Companies manage clients, projects, phases, photos, expenses, documents, and updates; clients log in and see only their own projects with real-time progress, financials, and photo galleries. The goal is to replace repetitive WhatsApp messages with a structured, client-facing portal that builds trust and saves the company administrative time.

**Core Value:** A client logs in, immediately sees the current state of their construction project (phase, progress %, recent photos, money spent), and feels informed — without needing to contact the company.

### Constraints

- **Tech stack**: Laravel 13 + Blade + TailwindCSS v4 + MySQL — no SPA framework, server-side rendering
- **Auth**: Laravel Breeze (Blade) — installed
- **PDF**: DomPDF (barryvdh/laravel-dompdf) — installed and in use by the four reports
- **Storage**: Private local disk via Flysystem; media served through authenticated routes; S3-ready
- **PHP**: 8.5 in production (cPanel ea-php85), 8.4 in Docker, 8.3+ minimum per composer.json
- **Security**: CSRF, XSS, SQL injection protection built-in via Laravel; upload validation required; policies on all sensitive routes
<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->
## Technology Stack

## Languages
- PHP 8.3+ (required by `composer.json`), PHP 8.4 (Docker), PHP 8.5 (HostGator production) - Server-side application logic, routing, ORM, queue processing, WebSocket handling
- JavaScript ES Modules - Frontend asset entry point at `resources/js/app.js`
- CSS via Tailwind v4 - Styling entry point at `resources/css/app.css`
## Runtime
- PHP-FPM 8.4 (Docker container `obra360_app`, image `php:8.4-fpm`)
- Node.js v24 (Docker build stage), v24-26 (host development)
- Composer 2 (latest, COPY from composer official image in Dockerfile)
- npm (host only, used for build tooling — not a runtime server)
- Composer 2 - PHP dependency manager
- npm - Node dependency manager
## Frameworks
- Laravel v13.8+ (`laravel/framework`) - Full-stack MVC PHP framework; routing, ORM (Eloquent), queues, events, broadcasting, mail, validation
- Laravel Reverb ^1.10 (`laravel/reverb`) - First-party WebSocket server for real-time broadcasting; replaces Pusher as primary
- Laravel Breeze ^2.4 (`laravel/breeze`) - Scaffolded authentication (dev dependency only)
- Vite 8.0 (`vite`) - Frontend asset bundler; config at `vite.config.js`
- laravel-vite-plugin 3.1 (`laravel-vite-plugin`) - Bridges Vite with Laravel's asset manifest
- Tailwind CSS v4 (`tailwindcss`, `@tailwindcss/vite`) - Utility-first CSS framework integrated via Vite plugin
- Alpine.js v3.4.2 (`alpinejs`) - Minimal JavaScript framework for DOM manipulation; used for modals, toggles, form validation
- Laravel-Echo ^2.3.7 (`laravel-echo`) - WebSocket event broadcasting client
- Pusher.js ^8.5.0 (`pusher-js`) - Pusher-protocol client that Laravel Echo uses to talk to Reverb (Reverb speaks the Pusher protocol) — not a fallback transport
- PHPUnit 12.5.12 (`phpunit/phpunit`) - PHP unit/feature test runner; config at `phpunit.xml`
- Mockery 1.6 (`mockery/mockery`) - Mock object library for PHPUnit
- FakerPHP 1.23 (`fakerphp/faker`) - Test data generation
- Laravel Pint 1.27 (`laravel/pint`) - PHP code style fixer (PSR-12); run via `./vendor/bin/pint`
- Laravel Pail 1.2.5 (`laravel/pail`) - Real-time log tailing in terminal
- Laravel Pao 1.0.6 (`laravel/pao`) - Process orchestration companion
- Concurrently 9.0.1 (`concurrently`) - Runs multiple dev processes in parallel; orchestrates artisan serve, queue, pail, vite, reverb
- Nunomaduro Collision 8.6 (`nunomaduro/collision`) - CLI error reporting and formatting
## Key Dependencies
- `laravel/framework` v13.8+ - Core framework; provides everything (Eloquent, Blade, HTTP kernel, queues, events, broadcasting)
- `laravel/reverb` ^1.10 - WebSocket server for real-time features (notifications, live updates)
- `guzzlehttp/guzzle` 7.12.3 - HTTP client; used for WhatsApp Business API integration via Meta Graph API
- `monolog/monolog` 3.10.0 - PSR-3 logging library used by Laravel's logging system
- `laravel/tinker` v3.0.2 - REPL for interactive shell access
- `vlucas/phpdotenv` 5.6.3 - `.env` file parsing
- `league/flysystem` 3.35.1 - Filesystem abstraction layer
- `league/flysystem-local` 3.31.0 - Local disk driver
- (S3 not currently in composer.json but available via flysystem-aws-s3-v3)
- `nesbot/carbon` 3.13.0 - Date/time library; Laravel datetime standard
- `symfony/routing`, `symfony/process`, `symfony/translation`, `symfony/uid` v8.1.0 - Symfony components bundled with Laravel
- `psy/psysh` v0.12.23 - Shell REPL used by `artisan tinker`
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
- Vite config: `vite.config.js`
- File: `docker/php/local.ini`
- Upload limit: `upload_max_filesize = 64M`
- Post data limit: `post_max_size = 64M`
- Execution: `max_execution_time = 300s`
- Memory: `memory_limit = 256M`
- File: `docker-compose.yml`
- Services: `app` (PHP-FPM), `webserver` (Nginx Alpine), `db` (MySQL 8.0)
- Network: `obra360_network` (bridge)
- Volume: `db_data` (persistent MySQL data)
- Port: 8000 (Nginx)
- Frontend build stage: Node.js 24-Alpine, builds CSS/JS via Vite
- App stage: PHP 8.4-FPM, copies built frontend, installs PHP deps with `--no-dev`
- Exposes: Port 9000 (PHP-FPM)
- Entrypoint: `php-fpm`
## Platform Requirements
- Docker + Docker Compose (recommended via `docker-compose.yml`)
- PHP 8.3+ and Composer 2 (for host-level artisan commands)
- Node.js v24+ and npm (for frontend build)
- Run via `composer run dev` (starts artisan serve, queue worker, pail, vite, reverb in parallel via concurrently)
- PHP 8.5 (ea-php85 via cPanel API)
- SSH access (for GitHub Actions deployment)
- Deployed to: `obra360app.com` (via HostGator shared hosting)
- Deployment mechanism: GitHub Actions workflow builds assets, compresses files, uploads via SCP, extracts, and runs artisan commands
- GitHub Actions (`.github/workflows/laravel.yml`)
- Test job: PHP 8.4, Node 24, SQLite database, runs Pint, tests, audits
- Deploy job: Builds production assets, deploys to HostGator via SSH (only on main branch push)
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

## Naming Patterns
- PascalCase for all class files: `User.php`, `UserFactory.php`, `ProjectController.php`, `UserManagementTest.php`
- snake_case for configuration files: `phpunit.xml`, `docker-compose.yml`
- kebab-case for Blade components: `text-input.blade.php`, `primary-button.blade.php`, `data-table.blade.php`
- Example structure: `app/Models/User.php`, `app/Http/Controllers/UserController.php`, `app/Http/Requests/StoreUserRequest.php`, `database/factories/UserFactory.php`
- camelCase for all method names: `definition()`, `unverified()`, `authenticate()`, `storePhoto()`, `updatePhase()`, `isAdmin()`, `totalSpent()`
- Method names describe expected behavior: `canAuthenticate`, `isFinancial`, `invalidateUserSessions`, `guardCriticalAdminChange`
- Test methods: `test_` prefix with snake_case, descriptive assertions. Example: `test_admin_can_create_user_with_valid_data()`, `test_client_cannot_download_internal_document()`, `test_inactive_user_cannot_login_with_correct_credentials()`
- camelCase for local variables: `$user`, `$response`, `$admin`, `$wouldDeactivate`, `$isSelfChange`, `$isLastActiveAdmin`
- Private methods prefixed with underscore: `private function invalidateUserSessions()`, `private function guardCriticalAdminChange()`
- Model constants use UPPER_SNAKE_CASE: `User::ROLE_ADMIN`, `User::ROLE_CLIENT`, `Project::STATUSES`
- PascalCase for all classes: `User`, `UserFactory`, `UserController`, `StoreUserRequest`, `AuthenticationTest`
- Suffix classes with role: `*Factory`, `*Controller`, `*Request`, `*Test`
- Nested resource controllers: `Admin\ProjectController`, `Admin\ClientController`, `Client\ProjectController` (namespace prefix follows functionality)
- Route names in English, kebab-case: `route('users.index')`, `route('admin.projects.show')`, `route('portal.dashboard')`
- Prefix groups with role: `admin.*`, `portal.*`
- Nested resource naming: `admin.projects.phases.store`, `admin.projects.documents.download`
- **URL paths in Portuguese:** routes/web.php uses Portuguese for user-facing URLs: `/clientes`, `/obras`, `/mensagens`, `/fotos`, `/documentos`, `/despesas`, `/pagamentos`, `/fases`, `/configuracoes` — but route **names** remain English for internal references
## Code Style
- Laravel Pint (`laravel/pint` v1.27) is the official formatter
- Enforced in CI via `./vendor/bin/pint --test`
- Run manually: `./vendor/bin/pint`
- 4 spaces indentation (no tabs), enforced via `.editorconfig`
- LF (Unix-style line endings), enforced via `.editorconfig`
- UTF-8 encoding
- Files trimmed of trailing whitespace, final newline on all files
- No separate ESLint or Prettier config detected; Laravel Pint is the sole PHP formatter
- Frontend CSS/JS uses Vite default formatting with no explicit linter enforced in CI
- Tailwind CSS v4 with Vite plugin (`@tailwindcss/vite`)
- PHP 8.3+ required by composer.json; 8.4 used in Docker, 8.5 on HostGator production
- PHP 8 **Attributes** used extensively in Models instead of property arrays:
- Constructor property promotion supported: not used extensively, but available
- Match expressions supported: not detected in codebase
- Named arguments supported: commonly used in test assertions
## Import Organization
- `App\` maps to `app/`
- `App\Models\` maps to `app/Models/`
- `App\Http\Controllers\` maps to `app/Http/Controllers/`
- `App\Http\Requests\` maps to `app/Http/Requests/`
- `App\Providers\` maps to `app/Providers/`
- `Database\Factories\` maps to `database/factories/`
- `Database\Seeders\` maps to `database/seeders/`
- `Tests\` maps to `tests/`
- ES Modules (import/export)
- Alpine.js imported directly: `import Alpine from 'alpinejs'`
- Laravel Echo for WebSocket: `import Echo from 'laravel-echo'`
- Pusher for WebSocket transport: `import Pusher from 'pusher-js'`
## Return Type Declarations
- Primitive types: `: void`, `: string`, `: bool`, `: int`, `: float`
- Array types: `: array`, `: array<string, mixed>`, `: array<string, string>`
- Model types: `: User`, `: View`, `: RedirectResponse`
- Static return: `: static` (used in factories)
- Union types supported: `: User|null` (though not extensively used)
## PHPDoc Blocks
- Used on classes, public methods, and complex logic
- JSDoc-style `@return`, `@param`, `@throws`
- Template types documented: `@extends Factory<User>`, `@use HasFactory<UserFactory>`
- Example:
- Minimal inline comments; code is expected to be self-documenting
- Auth logic marked with references: `// AUTH-01`, `// T-04-02` (from traceability docs)
- Comments used for non-obvious business logic, e.g., "Block inactive users — log out immediately so no session is persisted"
## Model Design
- All models extend `Illuminate\Database\Eloquent\Model` or `Illuminate\Foundation\Auth\User`
- Use PHP 8 Attributes for `#[Fillable]`, `#[Hidden]`, `#[Cast]` declarations (not property arrays)
- Explicit `protected function casts(): array` for type casting
#[Fillable(['name', 'email', 'password', 'role', 'phone', 'active', 'client_id'])]
#[Hidden(['password', 'remember_token'])]
- Return type declarations mandatory: `: BelongsTo`, `: HasMany`, `: HasOne`
- Methods chain fluent interface: `return $this->belongsTo(Client::class);`
- Eager loading constraints: `$this->hasMany(Photo::class)->latest()`
- Use `SoftDeletes` trait on models requiring archival: `User`, `Project`, `Photo`, `Document`, etc.
- Accessed via `Model::withTrashed()->find()` or `Model::onlyTrashed()`
## Controller Design
- Extend base `Controller` class
- Use `AuthorizesRequests` trait for policy checks
- Method names map to RESTful verbs: `index()`, `show()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`
- Additional actions: `cover()`, `photo()`, `storePhoto()`, `toggleActive()`, `markPaid()`
- `$this->authorize()` called at method start to check policies
- Returns 403 if not authorized
- Policies defined in `app/Policies/` (if complex authorization required)
- Middleware enforced at route level via `middleware(['auth', 'role:admin'])` in routes/web.php
## Request Validation
- Use `FormRequest` classes extending `Illuminate\Foundation\Http\FormRequest`
- Location: `app/Http/Requests/`
- Implement `authorize(): bool` to check if user can make request
- Implement `rules(): array` with validation rules
- Implement `messages(): array` with Portuguese error messages
- Use array syntax for rules (not pipe-delimited strings): `['required', 'email', 'unique:users,email']`
- Use `Rule::in()` for enum-style validation: `Rule::in([User::ROLE_ADMIN, User::ROLE_CLIENT])`
- File validation with MIME types: `mimetypes:application/pdf` for documents
- Password confirmation: `'password' => ['required', 'confirmed', 'min:8']`
## Error Handling
- Exception rendering configured in `bootstrap/app.php` via `->withExceptions()`
- No custom exception classes defined; use Laravel's built-in exceptions
- Framework automatically handles HTTP exceptions (401, 403, 404, 422, 500)
- JSON responses sent only for `api/*` routes (none currently exist)
- `abort(403)` for permission denied (used in middleware)
- `abort(422, 'Custom message')` for validation failures with specific messages (e.g., cannot demote last admin)
- `ValidationException::withMessages([...])` for form validation errors with Portuguese messages
- Soft failures: inactive users logged out immediately during auth without persisting session
## Logging
- Laravel's logging system (Monolog-based)
- PSR-3 interface via `Illuminate\Support\Facades\Log`
- Real-time log tailing in dev: `php artisan pail` (or `composer run dev` includes it)
- Log files: `storage/logs/` (one file per day by default)
- Not extensively logged in current codebase (focus on core auth/authorization)
- Available for custom logging via `Log::info()`, `Log::error()`, `Log::warning()`
- Queue job failures logged automatically by Laravel queue system
## Middleware
- Located in `app/Http/Middleware/`
- Implement `handle(Request $request, Closure $next): Response`
- Return `$next($request)` to pass to next middleware or controller
- Return HTTP response to short-circuit pipeline
- Registered in `bootstrap/app.php` via `$middleware->alias()` or `appendToGroup()`
- Applied in routes via `middleware(['role:admin,manager'])` syntax
## Blade Components
- Located in `resources/views/components/`
- Kebab-case filenames: `text-input.blade.php`, `primary-button.blade.php`, `data-table.blade.php`
- Use `@props` to declare expected attributes
- Spread attributes with `{{ $attributes->merge([...]) }}` for TailwindCSS classes
- Slot content via `{{ $slot }}`
- Invoked with `<x-component-name>` tag
- Pass attributes: `<x-text-input :disabled="$isDisabled" />`
- Pass slots: `<x-data-table><thead>...</thead></x-data-table>`
## Language & Localization
- **PHP identifiers (classes, methods, variables):** English — `User`, `Project`, `storePhoto()`, `$manager`
- **User-facing routes/URLs:** Portuguese — `/obras`, `/clientes`, `/fotos`, `/mensagens`, `/pagamentos`
- **UI/Form messages:** Portuguese — "Este campo é obrigatório", "Sua conta está inativa", "Usuário criado com sucesso"
- **Route names:** English — `route('admin.projects.show')`, `route('portal.dashboard')`
- Laravel `resources/lang/pt/` and `resources/lang/en/` (if applicable)
- Laravel's `trans()` function used for rate-limit messages: `trans('auth.throttle', ['seconds' => $seconds])`
## Database & Query Patterns
- Fluent interface: `User::query()->latest()->paginate(15)`
- Method chaining for filtering: `User::where('role', User::ROLE_ADMIN)->where('active', true)`
- Soft delete queries: `User::withTrashed()->find()`, `User::onlyTrashed()`
## Special Patterns
- User model constants: `User::ROLE_ADMIN`, `User::ROLE_CLIENT`, `User::ROLE_MANAGER`, `User::ROLE_FINANCIAL`
- Helper methods: `$user->isAdmin()`, `$user->isClient()`, `$user->isManager()`, `$user->isFinancial()`
- Middleware: `middleware(['role:admin,manager'])` enforces multiple roles
- Policies in routes: `$this->authorize('viewAny', User::class)` checks policy rules
- Built-in login throttling: 5 attempts per email|IP before lockout
- Configured via `RateLimiter::tooManyAttempts()` and `RateLimiter::hit()` in LoginRequest
- Database-backed sessions via `config('session.driver')` 
- Manual session invalidation: `DB::table('sessions')->where('user_id', $user->id)->delete()`
- Used when deactivating users to force logout
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

## Pattern Overview
- Server-side rendering (Blade templating) — no SPA/API-first architecture
- Role-based authorization (admin, manager, financial, client) enforced at middleware and trait levels
- Multi-tenant portal (clients and projects isolated per user role)
- Real-time messaging via Laravel Broadcasting (private channels per project)
- Event-driven notifications (ProjectUpdateObserver → ProjectUpdateNotification → WhatsApp/WebPush channels)
- Soft deletes on core models (User, Project, Client) for audit compliance
- Filesystem abstraction ready for S3 (local disk primary, public/private storage separated)
## Layers
- Purpose: Receives requests, routes to controllers, enforces middleware
- Location: `routes/web.php`, `bootstrap/app.php`
- Contains: Route definitions, middleware aliases (`role`, `EnsureUserIsActive`), exception config
- Depends on: Middleware, Controllers, Models
- Used by: Browser / HTTP clients
- Purpose: Authentication, role-based access control, user status validation
- Location: `app/Http/Middleware/`
- Contains: `EnsureUserRole.php` (role gate at route level), `EnsureUserIsActive.php` (logout inactive users)
- Depends on: Authenticatable User model, session state
- Used by: Route middleware groups
- Purpose: Fine-grained project-level access control
- Location: `app/Traits/AuthorizesProjectAccess.php`, `app/Policies/UserPolicy.php`
- Contains: Manager/financial role checks against project.manager_id, Laravel Policy pattern
- Depends on: Authenticated user, Project model relationships
- Used by: Controllers (Admin\ProjectController, Client\ProjectController)
- Purpose: Handles HTTP requests, orchestrates business logic, returns views/JSON
- Location: `app/Http/Controllers/`
- Contains: Four namespaces — `Admin/` (6 controllers), `Client/` (2 controllers), `Auth/` (8 controllers), `Portal/` (1 controller)
- Depends on: Models, Traits, Requests, Views
- Used by: Routes
- Purpose: Represents database entities, relationships, business logic
- Location: `app/Models/`
- Contains: 12 models — User, Client, Project, ProjectPhase, Photo, Expense, Document, ProjectUpdate, Message, Payment, Setting, PushSubscription
- Depends on: Database, Factory definitions
- Used by: Controllers, Seeders, Events/Notifications, Observers
- Purpose: Triggers side effects (notifications, broadcasts) when domain events occur
- Location: `app/Events/` (MessageSent), `app/Notifications/ProjectUpdateNotification`, `app/Observers/ProjectUpdateObserver`
- Contains: Event classes (ShouldBroadcast), Notification class (ShouldQueue), Eloquent observers
- Depends on: Models, Channels, Settings
- Used by: Controllers (store methods), Observers (ProjectUpdate.created)
- Purpose: Authorizes real-time message delivery to WebSocket subscribers
- Location: `routes/channels.php`
- Contains: Role-based channel guards (project.{projectId} gated by admin/manager/client status)
- Depends on: User model roles, Project relationships
- Used by: Events (MessageSent broadcasts to project channel)
- Purpose: Delivers notifications via custom transport (WhatsApp, WebPush)
- Location: `app/Channels/WebPushChannel.php`, `app/Channels/WhatsAppChannel.php`
- Contains: Custom channel implementations extending Illuminate\Notifications\Channels
- Depends on: External APIs (WhatsApp, Web Push service), User phone/push subscriptions
- Used by: ProjectUpdateNotification.via()
- Purpose: Renders HTML responses via Blade templating
- Location: `resources/views/`
- Contains: 40+ Blade templates organized by role (admin/, client/, auth/, profile/)
- Depends on: Controllers passing data, Components (AdminLayout, ClientLayout, AppLayout, GuestLayout)
- Used by: Controllers, View Composers
- Purpose: Reusable layout shells and pre-computed view data
- Location: `app/View/Components/`, `app/Http/View/Composers/`
- Contains: Layout components (AdminLayout, ClientLayout) render Blade templates; ClientLayoutComposer queries messages/updates/projects with caching
- Depends on: Models, Cache facade, Routes
- Used by: Views (client.blade.php uses x-client-layout component)
- Purpose: Bootstrap application services, define authorization gates, register observers
- Location: `app/Providers/AppServiceProvider.php`
- Contains: ProjectUpdateObserver registration, Gate definitions (access-admin, manage-users, access-financial), ClientLayoutComposer binding
- Depends on: Models, Observers, View facade
- Used by: Application bootstrap (routes/web.php loads via bootstrap/app.php)
- Purpose: Schema versioning and test data generation
- Location: `database/migrations/`, `database/factories/`, `database/seeders/`
- Contains: Migrations for `users`, `cache`, `jobs`, `sessions` tables; `UserFactory`; `DatabaseSeeder`
- Depends on: Schema facade, Models
- Used by: Artisan migrate/seed commands, tests
- Purpose: Artisan commands for maintenance tasks
- Location: `app/Console/Commands/`, `routes/console.php`
- Contains: MigratePublicProjectMedia (migrate legacy media from public to private storage)
- Depends on: Storage facade, Models
- Used by: `php artisan obra360:media:migrate-public`
## Data Flow
- Session state stored in database (`sessions` table, Laravel default)
- Client layout state (unread messages, recent updates) cached via ClientLayoutComposer
- No client-side state framework; form submissions reload page or return JSON
## Key Abstractions
- Purpose: Active Record ORM pattern — maps PHP objects to rows, encapsulates relationships
- Examples: `app/Models/Project.php`, `app/Models/User.php`, `app/Models/Message.php`
- Pattern: Extend `Illuminate\Database\Eloquent\Model` or `Authenticatable`; use PHP 8 attributes (#[Fillable], #[Hidden]) for field config
- Purpose: Register bindings into IoC container, perform boot-time configuration
- Examples: `app/Providers/AppServiceProvider.php`
- Pattern: Extend `Illuminate\Support\ServiceProvider`, implement register() and boot()
- Purpose: Version-controlled schema changes
- Examples: `database/migrations/2026_06_27_100002_create_projects_table.php`
- Pattern: Anonymous class extending Migration with up()/down() methods; uses Schema facade for DDL
- Purpose: Share authorization logic across controllers
- Examples: `app/Traits/AuthorizesProjectAccess.php` (project manager validation)
- Pattern: Provide protected methods that abort(403) on unauthorized access
- Purpose: Reusable Blade template shells with layout logic
- Examples: `app/View/Components/AdminLayout.php`, `ClientLayout.php`
- Pattern: Extend Component, render() returns view('components.layouts.admin')
- Purpose: Pre-compute and cache view data (messages, updates, projects)
- Examples: `app/Http/View/Composers/ClientLayoutComposer.php`
- Pattern: Implement compose(View $view), bind via View::composer() in AppServiceProvider
- Purpose: Hook into Eloquent model lifecycle events (created, updated, deleted)
- Examples: `app/Observers/ProjectUpdateObserver.php` (listens to ProjectUpdate.created)
- Pattern: Implement methods for each hook, register via Model::observe() in provider
- Purpose: Dispatch domain events for side-effect handling
- Examples: `app/Events/MessageSent.php` (implements ShouldBroadcast)
- Pattern: Extend Event, implement broadcastOn()/broadcastAs() for channel + event name
- Purpose: Multi-channel message delivery (mail, SMS, WhatsApp, WebPush)
- Examples: `app/Notifications/ProjectUpdateNotification.php` (implements ShouldQueue)
- Pattern: Extend Notification, implement via(), toWhatsApp(), toWebPush() methods
- Purpose: Transport notifications via non-standard channels (WhatsApp, WebPush)
- Examples: `app/Channels/WhatsAppChannel.php`, `WebPushChannel.php`
- Pattern: Implement Illuminate\Notifications\Channels\Channel, send() method dispatches to external API
## Entry Points
- Location: `public/index.php`
- Triggers: Every HTTP request via Nginx/PHP-FPM
- Responsibilities: Loads Composer autoloader, creates Laravel Application instance via `bootstrap/app.php`, dispatches HTTP request through middleware/routing pipeline
- Location: `artisan` (symlink to `vendor/bin/artisan`)
- Triggers: `php artisan <command>` invocation
- Responsibilities: Loads Composer autoloader, bootstraps app, dispatches Artisan command
- Location: Defined in `bootstrap/app.php` as `health: '/up'`
- Triggers: GET `/up`
- Responsibilities: Returns 200 OK if application is running (used by Docker/load balancer)
## Error Handling
- `abort(403)` in middleware/traits for unauthorized access (renders error view)
- `$request->validate()` throws ValidationException (renders form with errors)
- Uncaught exceptions logged via Monolog and rendered by Laravel's exception handler (from vendor package)
- No custom exception classes defined yet; relying on framework defaults
## Cross-Cutting Concerns
- Middleware-level: `middleware(['auth', 'role:admin,manager'])`
- Model-level: Traits (AuthorizesProjectAccess), Policies (UserPolicy.php)
- Application-level: Gates (access-admin, manage-users, access-financial) in AppServiceProvider
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

<!-- OVERRIDE:working-method — escrito a pedido do utilizador, 2026-08-21 -->
## Método de trabalho (substitui a secção GSD acima)

O utilizador dispensou explicitamente o workflow GSD a 2026-08-21. A secção
"GSD Workflow Enforcement" acima é gerada automaticamente e **não se aplica**.

- **Não invocar skills `gsd-*`.** As restantes skills (`security-review`,
  `code-review`, design, etc.) continuam a ser usadas.
- **Delegar a execução a subagentes.** Encontrar problemas e corrigi-los vai para
  os agentes, não para edição direta. Escolher o agente especializado quando
  existir um adequado (`Senior Developer` para Laravel, `Senior SecOps Engineer`
  para segurança, `Code Reviewer` para revisão).
- **Paralelo quando os ficheiros não se cruzam, série quando se cruzam** — dois
  agentes a escrever o mesmo ficheiro perdem trabalho um do outro.
- **Verificar antes de reportar.** Correr `composer test`, `./vendor/bin/pint --test`,
  `composer audit` e `npm audit` sobre o que o agente produziu. Um agente dizer
  "concluído" não é prova; já aconteceu um mapeador afirmar que o CI não tinha
  auditoria de dependências quando tinha.
- **Manter `.planning/` à mão.** Nenhum comando GSD o vai atualizar.

O registo do trabalho feito está em `docs/registo-2026-08-21.md`.
<!-- OVERRIDE:working-method-end -->
