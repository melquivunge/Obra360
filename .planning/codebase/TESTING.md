# Testing Patterns

**Analysis Date:** 2026-08-20

## Test Framework

**Runner:**
- PHPUnit 12.5.12 (`phpunit/phpunit`)
- Config: `phpunit.xml`
- Bootstrap: `vendor/autoload.php`
- Test suites: `Unit` (tests/Unit/), `Feature` (tests/Feature/)

**Assertion Library:**
- PHPUnit's built-in assertions: `$this->assertTrue()`, `$this->assertFalse()`, `$this->assertSame()`, `$this->assertNotNull()`, etc.
- Laravel's HTTP testing assertions: `$response->assertStatus(200)`, `$response->assertRedirect()`, `$response->assertSessionHasErrors()`
- Database assertions: `$this->assertDatabaseHas()`, `$this->assertDatabaseMissing()`, `$this->assertSoftDeleted()`

**Run Commands:**
```bash
php artisan test                 # Run all tests
php artisan test --filter=Auth   # Run tests matching filter
php artisan test --env=testing   # Explicitly set testing environment
composer test                    # Run via composer (same as above)
./vendor/bin/phpunit --testdox   # Run with verbose output
```

## Test Environment

**Database:**
- SQLite in-memory for speed (`:memory:` via `phpunit.xml`)
- Configured via `phpunit.xml` environment variables:
  ```xml
  <env name="DB_CONNECTION" value="sqlite"/>
  <env name="DB_DATABASE" value=":memory:"/>
  ```
- Each test class using `RefreshDatabase` trait auto-migrates and cleans up after each test

**Other Test Config (phpunit.xml):**
```xml
<env name="APP_ENV" value="testing"/>
<env name="CACHE_STORE" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
<env name="BCRYPT_ROUNDS" value="4"/>  <!-- Faster hashing for tests -->
```

## Test File Organization

**Location:**
- Co-located in `tests/` directory separate from `app/`
- Organized by test type: `tests/Unit/`, `tests/Feature/`, `tests/Feature/Auth/`
- Directory structure mirrors feature structure

**Naming:**
- Class files: PascalCase ending in `Test`: `UserRoleTest.php`, `AuthenticationTest.php`, `SecurityHardeningTest.php`
- Test methods: `test_` prefix followed by snake_case describing behavior: `test_admin_can_create_user_with_valid_data()`, `test_inactive_user_cannot_login_with_correct_credentials()`

**File Paths:**
- Feature tests: `tests/Feature/Auth/AuthenticationTest.php`, `tests/Feature/Auth/RoleRedirectTest.php`, `tests/Feature/UserManagementTest.php`, `tests/Feature/SecurityHardeningTest.php`, `tests/Feature/SchemaTest.php`
- Unit tests: `tests/Unit/UserRoleTest.php`, `tests/Unit/ExampleTest.php`
- Base class: `tests/TestCase.php` (extends `Illuminate\Foundation\Testing\TestCase`)

## Test Structure

**Base TestCase:**
```php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}
```

**Feature Test Pattern:**
```php
namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('portal.dashboard', absolute: false));
    }
}
```

**Unit Test Pattern:**
```php
namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_admin_role_returns_true_from_is_admin(): void
    {
        $user = new User;
        $user->role = 'admin';

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isClient());
    }
}
```

## Traits & Setup

**RefreshDatabase:**
- Used in almost all tests to ensure clean state
- Automatically runs migrations before each test, rolls back after
- Enables `$this->assertDatabaseHas()`, `$this->assertDatabaseMissing()`, etc.

**Setup Helpers (in test classes):**
```php
private function adminUser(): User
{
    return User::factory()->admin()->create();
}

private function managerUser(): User
{
    return User::factory()->manager()->create();
}

private function validCreatePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'phone' => '11999990000',
        'role' => User::ROLE_MANAGER,
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ], $overrides);
}
```

## HTTP Testing

**HTTP Assertions (from Laravel):**
```php
$response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

$response->assertStatus(200);              // HTTP status
$response->assertRedirect(route('...'));   // Redirect destination
$response->assertSessionHasErrors(['field']); // Validation errors
$response->assertSee('String in HTML');    // Content visible
$response->assertDontSee('Hidden string'); // Content hidden
$response->assertOk();                     // 200 status
$response->assertForbidden();              // 403 status
$response->assertNotFound();               // 404 status
```

**Authentication Helpers:**
```php
$user = User::factory()->create();
$this->actingAs($user);                    // Authenticate as user
$this->assertAuthenticated();              // User is logged in
$this->assertGuest();                      // No user authenticated
```

**Example from RoleRedirectTest:**
```php
public function test_admin_is_redirected_to_admin_dashboard_after_login(): void
{
    $admin = User::factory()->admin()->create();

    $response = $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('admin.dashboard'));
}

public function test_client_hitting_admin_route_receives_403(): void
{
    $client = User::factory()->create();

    $response = $this->actingAs($client)->get('/admin');

    $response->assertStatus(403);
}
```

## Database Assertions

