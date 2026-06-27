# Coding Conventions

**Analysis Date:** 2026-06-27

## Naming Patterns

**Classes:**
- PascalCase for all class names: `UserFactory`, `DatabaseSeeder`, `AppServiceProvider`, `Controller`
- Suffix classes with their role: `*Factory`, `*Seeder`, `*Controller`, `*ServiceProvider`, `*Migration`

**Methods:**
- camelCase for method names: `definition()`, `unverified()`, `register()`, `boot()`
- Return type declarations are mandatory on all methods: `void`, `array`, `static`, `array<string, mixed>`

**Test Methods:**
- snake_case with `test_` prefix: `test_that_true_is_true`, `test_the_application_returns_a_successful_response`
- Names describe expected behavior, not implementation

**Variables:**
- camelCase for local variables
- `$password`, `$attributes`, `$request` follow standard Laravel naming

**Files:**
- PascalCase for PHP class files: `User.php`, `UserFactory.php`, `Controller.php`
- Migration files use timestamp prefix: `0001_01_01_000000_create_users_table.php`

**Namespaces:**
- `App\` → `app/`
- `App\Models\` → `app/Models/`
- `App\Http\Controllers\` → `app/Http/Controllers/`
- `App\Providers\` → `app/Providers/`
- `Database\Factories\` → `database/factories/`
- `Database\Seeders\` → `database/seeders/`
- `Tests\` → `tests/`

## Code Style

**Formatter:**
- Laravel Pint (`laravel/pint` ^1.27) is installed as the code formatter
- Run: `./vendor/bin/pint`

**Indentation:**
- 4 spaces (no tabs), enforced via `.editorconfig`
- YAML/YML files use 2 spaces, except Docker Compose files which use 4

**Line Endings:**
- LF (Unix-style), enforced via `.editorconfig`

**Encoding:**
- UTF-8

**Trailing whitespace:**
- Trimmed from all files except Markdown

**Final newline:**
- All files end with a final newline

## PHP Version Features

**PHP 8.3+ syntax is used throughout:**

PHP 8 attributes replace class property arrays on models:
```php
// app/Models/User.php
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
```

Anonymous classes for migrations:
```php
// database/migrations/0001_01_01_000000_create_users_table.php
return new class extends Migration
{
    public function up(): void { ... }
    public function down(): void { ... }
};
```

Named arguments for clarity:
```php
// bootstrap/app.php
Application::configure(basePath: dirname(__DIR__))
```

Arrow functions with `fn()`:
```php
// bootstrap/app.php
$exceptions->shouldRenderJsonWhen(
    fn (Request $request) => $request->is('api/*'),
);
// database/factories/UserFactory.php
return $this->state(fn (array $attributes) => [
    'email_verified_at' => null,
]);
```

Nullsafe assignment operator (`??=`) for lazy initialization:
```php
// database/factories/UserFactory.php
'password' => static::$password ??= Hash::make('password'),
```

## Import Organization

**Order (top of file):**
1. `declare(strict_types=1)` if used
2. `namespace` declaration
3. Blank line
4. `use` imports — alphabetical within groups:
   - External/vendor classes first (`Illuminate\*`, `PHPUnit\*`)
   - Internal app classes second (`App\*`, `Database\*`, `Tests\*`)

Example from `app/Models/User.php`:
```php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
```

Commented-out imports are left in place when the feature is optional or pending:
```php
// use Illuminate\Contracts\Auth\MustVerifyEmail;
```

## PHPDoc Blocks

Methods with non-obvious generics are documented with PHPDoc:
```php
/**
 * Get the attributes that should be cast.
 *
 * @return array<string, string>
 */
protected function casts(): array
```

Generic factory extensions use `@extends` tag:
```php
/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
```

Trait usage with generic type hint uses `@use`:
```php
/** @use HasFactory<UserFactory> */
use HasFactory, Notifiable;
```

Method descriptions use a short summary sentence:
```php
/**
 * Register any application services.
 */
public function register(): void
```

## Model Design

**Attributes via PHP 8 Attributes (not `$fillable`/`$hidden` arrays):**
```php
// app/Models/User.php
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
```

**Casts defined via method (not `$casts` property):**
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

**Traits on one line when multiple:**
```php
use HasFactory, Notifiable;
```

## Controller Design

The base `Controller` class (`app/Http/Controllers/Controller.php`) is abstract and empty — new controllers extend it:
```php
abstract class Controller
{
    //
}
```

## Service Provider Design

`AppServiceProvider` (`app/Providers/AppServiceProvider.php`) provides `register()` and `boot()` stubs both with explicit `void` return types:
```php
public function register(): void { }
public function boot(): void { }
```

## Application Bootstrap

The app is configured via `bootstrap/app.php` using fluent method chaining:
```php
Application::configure(basePath: dirname(__DIR__))
    ->withRouting(...)
    ->withMiddleware(...)
    ->withExceptions(...)
    ->create();
```

API routes return JSON automatically when the request matches `api/*` — no explicit `Accept: application/json` header required.

## Error Handling

**API errors:** JSON rendered automatically for all `api/*` routes via `withExceptions()` in `bootstrap/app.php`.

**Web errors:** Default Laravel exception rendering.

## Logging

Not yet configured beyond Laravel defaults. Logging configuration is in `config/logging.php`.

## Comments

**Inline comments:** Used to disable optional code that is not yet needed:
```php
// User::factory(10)->create();
// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Illuminate\Foundation\Testing\RefreshDatabase;
```

**Block comments:** Short single-line descriptive PHPDoc, not prose paragraphs.

**Empty method bodies:** Use `//` placeholder:
```php
public function register(): void
{
    //
}
```

---

*Convention analysis: 2026-06-27*
