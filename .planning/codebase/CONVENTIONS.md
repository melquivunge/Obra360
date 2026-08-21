# Coding Conventions

**Analysis Date:** 2026-08-20

## Naming Patterns

**Files:**
- PascalCase for all class files: `User.php`, `UserFactory.php`, `ProjectController.php`, `UserManagementTest.php`
- snake_case for configuration files: `phpunit.xml`, `docker-compose.yml`
- kebab-case for Blade components: `text-input.blade.php`, `primary-button.blade.php`, `data-table.blade.php`
- Example structure: `app/Models/User.php`, `app/Http/Controllers/UserController.php`, `app/Http/Requests/StoreUserRequest.php`, `database/factories/UserFactory.php`

**Functions/Methods:**
- camelCase for all method names: `definition()`, `unverified()`, `authenticate()`, `storePhoto()`, `updatePhase()`, `isAdmin()`, `totalSpent()`
- Method names describe expected behavior: `canAuthenticate`, `isFinancial`, `invalidateUserSessions`, `guardCriticalAdminChange`
- Test methods: `test_` prefix with snake_case, descriptive assertions. Example: `test_admin_can_create_user_with_valid_data()`, `test_client_cannot_download_internal_document()`, `test_inactive_user_cannot_login_with_correct_credentials()`

**Variables/Properties:**
- camelCase for local variables: `$user`, `$response`, `$admin`, `$wouldDeactivate`, `$isSelfChange`, `$isLastActiveAdmin`
- Private methods prefixed with underscore: `private function invalidateUserSessions()`, `private function guardCriticalAdminChange()`
- Model constants use UPPER_SNAKE_CASE: `User::ROLE_ADMIN`, `User::ROLE_CLIENT`, `Project::STATUSES`

**Classes:**
- PascalCase for all classes: `User`, `UserFactory`, `UserController`, `StoreUserRequest`, `AuthenticationTest`
- Suffix classes with role: `*Factory`, `*Controller`, `*Request`, `*Test`
- Nested resource controllers: `Admin\ProjectController`, `Admin\ClientController`, `Client\ProjectController` (namespace prefix follows functionality)

**Route Names:**
- Route names in English, kebab-case: `route('users.index')`, `route('admin.projects.show')`, `route('portal.dashboard')`
- Prefix groups with role: `admin.*`, `portal.*`
- Nested resource naming: `admin.projects.phases.store`, `admin.projects.documents.download`
- **URL paths in Portuguese:** routes/web.php uses Portuguese for user-facing URLs: `/clientes`, `/obras`, `/mensagens`, `/fotos`, `/documentos`, `/despesas`, `/pagamentos`, `/fases`, `/configuracoes` — but route **names** remain English for internal references

## Code Style

**Formatting:**
- Laravel Pint (`laravel/pint` v1.27) is the official formatter
- Enforced in CI via `./vendor/bin/pint --test`
- Run manually: `./vendor/bin/pint`
- 4 spaces indentation (no tabs), enforced via `.editorconfig`
- LF (Unix-style line endings), enforced via `.editorconfig`
- UTF-8 encoding
- Files trimmed of trailing whitespace, final newline on all files

**Linting:**
- No separate ESLint or Prettier config detected; Laravel Pint is the sole PHP formatter
- Frontend CSS/JS uses Vite default formatting with no explicit linter enforced in CI
- Tailwind CSS v4 with Vite plugin (`@tailwindcss/vite`)

**PHP Version Features:**
- PHP 8.3+ required by composer.json; 8.4 used in Docker, 8.5 on HostGator production
- PHP 8 **Attributes** used extensively in Models instead of property arrays:
  ```php
  #[Fillable(['name', 'email', 'password', 'role', 'phone', 'active', 'client_id'])]
  #[Hidden(['password', 'remember_token'])]
  class User extends Authenticatable
  ```
- Constructor property promotion supported: not used extensively, but available
- Match expressions supported: not detected in codebase
- Named arguments supported: commonly used in test assertions

## Import Organization

**Order (PHP):**
1. Namespace declaration
2. Traits (if using `use` for mixins within class)
3. External/Framework imports: `use Illuminate\*`, `use Database\Factories\*`, `use App\Models\*`
4. Internal app imports: `use App\Http\*`, `use App\Services\*` (organized by responsibility)

Example from `app/Http/Controllers/UserController.php`:
```php
namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
```

