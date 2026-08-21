# Codebase Structure

**Analysis Date:** 2026-08-20

## Directory Layout

```
Obra360/
├── app/                          # Application code (PHP)
│   ├── Channels/                 # Custom notification channels (WhatsApp, WebPush)
│   ├── Console/
│   │   └── Commands/             # Artisan commands
│   ├── Events/                   # Laravel Broadcasting events
│   ├── Http/
│   │   ├── Controllers/          # HTTP request handlers (Admin, Auth, Client, Portal)
│   │   │   ├── Admin/            # Admin portal controllers (6 controllers)
│   │   │   ├── Auth/             # Authentication controllers (8 controllers)
│   │   │   ├── Client/           # Client portal controllers (2 controllers)
│   │   │   └── Portal/           # PWA subscription controller
│   │   ├── Middleware/           # Request middleware
│   │   ├── Requests/             # FormRequest validation classes
│   │   └── View/
│   │       └── Composers/        # View data composers
│   ├── Models/                   # Eloquent ORM models (12 models)
│   ├── Notifications/            # Notification classes
│   ├── Observers/                # Eloquent model observers
│   ├── Policies/                 # Authorization policies
│   ├── Providers/                # Service providers
│   ├── Traits/                   # Shared traits
│   └── View/
│       └── Components/           # Blade view components
│
├── bootstrap/                    # Application bootstrap
│   ├── app.php                   # Application configuration entry point
│   └── providers.php             # Service provider list
│
├── config/                       # Configuration files (app, auth, database, etc.)
│
├── database/
│   ├── factories/                # Model factories for testing (UserFactory)
│   ├── migrations/               # Schema migrations (17 files)
│   └── seeders/                  # Database seeders
│
├── docker/                       # Docker configuration
│   ├── nginx/                    # Nginx reverse proxy config
│   └── php/                      # PHP-FPM config
│
├── public/                       # Public web root
│   ├── build/assets/             # Compiled Vite assets (CSS, JS)
│   └── index.php                 # HTTP entry point
│
├── resources/
│   ├── css/
│   │   └── app.css               # Tailwind CSS entry point
│   ├── js/
│   │   └── app.js                # Frontend JavaScript entry point (ES Modules)
│   └── views/                    # Blade templates
│       ├── admin/                # Admin portal views
│       │   ├── projects/         # Project CRUD views
│       │   ├── clients/          # Client CRUD views
│       │   └── settings/         # Settings views
│       ├── auth/                 # Authentication views (login, register, etc.)
│       ├── client/               # Client portal views
│       │   ├── projects/         # Client project detail view
│       │   └── messages/         # Client messaging view
│       ├── components/
│       │   └── layouts/          # Layout components (admin, client, guest)
│       ├── layouts/              # Legacy layout templates
│       ├── profile/              # User profile views
│       └── users/                # User management views
│
├── routes/
│   ├── web.php                   # Web routes (role-gated groups: admin, portal)
│   ├── auth.php                  # Auth routes (Breeze)
│   └── channels.php              # Broadcasting channel authorization
│
├── storage/
│   ├── app/
│   │   ├── private/              # Private disk (non-web-accessible files)
│   │   └── public/               # Public disk (web-accessible, via /storage symlink)
│   ├── framework/                # Framework cache/sessions/views
│   └── logs/                     # Application logs
│
├── tests/
│   ├── Feature/                  # Feature/integration tests
│   │   └── Auth/
│   ├── Unit/                     # Unit tests
│   └── TestCase.php              # Base test class
│
├── .planning/codebase/           # Codebase analysis documents (generated)
├── .planning/phases/             # Phase implementation docs (generated)
├── .claude/                      # Claude tooling (workspaces, memory)
├── .github/workflows/            # GitHub Actions CI/CD
├── docker-compose.yml            # Docker Compose configuration
├── Dockerfile                    # PHP-FPM Docker image
├── vite.config.js                # Vite build configuration
├── artisan                       # Artisan CLI binary
├── composer.json                 # PHP dependencies
├── package.json                  # Node.js dev dependencies
└── phpunit.xml                   # PHPUnit test configuration

```

## Directory Purposes