**Patterns:**
```php
// Check record exists with specific attributes
$this->assertDatabaseHas('users', [
    'email' => 'joao@example.com',
    'role' => User::ROLE_MANAGER,
]);

// Check record does NOT exist
$this->assertDatabaseMissing('users', ['email' => 'joao@example.com']);

// Check soft-deleted record
$this->assertSoftDeleted('users', ['id' => $user->id]);

// Verify record was deleted after soft delete
$found = User::withTrashed()->find($user->id);
$this->assertNotNull($found);
$this->assertNotNull($found->deleted_at);
```

**Example from UserManagementTest:**
```php
public function test_admin_can_create_user_with_valid_data(): void
{
    $admin = $this->adminUser();

    $this->actingAs($admin)
        ->post(route('users.store'), $this->validCreatePayload())
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'joao@example.com',
        'role' => User::ROLE_MANAGER,
    ]);
}

public function test_delete_soft_deletes_user(): void
{
    $admin = $this->adminUser();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    $this->assertSoftDeleted('users', ['id' => $user->id]);
}
```

## Factories

**Pattern:**
- Located in `database/factories/`
- Extend `Illuminate\Database\Eloquent\Factories\Factory<Model>`
- Implement `definition(): array` for default state
- Chain factory methods for variations

**Example from UserFactory.php:**
```php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
            'role' => User::ROLE_CLIENT,
            'phone' => fake()->phoneNumber(),
            'active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_MANAGER,
        ]);
    }
}
```

**Factory Usage in Tests:**
```php
// Create single instance
$user = User::factory()->create();
$admin = User::factory()->admin()->create();

// Create multiple instances
$users = User::factory()->count(5)->create();

// Create without persisting
$user = User::factory()->make();

// Chain factory methods
$unverifiedManager = User::factory()->manager()->unverified()->create();
```

## Test Data & Fixtures

**FakerPHP Integration:**
- Installed as `fakerphp/faker` v1.23
- Used via `fake()` helper in factories: `fake()->name()`, `fake()->unique()->safeEmail()`, `fake()->phoneNumber()`
- Provides realistic dummy data without manual fixture files

**Test Payloads (from UserManagementTest):**
```php
private function validCreatePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'phone' => '11999990000',
        'role' => User::ROLE_MANAGER,
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ], $overrides);
}

// Usage:
$this->post(route('users.store'), $this->validCreatePayload());

// With overrides:
$this->post(route('users.store'), $this->validCreatePayload([
    'role' => 'superuser'  // Invalid role to test validation
]));
```

## Storage Faking

**Pattern:**
- Use `Storage::fake('disk')` to mock filesystem in tests
- Prevents writing to actual disk during tests
- Assert file operations without side effects

**Example from SecurityHardeningTest:**
```php
public function test_documents_are_stored_on_private_disk(): void
{
    Storage::fake('local');
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $project = Project::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.projects.documents.store', $project), [
            'file' => UploadedFile::fake()->create('contract.pdf', 10, 'application/pdf'),
            'name' => 'Contrato',
            'type' => 'contrato',
            'visibility' => 'cliente',
        ])
        ->assertRedirect();

    $document = Document::firstOrFail();
    Storage::disk('local')->assertExists($document->path);
    Storage::disk('public')->assertMissing($document->path);
}
```

## Validation Testing

**Testing Form Validation:**
```php
public function test_create_with_invalid_role_fails_validation(): void
{
    $admin = $this->adminUser();

    $this->actingAs($admin)
        ->post(route('users.store'), $this->validCreatePayload(['role' => 'superuser']))
        ->assertSessionHasErrors('role');

    $this->assertDatabaseMissing('users', ['email' => 'joao@example.com']);
}

public function test_create_with_duplicate_email_fails_validation(): void
{
    $admin = $this->adminUser();
    User::factory()->create(['email' => 'joao@example.com']);

    $this->actingAs($admin)
        ->post(route('users.store'), $this->validCreatePayload())
        ->assertSessionHasErrors('email');
}
```

## JSON API Testing (Reserved)

**Pattern Not Currently Used:**
- No API routes exist yet (all routes render HTML)
- When API routes are added, use `postJson()`, `getJson()` instead of `post()`, `get()`
- Example (for future implementation):
```php
$this->postJson(route('api.projects.store'), $payload)
    ->assertStatus(201)
    ->assertJson(['id' => 1, 'name' => 'Project']);
```

## Test Coverage

**Target:** Not enforced by CI (no coverage requirement in phpunit.xml)

**View Coverage:**
```bash
./vendor/bin/phpunit --coverage-html coverage/
# Open coverage/index.html in browser
```

**Coverage Config (if added to phpunit.xml):**
```xml
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">app</directory>
    </include>
    <report>
        <html outputDirectory="coverage"/>
        <text outputFile="php://stdout" showUncoveredFiles="true"/>
    </report>
</coverage>
```

## Test Categories

**Unit Tests (`tests/Unit/`):**
- Test model logic in isolation
- No database required (though `RefreshDatabase` still used for consistency)
- Example: `UserRoleTest` — verifies role helper methods

