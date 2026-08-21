# Architecture

**Analysis Date:** 2026-08-20

## Pattern Overview

**Overall:** Layered MVC with role-based access control and real-time event broadcasting

**Key Characteristics:**
- Server-side rendering (Blade templating) — no SPA/API-first architecture
- Role-based authorization (admin, manager, financial, client) enforced at middleware and trait levels
- Multi-tenant portal (clients and projects isolated per user role)
- Real-time messaging via Laravel Broadcasting (private channels per project)
- Event-driven notifications (ProjectUpdateObserver → ProjectUpdateNotification → WhatsApp/WebPush channels)
- Soft deletes on core models (User, Project, Client) for audit compliance
- Filesystem abstraction ready for S3 (local disk primary, public/private storage separated)

## Layers

**HTTP Request Layer:**
- Purpose: Receives requests, routes to controllers, enforces middleware
- Location: `routes/web.php`, `bootstrap/app.php`
- Contains: Route definitions, middleware aliases (`role`, `EnsureUserIsActive`), exception config
- Depends on: Middleware, Controllers, Models
- Used by: Browser / HTTP clients

**Middleware Layer:**
- Purpose: Authentication, role-based access control, user status validation
- Location: `app/Http/Middleware/`
- Contains: `EnsureUserRole.php` (role gate at route level), `EnsureUserIsActive.php` (logout inactive users)
- Depends on: Authenticatable User model, session state
- Used by: Route middleware groups

**Authorization & Traits:**
- Purpose: Fine-grained project-level access control
- Location: `app/Traits/AuthorizesProjectAccess.php`, `app/Policies/UserPolicy.php`
- Contains: Manager/financial role checks against project.manager_id, Laravel Policy pattern
- Depends on: Authenticated user, Project model relationships
- Used by: Controllers (Admin\ProjectController, Client\ProjectController)

**Controller Layer:**
- Purpose: Handles HTTP requests, orchestrates business logic, returns views/JSON
- Location: `app/Http/Controllers/`
- Contains: Four namespaces — `Admin/` (6 controllers), `Client/` (2 controllers), `Auth/` (8 controllers), `Portal/` (1 controller)
- Depends on: Models, Traits, Requests, Views
- Used by: Routes

**Model Layer (Eloquent ORM):**
- Purpose: Represents database entities, relationships, business logic
- Location: `app/Models/`
- Contains: 12 models — User, Client, Project, ProjectPhase, Photo, Expense, Document, ProjectUpdate, Message, Payment, Setting, PushSubscription
- Depends on: Database, Factory definitions
- Used by: Controllers, Seeders, Events/Notifications, Observers

**Event & Notification Layer:**
- Purpose: Triggers side effects (notifications, broadcasts) when domain events occur
- Location: `app/Events/` (MessageSent), `app/Notifications/ProjectUpdateNotification`, `app/Observers/ProjectUpdateObserver`
- Contains: Event classes (ShouldBroadcast), Notification class (ShouldQueue), Eloquent observers
- Depends on: Models, Channels, Settings
- Used by: Controllers (store methods), Observers (ProjectUpdate.created)

**Broadcasting Channels:**
- Purpose: Authorizes real-time message delivery to WebSocket subscribers
- Location: `routes/channels.php`
- Contains: Role-based channel guards (project.{projectId} gated by admin/manager/client status)
- Depends on: User model roles, Project relationships
- Used by: Events (MessageSent broadcasts to project channel)

**Notification Channels:**
- Purpose: Delivers notifications via custom transport (WhatsApp, WebPush)
- Location: `app/Channels/WebPushChannel.php`, `app/Channels/WhatsAppChannel.php`
- Contains: Custom channel implementations extending Illuminate\Notifications\Channels
- Depends on: External APIs (WhatsApp, Web Push service), User phone/push subscriptions
- Used by: ProjectUpdateNotification.via()

**View Layer:**
- Purpose: Renders HTML responses via Blade templating
- Location: `resources/views/`
- Contains: 40+ Blade templates organized by role (admin/, client/, auth/, profile/)
- Depends on: Controllers passing data, Components (AdminLayout, ClientLayout, AppLayout, GuestLayout)
- Used by: Controllers, View Composers

**View Components & Composers:**
- Purpose: Reusable layout shells and pre-computed view data
- Location: `app/View/Components/`, `app/Http/View/Composers/`
- Contains: Layout components (AdminLayout, ClientLayout) render Blade templates; ClientLayoutComposer queries messages/updates/projects with caching
- Depends on: Models, Cache facade, Routes
- Used by: Views (client.blade.php uses x-client-layout component)