**Path Aliases:**
- `App\` maps to `app/`
- `App\Models\` maps to `app/Models/`
- `App\Http\Controllers\` maps to `app/Http/Controllers/`
- `App\Http\Requests\` maps to `app/Http/Requests/`
- `App\Providers\` maps to `app/Providers/`
- `Database\Factories\` maps to `database/factories/`
- `Database\Seeders\` maps to `database/seeders/`
- `Tests\` maps to `tests/`

**Frontend (JavaScript/ESM):**
- ES Modules (import/export)
- Alpine.js imported directly: `import Alpine from 'alpinejs'`
- Laravel Echo for WebSocket: `import Echo from 'laravel-echo'`
- Pusher for WebSocket transport: `import Pusher from 'pusher-js'`

## Return Type Declarations

**Mandatory on all public/private methods:**
- Primitive types: `: void`, `: string`, `: bool`, `: int`, `: float`
- Array types: `: array`, `: array<string, mixed>`, `: array<string, string>`
- Model types: `: User`, `: View`, `: RedirectResponse`
- Static return: `: static` (used in factories)
- Union types supported: `: User|null` (though not extensively used)

Example:
```php
public function definition(): array
public function unverified(): static
public function index(): View
public function store(StoreUserRequest $request): RedirectResponse
public function invalidateUserSessions(User $user): void
protected function casts(): array
```

## PHPDoc Blocks

**Usage:**
- Used on classes, public methods, and complex logic
- JSDoc-style `@return`, `@param`, `@throws`
- Template types documented: `@extends Factory<User>`, `@use HasFactory<UserFactory>`
- Example:
```php
/**
 * @extends Factory<User>
 */
class UserFactory extends Factory

/**
 * Define the model's default state.
 *
 * @return array<string, mixed>
 */
public function definition(): array
```

**Comments:**
- Minimal inline comments; code is expected to be self-documenting
- Auth logic marked with references: `// AUTH-01`, `// T-04-02` (from traceability docs)
- Comments used for non-obvious business logic, e.g., "Block inactive users — log out immediately so no session is persisted"

## Model Design

**Pattern:**
- All models extend `Illuminate\Database\Eloquent\Model` or `Illuminate\Foundation\Auth\User`
- Use PHP 8 Attributes for `#[Fillable]`, `#[Hidden]`, `#[Cast]` declarations (not property arrays)
- Explicit `protected function casts(): array` for type casting

Example from `app/Models/User.php`:
```php
#[Fillable(['name', 'email', 'password', 'role', 'phone', 'active', 'client_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CLIENT = 'client';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function isAdmin(): bool { return $this->role === self::ROLE_ADMIN; }
}
```

**Relationships:**
- Return type declarations mandatory: `: BelongsTo`, `: HasMany`, `: HasOne`
- Methods chain fluent interface: `return $this->belongsTo(Client::class);`
- Eager loading constraints: `$this->hasMany(Photo::class)->latest()`

**Soft Deletes:**
- Use `SoftDeletes` trait on models requiring archival: `User`, `Project`, `Photo`, `Document`, etc.
- Accessed via `Model::withTrashed()->find()` or `Model::onlyTrashed()`

## Controller Design

**Pattern:**
- Extend base `Controller` class
- Use `AuthorizesRequests` trait for policy checks
- Method names map to RESTful verbs: `index()`, `show()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`
- Additional actions: `cover()`, `photo()`, `storePhoto()`, `toggleActive()`, `markPaid()`

Example from `app/Http/Controllers/UserController.php`:
```php
class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', User::class);
        return view('users.index', compact('users'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated() + ['active' => true]);
        return redirect()->route('users.index')->with('success', 'Usuário criado...');
    }
}
```

**Authorization:**
- `$this->authorize()` called at method start to check policies
- Returns 403 if not authorized
- Policies defined in `app/Policies/` (if complex authorization required)
- Middleware enforced at route level via `middleware(['auth', 'role:admin'])` in routes/web.php

## Request Validation

**Pattern:**
- Use `FormRequest` classes extending `Illuminate\Foundation\Http\FormRequest`
- Location: `app/Http/Requests/`
- Implement `authorize(): bool` to check if user can make request
- Implement `rules(): array` with validation rules
- Implement `messages(): array` with Portuguese error messages

Example from `app/Http/Requests/StoreUserRequest.php`:
```php
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Este campo é obrigatório.',
            'email.unique' => 'Este e-mail já está em uso.',
        ];
    }
}
```

**Validation Rules:**
- Use array syntax for rules (not pipe-delimited strings): `['required', 'email', 'unique:users,email']`
- Use `Rule::in()` for enum-style validation: `Rule::in([User::ROLE_ADMIN, User::ROLE_CLIENT])`
- File validation with MIME types: `mimetypes:application/pdf` for documents
- Password confirmation: `'password' => ['required', 'confirmed', 'min:8']`

## Error Handling

**Strategy:**
- Exception rendering configured in `bootstrap/app.php` via `->withExceptions()`
- No custom exception classes defined; use Laravel's built-in exceptions
- Framework automatically handles HTTP exceptions (401, 403, 404, 422, 500)
- JSON responses sent only for `api/*` routes (none currently exist)

**Patterns:**
- `abort(403)` for permission denied (used in middleware)
- `abort(422, 'Custom message')` for validation failures with specific messages (e.g., cannot demote last admin)
- `ValidationException::withMessages([...])` for form validation errors with Portuguese messages
- Soft failures: inactive users logged out immediately during auth without persisting session

