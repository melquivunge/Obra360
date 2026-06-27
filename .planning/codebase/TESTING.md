# Testing Patterns

**Analysis Date:** 2026-06-27

## Test Framework

**Runner:**
- PHPUnit 12.5+ (`phpunit/phpunit` ^12.5.12)
- Config: `phpunit.xml` (project root)
- Bootstrap: `vendor/autoload.php`
- Colors enabled by default

**Mocking Library:**
- Mockery (`mockery/mockery` ^1.6)

**Assertion Library:**
- PHPUnit built-in assertions + Laravel's `TestResponse` assertions for HTTP

**Run Commands:**
```bash
composer test                  # Recommended: clears config cache, then runs all tests
php artisan test               # Runs all tests via Artisan
./vendor/bin/phpunit           # Run PHPUnit directly
./vendor/bin/phpunit --testsuite Unit     # Run only Unit suite
./vendor/bin/phpunit --testsuite Feature  # Run only Feature suite
```

## Test File Organization

**Location:** Separate `tests/` directory, not co-located with source

**Structure:**
```
tests/
├── TestCase.php          # Base class for all tests (extends Laravel's TestCase)
├── Unit/                 # Pure unit tests (no Laravel app boot)
│   └── ExampleTest.php
└── Feature/              # Integration/HTTP tests (full Laravel app)
    └── ExampleTest.php
```

**Naming:**
- Files: `PascalCase` with `Test` suffix: `ExampleTest.php`, `UserTest.php`
- Methods: `snake_case` with `test_` prefix: `test_that_true_is_true`

## Test Suites

**Unit Suite** (`tests/Unit`):
- Extends `PHPUnit\Framework\TestCase` directly — Laravel app is NOT booted
- Use for pure logic, helpers, value objects, isolated class behavior
- Faster execution

**Feature Suite** (`tests/Feature`):
- Extends `Tests\TestCase` which extends `Illuminate\Foundation\Testing\TestCase`
- Full Laravel app is booted, database and HTTP available
- Use for HTTP endpoints, database interactions, full request/response cycle

## Test Structure

**Unit Test Pattern:**
```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
```

**Feature Test Pattern:**
```php
<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
```

## Base TestCase

`tests/TestCase.php` is an abstract class that extends `Illuminate\Foundation\Testing\TestCase`:
```php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}
```

All Feature tests must extend `Tests\TestCase`. Unit tests extend `PHPUnit\Framework\TestCase` directly.

## Database in Tests

**Connection:** SQLite in-memory (`DB_DATABASE=:memory:`) — configured in `phpunit.xml`

**Driver:** `DB_CONNECTION=sqlite`

**RefreshDatabase Trait:**
- Available from `Illuminate\Foundation\Testing\RefreshDatabase`
- Commented out in `tests/Feature/ExampleTest.php` — add it to Feature tests that interact with the database
- Usage:
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_something(): void
    {
        // database is migrated fresh for each test
    }
}
```

## Test Environment Configuration

All set in `phpunit.xml` `<php>` block — no `.env.testing` file required:

| Variable | Test Value | Reason |
|---|---|---|
| `APP_ENV` | `testing` | Enables test mode |
| `BCRYPT_ROUNDS` | `4` | Faster password hashing |
| `BROADCAST_CONNECTION` | `null` | Disable broadcasts |
| `CACHE_STORE` | `array` | In-memory, no Redis/file |
| `DB_CONNECTION` | `sqlite` | SQLite for speed |
| `DB_DATABASE` | `:memory:` | In-memory, no file I/O |
| `MAIL_MAILER` | `array` | Capture mail, don't send |
| `QUEUE_CONNECTION` | `sync` | Run jobs synchronously |
| `SESSION_DRIVER` | `array` | In-memory sessions |
| `PULSE_ENABLED` | `false` | Disable Pulse |
| `TELESCOPE_ENABLED` | `false` | Disable Telescope |
| `NIGHTWATCH_ENABLED` | `false` | Disable Nightwatch |

## Factories

**Location:** `database/factories/`

**Base class:** `Illuminate\Database\Eloquent\Factories\Factory`

**Pattern:**
```php
// database/factories/UserFactory.php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

**Key conventions:**
- Use `fake()` global helper (not `$this->faker`)
- Cache expensive operations (like `Hash::make`) as static class properties
- Implement model states as methods returning `$this->state(fn (...) => [...])`
- State methods return `static` for proper subclass chaining
- PHPDoc `@extends Factory<ModelClass>` required on class

**Using factories in tests:**
```php
// Create and persist a model
$user = User::factory()->create();

// Create with overrides
$user = User::factory()->create(['name' => 'Jane']);

// Create without persisting
$user = User::factory()->make();

// Use a named state
$user = User::factory()->unverified()->create();

// Create multiple
$users = User::factory(10)->create();
```

## Mocking

**Framework:** Mockery (`mockery/mockery` ^1.6) is available

**Laravel built-in faking** is preferred over Mockery for Laravel facades and services:
```php
// Prefer these built-in fakes in Feature tests:
Mail::fake();
Event::fake();
Queue::fake();
Storage::fake('public');
Http::fake();
Notification::fake();
```

**Mockery** is used for non-Laravel dependencies or when granular expectations are needed:
```php
$mock = Mockery::mock(SomeService::class);
$mock->shouldReceive('doSomething')->once()->andReturn('result');
$this->app->instance(SomeService::class, $mock);
```

## Coverage

**Source directory:** `app/` (configured in `phpunit.xml` `<source><include>` block)

**Coverage target:** Not enforced — no minimum threshold configured

**View coverage:**
```bash
./vendor/bin/phpunit --coverage-html coverage/
```

## Seeders in Tests

`DatabaseSeeder` (`database/seeders/DatabaseSeeder.php`) uses the `WithoutModelEvents` trait to suppress model events during seeding:
```php
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
```

Seeders can be called in tests with `RefreshDatabase`:
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(); // runs DatabaseSeeder
        // or: $this->seed(UserSeeder::class);
    }
}
```

## HTTP Testing (Feature Tests)

Use `$this->get()`, `$this->post()`, `$this->put()`, `$this->delete()` etc:
```php
$response = $this->get('/');
$response->assertStatus(200);

$response = $this->postJson('/api/users', ['name' => 'Test']);
$response->assertCreated()->assertJson(['name' => 'Test']);
```

Authenticate as a user using `$this->actingAs()`:
```php
$user = User::factory()->create();
$response = $this->actingAs($user)->get('/dashboard');
```

## Common Patterns

**Async/Queue Testing:**
```php
Queue::fake();
// trigger action
Queue::assertPushed(MyJob::class);
```

**Error/Exception Testing:**
```php
$this->expectException(ValidationException::class);
// or for HTTP:
$response->assertUnprocessable(); // 422
$response->assertForbidden();     // 403
$response->assertNotFound();      // 404
```

**Database Assertions:**
```php
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);
$this->assertDatabaseCount('users', 1);
```

---

*Testing analysis: 2026-06-27*