**app/Models/:**
- Purpose: Eloquent ORM entities — each model maps to a database table
- Contains: 12 models (User, Client, Project, ProjectPhase, Photo, Expense, Document, ProjectUpdate, Message, Payment, Setting, PushSubscription)
- Key files: `User.php` (authenticatable), `Project.php` (has phases/photos/messages/expenses/payments), `Client.php` (has users/projects)
- Relationships use PHP 8 attributes (#[Fillable], #[Hidden]) for field config

**app/Http/Controllers/:**
- Purpose: HTTP request handlers organized by role/domain
- Namespaces:
  - `Admin/` (6 controllers) — project/client/message/payment/setting management
  - `Auth/` (8 controllers) — authentication (login, register, password reset, email verification)
  - `Client/` (2 controllers) — client portal (dashboard, project view)
  - `Portal/` (1 controller) — PWA push subscriptions
  - Root: `Controller.php` (base abstract class), `ProfileController.php`, `UserController.php`

**app/Http/Middleware/:**
- Purpose: Request filters (authentication, authorization, validation)
- Contains: `EnsureUserRole.php` (gate access by role), `EnsureUserIsActive.php` (logout deactivated users)

**app/Http/Requests/:**
- Purpose: Form request validation (centralize validation logic)
- Contains: `StoreUserRequest.php`, `UpdateUserRequest.php`, `ProfileUpdateRequest.php`, `Auth/` (Breeze auth requests)

**app/Http/View/Composers/:**
- Purpose: Pre-compute and inject data into views before rendering
- Contains: `ClientLayoutComposer.php` (queries unread messages, recent updates with caching)

**app/View/Components/:**
- Purpose: Reusable Blade view components (layout shells)
- Contains: `AdminLayout.php`, `ClientLayout.php`, `AppLayout.php`, `GuestLayout.php`

**app/Events/:**
- Purpose: Domain events for side-effect handling
- Contains: `MessageSent.php` (implements ShouldBroadcast, broadcasts to project channel)

**app/Notifications/:**
- Purpose: Multi-channel message delivery (WhatsApp, WebPush)
- Contains: `ProjectUpdateNotification.php` (queued, sends on ProjectUpdate.created)

**app/Channels/:**
- Purpose: Custom notification transports (non-standard channels)
- Contains: `WhatsAppChannel.php`, `WebPushChannel.php` (send() method dispatches to external API)

**app/Observers/:**
- Purpose: Listen to Eloquent model lifecycle events and trigger side effects
- Contains: `ProjectUpdateObserver.php` (listens to ProjectUpdate.created, sends notifications to client users)

**app/Policies/:**
- Purpose: Authorization policies (Laravel Gate pattern)
- Contains: `UserPolicy.php` (controls who can view/edit/delete users)

**app/Traits/:**
- Purpose: Shared authorization/utility methods across controllers
- Contains: `AuthorizesProjectAccess.php` (validates manager_id matches auth user or aborts 403)

**app/Providers/:**
- Purpose: Service provider definitions (IoC container registration, boot hooks)
- Contains: `AppServiceProvider.php` (registers observers, defines gates, binds view composers)

**app/Console/Commands/:**
- Purpose: Artisan command implementations
- Contains: `MigratePublicProjectMedia.php` (migrate legacy media from public to private storage)

**database/migrations/:**
- Purpose: Versioned schema changes
- Contains: 17 migrations from framework defaults (users, cache, jobs, sessions) + domain models (clients → push_subscriptions)
- Timestamp prefixes: `0001_*` (framework), `2026_06_27_*` (domain), `2026_06_27_2_*` (relationships), `2026_06_27_3_*` (payments/push)

**database/factories/:**
- Purpose: Fake data generation for tests
- Contains: `UserFactory.php` (generates test users with roles)

**database/seeders/:**
- Purpose: Populate database with seed data
- Contains: `DatabaseSeeder.php` (orchestrates all seeders)

**resources/views/**

- **admin/:** Admin portal Blade templates
  - `dashboard.blade.php` — stats, recent projects, recent updates
  - `projects/` — index (filtered list), create, edit, show (with phases, photos, expenses, messages)
  - `clients/` — index (list), create, edit (CRUD)
  - `settings/` — index (global settings form)
  - `messages/` — messaging interface (deprecated in favor of projects.messages.index)

- **client/:** Client portal Blade templates
  - `dashboard.blade.php` — project cards with cover images
  - `projects/show.blade.php` — project detail (phases, photos, expenses, messages, documents)
  - `messages/index.blade.php` — project messaging thread

- **auth/:** Authentication views (Laravel Breeze)
  - `login.blade.php`, `register.blade.php`, `forgot-password.blade.php`, `reset-password.blade.php`, `verify-email.blade.php`

- **profile/:** User profile management
  - `edit.blade.php` — profile edit form
  - `partials/` — form sections (update info, change password, delete account)

- **components/layouts/:** Layout shells (Blade components)
  - `admin.blade.php` — admin sidebar, nav, footer (extends components.layouts.app)
  - `client.blade.php` — client nav, sidebar, unread messages badge (extends components.layouts.app)
  - `app.blade.php` — root layout with Vite asset injection
  - `guest.blade.php` — auth layout (login, register)

- **layouts/:** Legacy layout templates (used before components)
- **users/:** User management views (admin only)

**routes/web.php:**
- Purpose: Define all web routes with role-based middleware
- Groups:
  - `/admin` — role: admin,manager,financial (read projects/messages)
  - `/admin` — role: admin (manage clients, settings, users)
  - `/admin` — role: admin,manager (create/edit projects, phases, photos, documents, updates)
  - `/admin` — role: admin,financial (expenses, payments)
  - `/portal` — role: client (client dashboard, projects, messages, push subscriptions)
- All route names prefixed with group name (admin.*, portal.*, etc.)

**routes/channels.php:**
- Purpose: Authorize broadcasting channel subscriptions
- Contains: `project.{projectId}` channel gated by role (admin/manager/client access rules)

**routes/auth.php:**
- Purpose: Authentication routes (Laravel Breeze)
- Contains: Login, register, password reset, email verification routes

**resources/css/app.css:**
- Purpose: Tailwind CSS entry point
- Imports: Tailwind directives (@tailwind), custom utilities, font imports (Bunny Fonts CDN for Instrument Sans)

**resources/js/app.js:**
- Purpose: Frontend JavaScript entry point (ES Modules)
- Bundled by Vite, loaded via `@vite()` directive in layouts

**storage/app/private/:**
- Purpose: Private disk (not web-accessible)
- Contains: Project media (covers, photos, documents)
- Path pattern: `projects/{project_id}/`, `covers/`, etc.

**storage/app/public/:**
- Purpose: Public disk (web-accessible via /storage symlink)
- Currently unused (media migrating from public to private disk)

**public/index.php:**
- Purpose: HTTP entry point
- Loads autoloader, creates Application instance, dispatches request

**public/build/assets/:**
- Purpose: Compiled Vite assets (CSS, JS, images)
- Generated by `npm run build`; versioned by Vite's cache-buster

**tests/Feature/:**
- Purpose: Integration/feature tests (test full request cycle)
- Contains: Auth tests (login, register, email verification, password reset)

**tests/Unit/:**
- Purpose: Unit tests (test isolated components)

**bootstrap/app.php:**
- Purpose: Application configuration entry point
- Defines: routing (web.php, channels.php, health:/up), middleware config, exception handling

**bootstrap/providers.php:**
- Purpose: Service provider list (list of all providers to load)

**vite.config.js:**
- Purpose: Vite bundler configuration
- Defines: CSS/JS entry points, Tailwind/Laravel plugins, hot reload settings

**docker-compose.yml:**
- Purpose: Orchestrate local dev containers (PHP-FPM, Nginx, MySQL/Redis)

**Dockerfile:**
- Purpose: PHP-FPM 8.4 Docker image (installs extensions, Composer, Laravel app)

**composer.json:**
- Purpose: PHP dependencies (Laravel 13, Tailwind, DomPDF, etc.)
- Key: `require` and `require-dev`, autoload PSR-4 mapping

**package.json:**
- Purpose: Node.js dev dependencies (Vite, Tailwind, concurrently, etc.)
- No package-lock.json committed (npm install uses latest compatible versions)

## Key File Locations

**Entry Points:**
- `public/index.php` — Web HTTP entry point
- `artisan` — Artisan CLI entry point
- `routes/web.php` — All web route definitions
- `bootstrap/app.php` — Application configuration factory

**Configuration:**
- `config/app.php` — App name, timezone, etc.
- `config/database.php` — Database connection config
- `config/auth.php` — Authentication guards/providers
- `.env` — Environment variables (not committed, see .env.example)
- `vite.config.js` — Asset bundling

**Core Logic:**
- `app/Models/` — Data models (12 Eloquent models)
- `app/Http/Controllers/` — HTTP request handlers
- `routes/web.php` — Route definitions + middleware groups
- `app/Providers/AppServiceProvider.php` — Service registration + gates + observers

**Testing:**
- `tests/Feature/Auth/` — Authentication feature tests
- `tests/Unit/` — Unit test stubs
- `phpunit.xml` — PHPUnit configuration

## Naming Conventions

**Files:**
- Class files: PascalCase, one class per file (e.g., `UserController.php`, `ProjectFactory.php`)
- Migration files: timestamp prefix + underscore-separated name (e.g., `0001_01_01_000000_create_users_table.php`)
- Blade views: snake_case with .blade.php suffix (e.g., `dashboard.blade.php`, `projects/show.blade.php`)
- Config files: snake_case.php (e.g., `app.php`, `database.php`)

**Directories:**
- PascalCase for namespaced directories (Controllers, Models, Events, Channels)
- snake_case for template directories (resources/views/admin, resources/views/client)

**Classes:**
- PascalCase for all class names
- Suffix with role/purpose: `*Controller`, `*Factory`, `*Seeder`, `*Request`, `*Policy`, `*Observer`, `*Provider`

**Methods:**
- camelCase (e.g., `index()`, `storePhase()`, `toggleActive()`)
- Return type declarations mandatory (e.g., `public function index(): View`)

**Route Names:**
- snake_case, prefixed by middleware group (e.g., `admin.projects.create`, `portal.dashboard`)
- Nested resources separated by dot (e.g., `admin.projects.photos.store`)

**Database Tables:**
- snake_case, plural (e.g., `users`, `projects`, `project_updates`, `push_subscriptions`)

**Database Columns:**
- snake_case, suffixed with type (e.g., `created_at`, `manager_id`, `cover_image_path`)

## Where to Add New Code

**New Feature (e.g., Site Visits Tracking):**

1. **Model:**
   - Create `app/Models/SiteVisit.php` extending Model
   - Add #[Fillable] attribute, relationships, business logic methods
   - Create migration: `database/migrations/2026_08_20_000000_create_site_visits_table.php`

2. **Controller (Admin):**
   - Create `app/Http/Controllers/Admin/SiteVisitController.php`
   - Implement index(), create(), store(), show(), edit(), update(), destroy() (as needed)
   - Add role middleware via route group: `middleware(['auth', 'role:admin,manager'])`
   - Use AuthorizesProjectAccess trait if project-scoped

3. **Routes:**
   - Add to `routes/web.php` under appropriate role group
   - Example: `Route::resource('site-visits', SiteVisitController::class)->middleware(['auth', 'role:admin,manager'])`

4. **Validation:**
   - Create `app/Http/Requests/StoreSiteVisitRequest.php` extending FormRequest
   - Implement rules() method
   - Use in controller: `$validated = $request->validated();`

5. **Views:**
   - Create `resources/views/admin/site-visits/` directory
   - Add: `index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`
   - Use `<x-admin-layout>` component wrapper

6. **Events/Notifications (if triggering side-effects):**
   - Create `app/Events/SiteVisitCreated.php` (if broadcasting needed)
   - Create `app/Notifications/SiteVisitNotification.php` (if notifying users)
   - Register in `app/Providers/AppServiceProvider.php` via observer

7. **Tests:**
   - Create `tests/Feature/SiteVisitTest.php`
   - Test authorization (role checks), CRUD operations, validation

**New Component/Module (e.g., Photo Gallery Reusable Component):**

- Create `app/View/Components/PhotoGallery.php`
- Create `resources/views/components/photo-gallery.blade.php`
- Use in templates: `<x-photo-gallery :photos="$project->photos" />`

**Utilities/Helpers:**

- Create `app/Traits/NameUtility.php` if used across multiple classes
- Create `app/Services/SomeService.php` if stateful service needed
- Create plain functions in `app/Helpers/helpers.php` (auto-loaded via composer.json) for stateless helpers

**Middleware:**

- Create `app/Http/Middleware/YourMiddleware.php`
- Register in `bootstrap/app.php` via `$middleware->alias(['your' => YourMiddleware::class])`
- Apply to routes: `middleware('your')`

**Artisan Command:**

- Create `app/Console/Commands/YourCommand.php`
- Extend `Illuminate\Console\Command`
- Define $signature and $description
- Implement handle() method
- Register in `bootstrap/app.php` via `->withCommands([YourCommand::class])`

## Special Directories

**storage/app/private/:**
- Purpose: Non-web-accessible file storage (project media)
- Generated: Yes (created by Laravel)
- Committed: No (.gitignore excludes storage/)
- Accessed via: Storage::disk('local')->get/put/download

**storage/framework/:**
- Purpose: Framework cache, sessions, views (cache, sessions, cache/data, views, testing)
- Generated: Yes
- Committed: No

**bootstrap/cache/:**
- Purpose: Config/route caching (production optimization)
- Generated: Yes (created by `php artisan config:cache`, `php artisan route:cache`)
- Committed: No

**public/build/assets/:**
- Purpose: Compiled frontend assets (CSS, JS, images from Vite)
- Generated: Yes (created by `npm run build`)
- Committed: No (.gitignore excludes)

**.planning/:**
- Purpose: GSD workflow artifacts (codebase analysis, phase plans)
- Generated: Yes (created by GSD commands)
- Committed: No (these are working documents)

**.claude/:**
- Purpose: Claude-specific tooling (project workspaces, memory, skills)
- Generated: Yes (created by Claude agent)
- Committed: No (workspace-specific)

---

*Structure analysis: 2026-08-20*