Example from `app/Http/Middleware/EnsureUserRole.php`:
```php
if (! auth()->check() || ! in_array(auth()->user()->role, $roles, true)) {
    abort(403);
}
```

Example from `app/Http/Requests/Auth/LoginRequest.php`:
```php
if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
    throw ValidationException::withMessages([
        'email' => 'E-mail ou senha incorretos.',
    ]);
}

if (! Auth::user()->active) {
    Auth::logout();
    throw ValidationException::withMessages([
        'email' => 'Sua conta está inativa. Entre em contato com o administrador.',
    ]);
}
```

## Logging

**Framework:** 
- Laravel's logging system (Monolog-based)
- PSR-3 interface via `Illuminate\Support\Facades\Log`
- Real-time log tailing in dev: `php artisan pail` (or `composer run dev` includes it)
- Log files: `storage/logs/` (one file per day by default)

**Patterns:**
- Not extensively logged in current codebase (focus on core auth/authorization)
- Available for custom logging via `Log::info()`, `Log::error()`, `Log::warning()`
- Queue job failures logged automatically by Laravel queue system

## Middleware

**Pattern:**
- Located in `app/Http/Middleware/`
- Implement `handle(Request $request, Closure $next): Response`
- Return `$next($request)` to pass to next middleware or controller
- Return HTTP response to short-circuit pipeline

Example from `app/Http/Middleware/EnsureUserRole.php`:
```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (! auth()->check() || ! in_array(auth()->user()->role, $roles, true)) {
        abort(403);
    }
    return $next($request);
}
```

**Registration:**
- Registered in `bootstrap/app.php` via `$middleware->alias()` or `appendToGroup()`
- Applied in routes via `middleware(['role:admin,manager'])` syntax

## Blade Components

**Pattern:**
- Located in `resources/views/components/`
- Kebab-case filenames: `text-input.blade.php`, `primary-button.blade.php`, `data-table.blade.php`
- Use `@props` to declare expected attributes
- Spread attributes with `{{ $attributes->merge([...]) }}` for TailwindCSS classes
- Slot content via `{{ $slot }}`

Example from `resources/views/components/text-input.blade.php`:
```blade
@props(['disabled' => false])
<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500...']) }}>
```

Example from `resources/views/components/data-table.blade.php`:
```blade
@props(['head' => null])
<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm...']) }}>
    <table>
        @if ($head) <thead>{{ $head }}</thead> @endif
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
```

**Usage in Views:**
- Invoked with `<x-component-name>` tag
- Pass attributes: `<x-text-input :disabled="$isDisabled" />`
- Pass slots: `<x-data-table><thead>...</thead></x-data-table>`

## Language & Localization

**Naming Convention Duality:**
- **PHP identifiers (classes, methods, variables):** English — `User`, `Project`, `storePhoto()`, `$manager`
- **User-facing routes/URLs:** Portuguese — `/obras`, `/clientes`, `/fotos`, `/mensagens`, `/pagamentos`
- **UI/Form messages:** Portuguese — "Este campo é obrigatório", "Sua conta está inativa", "Usuário criado com sucesso"
- **Route names:** English — `route('admin.projects.show')`, `route('portal.dashboard')`

**Translation Files:**
- Laravel `resources/lang/pt/` and `resources/lang/en/` (if applicable)
- Laravel's `trans()` function used for rate-limit messages: `trans('auth.throttle', ['seconds' => $seconds])`

## Database & Query Patterns

**Query Builder:**
- Fluent interface: `User::query()->latest()->paginate(15)`
- Method chaining for filtering: `User::where('role', User::ROLE_ADMIN)->where('active', true)`
- Soft delete queries: `User::withTrashed()->find()`, `User::onlyTrashed()`

**Example from UserController:**
```php
$users = User::query()->latest()->paginate(15);
$found = User::withTrashed()->find($user->id);
$isLastActiveAdmin = User::where('role', User::ROLE_ADMIN)
    ->where('active', true)
    ->whereKeyNot($user->id)
    ->doesntExist();
```

## Special Patterns

**Role-Based Access Control (RBAC):**
- User model constants: `User::ROLE_ADMIN`, `User::ROLE_CLIENT`, `User::ROLE_MANAGER`, `User::ROLE_FINANCIAL`
- Helper methods: `$user->isAdmin()`, `$user->isClient()`, `$user->isManager()`, `$user->isFinancial()`
- Middleware: `middleware(['role:admin,manager'])` enforces multiple roles
- Policies in routes: `$this->authorize('viewAny', User::class)` checks policy rules

**Rate Limiting:**
- Built-in login throttling: 5 attempts per email|IP before lockout
- Configured via `RateLimiter::tooManyAttempts()` and `RateLimiter::hit()` in LoginRequest

**Session Management:**
- Database-backed sessions via `config('session.driver')` 
- Manual session invalidation: `DB::table('sessions')->where('user_id', $user->id)->delete()`
- Used when deactivating users to force logout

---

*Convention analysis: 2026-08-20*