**Service Provider:**
- Purpose: Bootstrap application services, define authorization gates, register observers
- Location: `app/Providers/AppServiceProvider.php`
- Contains: ProjectUpdateObserver registration, Gate definitions (access-admin, manage-users, access-financial), ClientLayoutComposer binding
- Depends on: Models, Observers, View facade
- Used by: Application bootstrap (routes/web.php loads via bootstrap/app.php)

**Database & Seeding:**
- Purpose: Schema versioning and test data generation
- Location: `database/migrations/`, `database/factories/`, `database/seeders/`
- Contains: Migrations for `users`, `cache`, `jobs`, `sessions` tables; `UserFactory`; `DatabaseSeeder`
- Depends on: Schema facade, Models
- Used by: Artisan migrate/seed commands, tests

**Console:**
- Purpose: Artisan commands for maintenance tasks
- Location: `app/Console/Commands/`, `routes/console.php`
- Contains: MigratePublicProjectMedia (migrate legacy media from public to private storage)
- Depends on: Storage facade, Models
- Used by: `php artisan obra360:media:migrate-public`

## Data Flow

**New Project Creation (Admin/Manager):**

1. Admin clicks "Nova obra" → routes/web.php routes to `Admin\ProjectController@create`
2. Controller queries clients + managers, renders `admin/projects/create.blade.php`
3. Form POST to `Admin\ProjectController@store`
4. Validate request data (client_id, name, budget, manager_id, cover image)
5. Create Project (Eloquent model.create()), auto-set progress=0, status=planejada
6. ProjectUpdate::log() creates audit entry (type='status')
7. Redirect to project show view
8. Observer triggers on ProjectUpdate.created? → Notifies client users (WhatsApp/WebPush)

**Photo Upload (Admin/Manager):**

1. Admin uploads photo on project show page
2. POST to `Admin\ProjectController@storePhoto`
3. Validate image (jpg/jpeg/png/webp, max 4096)
4. Store to `storage/app/private/projects/{project_id}/` (local disk)
5. Create Photo record (project_id, path, media_type=image)
6. ProjectUpdate::log() creates 'foto' entry
7. Observer notifies client → WebPush/WhatsApp sent to client users

**Client Portal Access (Client User):**

1. Client logs in, redirected to `portal.dashboard`
2. Client\DashboardController@index queries `Project::where('client_id', $user->client_id)`
3. Loads latestImage per project, computes cover_url
4. Renders `client/dashboard.blade.php` with project cards
5. Each project card links to `portal.projects.show` (Client\ProjectController@show)
6. Controller validates $project->client_id === $user->client_id (aborts 403 if false)
7. Loads project + phases, photos (limit 200), expenses (if financial enabled), messages (limit 100)
8. Renders `client/projects/show.blade.php` with rich project data

**Message Broadcasting (Realtime):**

1. Client posts message via `portal.projects.messages.store`
2. Client\ProjectController@storeMessage validates, creates Message record
3. Message.created event triggered → dispatch(new MessageSent($msg))->toOthers()
4. MessageSent event implements ShouldBroadcast, broadcastOn() returns PrivateChannel("project.{$project_id}")
5. Pusher/Laravel Echo broadcasts to all authenticated users in that channel
6. Admin/manager/other clients subscribed to project receive message in real-time (no page reload)

**Project Update Audit & Notification:**

1. Controller creates ProjectUpdate (e.g., ProjectUpdate::log('Obra criada', 'status'))
2. ProjectUpdateObserver.created() triggered
3. Query project + client.users (N+1 optimized with eager loading)
4. For each client user (except creator): `$user->notify(new ProjectUpdateNotification($update))`
5. ProjectUpdateNotification.via() returns [WhatsAppChannel, WebPushChannel]
6. WhatsAppChannel queues async request to WhatsApp API with templated message
7. WebPushChannel queries user's PushSubscription records, sends web push payload
8. Queued jobs processed by `php artisan queue:work`

**State Management:**

- Session state stored in database (`sessions` table, Laravel default)
- Client layout state (unread messages, recent updates) cached via ClientLayoutComposer
  - Cache key: `client_unread_msgs_{$userId}_{$bucket}` (30-second TTL for real-time feel)
  - Separate COUNT query for badge accuracy
- No client-side state framework; form submissions reload page or return JSON

## Key Abstractions