**Feature Tests (`tests/Feature/`):**
- Test full request/response cycles
- Database integration
- Auth flow, redirects, permissions
- Examples:
  - `Auth/AuthenticationTest.php` — login/logout
  - `Auth/RoleRedirectTest.php` — role-based redirects
  - `Auth/InactiveLoginTest.php` — inactive user blocking
  - `UserManagementTest.php` — CRUD operations
  - `SecurityHardeningTest.php` — authorization, file storage
  - `SchemaTest.php` — database schema validation

## Common Testing Patterns

**Authorization Testing (from SecurityHardeningTest):**
```php
public function test_manager_cannot_access_admin_only_settings_or_clients(): void
{
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('admin.settings.index'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('admin.clients.index'))
        ->assertForbidden();
}

public function test_client_cannot_download_internal_document(): void
{
    $client = Client::factory()->create();
    $user = User::factory()->create([
        'role' => User::ROLE_CLIENT,
        'client_id' => $client->id,
    ]);
    $project = Project::factory()->create(['client_id' => $client->id]);
    $document = Document::factory()->create([
        'project_id' => $project->id,
        'visibility' => 'interno',
    ]);

    $this->actingAs($user)
        ->get(route('portal.projects.documents.download', [$project, $document]))
        ->assertForbidden();
}
```

**Scope Testing (from SecurityHardeningTest):**
```php
public function test_manager_only_sees_assigned_projects(): void
{
    $manager = User::factory()->manager()->create();
    $ownProject = Project::factory()->create([
        'name' => 'Assigned project',
        'manager_id' => $manager->id,
    ]);
    Project::factory()->create(['name' => 'Other manager project']);

    $this->actingAs($manager)
        ->get(route('admin.projects.index'))
        ->assertOk()
        ->assertSee($ownProject->name)
        ->assertDontSee('Other manager project');
}
```

**Nested Route Protection (from SecurityHardeningTest):**
```php
public function test_nested_phase_cannot_be_updated_through_another_project(): void
{
    $admin = User::factory()->admin()->create();
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();
    $phase = ProjectPhase::factory()->create(['project_id' => $projectB->id]);

    $this->actingAs($admin)
        ->put(route('admin.projects.phases.update', [$projectA, $phase]), [
            'name' => 'Tampered',
            'status' => 'pendente',
            'percentage' => 10,
        ])
        ->assertNotFound();

    $this->assertNotSame('Tampered', $phase->fresh()->name);
}
```

**File Upload Testing (from SecurityHardeningTest):**
```php
public function test_receipt_upload_rejects_unapproved_file_types(): void
{
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.projects.expenses.store', $project), [
            'category' => 'material',
            'description' => 'Unsafe receipt',
            'amount' => 100,
            'spent_at' => now()->toDateString(),
            'receipt' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
        ])
        ->assertSessionHasErrors('receipt');
}
```

**Portuguese Error Messages Testing (from InactiveLoginTest):**
```php
public function test_inactive_user_sees_portuguese_inactive_error_message(): void
{
    $user = User::factory()->create(['active' => false]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $errors = session('errors');
    $this->assertStringContainsString('Sua conta está inativa', $errors->first('email'));
}
```

**Schema Validation Testing (from SchemaTest):**
```php
public function test_all_application_tables_exist(): void
{
    $this->assertTrue(Schema::hasTable('users'));
    $this->assertTrue(Schema::hasTable('clients'));
    $this->assertTrue(Schema::hasTable('projects'));
    $this->assertTrue(Schema::hasTable('documents'));
    $this->assertTrue(Schema::hasTable('messages'));
}

public function test_users_table_has_role_and_relations_columns(): void
{
    $this->assertTrue(Schema::hasColumns('users', [
        'role',
        'phone',
        'active',
        'client_id',
        'deleted_at',
    ]));
}
```

## Running Tests in CI

**GitHub Actions Workflow (`.github/workflows/laravel.yml`):**
```bash
# Migrations run automatically before tests
php artisan migrate --force

# Tests run with SQLite :memory:
php artisan test

# Syntax check
./vendor/bin/pint --test

# Both checked in CI/CD pipeline
```

## Test Assertions Quick Reference

| Assertion | Purpose |
|-----------|---------|
| `$response->assertStatus(200)` | HTTP status code |
| `$response->assertRedirect(route(...))` | Redirect destination |
| `$response->assertOk()` | 200 status |
| `$response->assertForbidden()` | 403 status |
| `$response->assertNotFound()` | 404 status |
| `$response->assertSee('text')` | Text in response |
| `$response->assertDontSee('text')` | Text NOT in response |
| `$response->assertSessionHasErrors(['field'])` | Validation errors |
| `$this->assertDatabaseHas('table', [...])` | Record exists |
| `$this->assertDatabaseMissing('table', [...])` | Record doesn't exist |
| `$this->assertSoftDeleted('table', ['id' => 1])` | Soft-deleted record |
| `$this->assertAuthenticated()` | User is logged in |
| `$this->assertGuest()` | No user logged in |
| `Storage::disk('local')->assertExists($path)` | File exists |
| `Storage::disk('local')->assertMissing($path)` | File doesn't exist |

---

*Testing analysis: 2026-08-20*