**Eloquent Models:**
- Purpose: Active Record ORM pattern — maps PHP objects to rows, encapsulates relationships
- Examples: `app/Models/Project.php`, `app/Models/User.php`, `app/Models/Message.php`
- Pattern: Extend `Illuminate\Database\Eloquent\Model` or `Authenticatable`; use PHP 8 attributes (#[Fillable], #[Hidden]) for field config

**Service Providers:**
- Purpose: Register bindings into IoC container, perform boot-time configuration
- Examples: `app/Providers/AppServiceProvider.php`
- Pattern: Extend `Illuminate\Support\ServiceProvider`, implement register() and boot()

**Migrations:**
- Purpose: Version-controlled schema changes
- Examples: `database/migrations/2026_06_27_100002_create_projects_table.php`
- Pattern: Anonymous class extending Migration with up()/down() methods; uses Schema facade for DDL

**Traits:**
- Purpose: Share authorization logic across controllers
- Examples: `app/Traits/AuthorizesProjectAccess.php` (project manager validation)
- Pattern: Provide protected methods that abort(403) on unauthorized access

**View Components:**
- Purpose: Reusable Blade template shells with layout logic
- Examples: `app/View/Components/AdminLayout.php`, `ClientLayout.php`
- Pattern: Extend Component, render() returns view('components.layouts.admin')

**View Composers:**
- Purpose: Pre-compute and cache view data (messages, updates, projects)
- Examples: `app/Http/View/Composers/ClientLayoutComposer.php`
- Pattern: Implement compose(View $view), bind via View::composer() in AppServiceProvider

**Observers:**
- Purpose: Hook into Eloquent model lifecycle events (created, updated, deleted)
- Examples: `app/Observers/ProjectUpdateObserver.php` (listens to ProjectUpdate.created)
- Pattern: Implement methods for each hook, register via Model::observe() in provider

**Events:**
- Purpose: Dispatch domain events for side-effect handling
- Examples: `app/Events/MessageSent.php` (implements ShouldBroadcast)
- Pattern: Extend Event, implement broadcastOn()/broadcastAs() for channel + event name

**Notifications:**
- Purpose: Multi-channel message delivery (mail, SMS, WhatsApp, WebPush)
- Examples: `app/Notifications/ProjectUpdateNotification.php` (implements ShouldQueue)
- Pattern: Extend Notification, implement via(), toWhatsApp(), toWebPush() methods

**Custom Channels:**
- Purpose: Transport notifications via non-standard channels (WhatsApp, WebPush)
- Examples: `app/Channels/WhatsAppChannel.php`, `WebPushChannel.php`
- Pattern: Implement Illuminate\Notifications\Channels\Channel, send() method dispatches to external API

## Entry Points

**Web Request Entry Point:**
- Location: `public/index.php`
- Triggers: Every HTTP request via Nginx/PHP-FPM
- Responsibilities: Loads Composer autoloader, creates Laravel Application instance via `bootstrap/app.php`, dispatches HTTP request through middleware/routing pipeline

**Artisan CLI Entry Point:**
- Location: `artisan` (symlink to `vendor/bin/artisan`)
- Triggers: `php artisan <command>` invocation
- Responsibilities: Loads Composer autoloader, bootstraps app, dispatches Artisan command

**Health Check Endpoint:**
- Location: Defined in `bootstrap/app.php` as `health: '/up'`
- Triggers: GET `/up`
- Responsibilities: Returns 200 OK if application is running (used by Docker/load balancer)

## Error Handling

**Strategy:** Server-side rendering of error views (HTML) except for API routes

**Configuration:** `bootstrap/app.php` via `->withExceptions(fn(Exceptions $e) => $e->shouldRenderJsonWhen(fn(Request $r) => $r->is('api/*')))`

**Patterns:**
- `abort(403)` in middleware/traits for unauthorized access (renders error view)
- `$request->validate()` throws ValidationException (renders form with errors)
- Uncaught exceptions logged via Monolog and rendered by Laravel's exception handler (from vendor package)
- No custom exception classes defined yet; relying on framework defaults

## Cross-Cutting Concerns

**Logging:** Laravel Monolog integration (app/logs/), logged via Log facade in exception handler

**Validation:** Form validation via `$request->validate()` in controller actions; FormRequest classes in `app/Http/Requests/` for user/profile updates

**Authentication:** Laravel Breeze (built-in auth scaffold); session-based with remember_token; enforced by `auth` middleware

**Authorization:** 
- Middleware-level: `middleware(['auth', 'role:admin,manager'])`
- Model-level: Traits (AuthorizesProjectAccess), Policies (UserPolicy.php)
- Application-level: Gates (access-admin, manage-users, access-financial) in AppServiceProvider

**Real-Time:** Laravel Broadcasting + Pusher/Redis (MessageSent events), Web Push API (PushSubscriptionController stores subscriptions)

**Queuing:** Laravel Queue (ShouldQueue on Notifications); backed by database or Redis; workers run via `php artisan queue:work`

**Caching:** Laravel Cache facade; used in ClientLayoutComposer (30-second TTL), DashboardController (project stats)

---

*Architecture analysis: 2026-08-20*
