# Padrões de Teste

**Data da análise:** 2026-08-22

## Framework de teste

**Executor:**
- PHPUnit 12.5.30 (`phpunit/phpunit`)
- Configuração: `phpunit.xml`
- Bootstrap: `vendor/autoload.php`
- Suites: `Unit` (`tests/Unit/`) e `Feature` (`tests/Feature/`)
- Cobertura declarada em `<source><include><directory>app</directory>` — não há limiar imposto

**Dimensão actual:**
- 32 ficheiros de teste: 30 em `Feature/` (8 dos quais em `Feature/Auth/`) e 2 em `Unit/`
- **221 testes** (`./vendor/bin/phpunit --list-tests`). A diferença face ao número de métodos `test_` vem dos casos gerados por `#[DataProvider]` em `ReportTest`

**Biblioteca de asserções:**
- Asserções do PHPUnit: `assertTrue()`, `assertSame()`, `assertNotNull()`, `assertIsArray()`, `assertGreaterThan()`, `assertStringContainsString()`, `assertInstanceOf()`
- Asserções HTTP do Laravel: `assertOk()`, `assertStatus()`, `assertRedirect()`, `assertForbidden()`, `assertNotFound()`, `assertSessionHasErrors()`, `assertSee()`, `assertHeader()`
- Asserções de base de dados: `assertDatabaseHas()`, `assertDatabaseMissing()`, `assertSoftDeleted()`
- Asserções de ficheiros: `Storage::disk('local')->assertExists()` / `assertMissing()`
- Asserções de consola: `$this->artisan(...)->assertSuccessful()` / `assertFailed()`

**Comandos:**
```bash
php artisan test                    # Corre tudo
php artisan test --filter=Report    # Filtra por nome
composer test                       # config:clear seguido de artisan test
./vendor/bin/phpunit --list-tests   # Lista os testes sem os correr
./vendor/bin/phpunit --testdox      # Saída legível
```

## Ambiente de teste

**Base de dados:**
- SQLite em memória, definido em `phpunit.xml`:
  ```xml
  <env name="DB_CONNECTION" value="sqlite"/>
  <env name="DB_DATABASE" value=":memory:"/>
  ```
- Todas as classes usam `RefreshDatabase`, que migra e limpa por teste
- Consequência prática: as consultas com SQL cru têm de funcionar em SQLite **e** em MySQL — daí o `SUBSTR(spent_at, 1, 7)` em `Project::expensesByMonth()`

**Restante configuração (`phpunit.xml`):**
```xml
<env name="APP_ENV" value="testing"/>
<env name="APP_MAINTENANCE_DRIVER" value="file"/>
<env name="BCRYPT_ROUNDS" value="4"/>
<env name="BROADCAST_CONNECTION" value="null"/>
<env name="CACHE_STORE" value="array"/>
<env name="MAIL_MAILER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
```
`BROADCAST_CONNECTION=null` evita ligações ao Reverb nos testes; `QUEUE_CONNECTION=sync` faz as notificações em fila correrem em linha.

## Organização dos ficheiros

**Localização:** `tests/`, separado de `app/`, com `tests/Unit/`, `tests/Feature/` e `tests/Feature/Auth/`

**Nomenclatura:**
- Classes em PascalCase terminadas em `Test`
- Métodos com prefixo `test_` e snake_case, a descrever o comportamento esperado

**Inventário:**

| Ficheiro | Cobre |
|----------|-------|
| `Feature/Auth/AuthenticationTest.php` | Ecrã de login e autenticação |
| `Feature/Auth/RegistrationTest.php` | Confirma que o registo público está desactivado (404) |
| `Feature/Auth/InactiveLoginTest.php` | Bloqueio de utilizadores inactivos e mensagem em português |
| `Feature/Auth/RoleRedirectTest.php` | Encaminhamento por papel depois do login e 403 em rotas alheias |
| `Feature/Auth/PasswordResetTest.php`, `PasswordUpdateTest.php`, `PasswordConfirmationTest.php`, `EmailVerificationTest.php` | Fluxos de palavra-passe e verificação de e-mail |
| `Feature/ProjectCrudTest.php` | CRUD de obras e âmbito de escrita do gestor |
| `Feature/ProjectPhaseTest.php` | CRUD de fases, ordenação e recálculo do progresso |
| `Feature/ProgressModeTest.php` | `progress_mode` derivado vs. manual (FASE-04) |
| `Feature/ProjectFinancialsTest.php` | Totais, agregação por categoria e por mês |
| `Feature/ExpenseUpdateTest.php` | Edição de despesa e substituição de recibo |
| `Feature/PaymentTest.php` | Criação, edição, marcação como pago e totais |
| `Feature/PhotoGalleryTest.php` | Miniaturas (FOTO-03) e filtros da galeria (FOTO-04) |
| `Feature/DocumentAccessTest.php` | Visibilidade e download de documentos |
| `Feature/MissingMediaTest.php` | Registo de media sem ficheiro devolve 404, não 500 |
| `Feature/MigratePublicMediaTest.php` | Comando `obra360:media:migrate-public` |
| `Feature/ReportTest.php` | Quatro relatórios em ecrã e em PDF, tipo desconhecido, âmbito por papel |
| `Feature/AuditLogTest.php` | Trilho de auditoria (SEC-05) |
| `Feature/NotificationToggleTest.php` | Interruptores `notify_{tipo}` (CONF-04) |
| `Feature/ProjectNotificationTest.php` | Notificação de actualização de obra |
| `Feature/ClientLayoutCacheTest.php` | A cache do layout do cliente nunca guarda modelos Eloquent |
| `Feature/DashboardKpiTest.php` | KPIs de atraso e de obras quase concluídas (DASH-01) |
| `Feature/SecurityHardeningTest.php` | Autorização, disco privado, uploads e rotas aninhadas |
| `Feature/SecurityHeadersTest.php` | Cabeçalhos de segurança |
| `Feature/SchemaTest.php` | Existência de tabelas e colunas |
| `Feature/ProfileTest.php` | Perfil do utilizador |
| `Feature/UserManagementTest.php` | CRUD de utilizadores e regras de administrador |
| `Unit/UserRoleTest.php` | Métodos `isAdmin()`, `isClient()`, ... |

## Estrutura dos testes

**Classe base:**
```php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}
```

**Teste de feature:**
```php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_project_list(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.index'))
            ->assertOk();
    }
}
```

**Teste unitário:**
```php
namespace Tests\Unit;

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

## Traits e preparação

**RefreshDatabase:** usado em todos os testes com base de dados; migra antes e reverte depois de cada método.

**`setUp()` partilhado:** as classes mais recentes preparam o cenário uma vez, em vez de o repetir por método. Exemplo de `MissingMediaTest`:
```php
protected function setUp(): void
{
    parent::setUp();

    Storage::fake('local');
    Storage::fake('public');

    $client = Client::factory()->create();
    $this->clientUser = User::factory()->create([
        'role' => User::ROLE_CLIENT,
        'client_id' => $client->id,
    ]);
    $this->project = Project::factory()->create([
        'client_id' => $client->id,
        'cover_image_path' => 'covers/desaparecida.png',
    ]);
}
```

**Auxiliares privados:** construção de cenário e de payload em métodos privados — `private function project(): Project`, `private function payload(Project $project, array $overrides = []): array`, `private function validCreatePayload(array $overrides = []): array`.

## Data providers

`ReportTest` usa o atributo do PHPUnit 12 para cobrir os quatro tipos de relatório sem duplicar código:

```php
public static function reportTypes(): array
{
    return [['geral'], ['financeiro'], ['fotos'], ['fases']];
}

#[DataProvider('reportTypes')]
public function test_admin_can_download_every_report_as_pdf(string $type): void
{
    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.reports.pdf', [$this->project(), $type]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $this->assertStringStartsWith('%PDF', $response->getContent());
}
```

## Testes HTTP

**Asserções e auxiliares:**
```php
$this->actingAs($user);                     // Autenticar
$this->assertAuthenticated();               // Sessão activa
$this->assertGuest();                       // Sem sessão

$response->assertOk();                      // 200
$response->assertForbidden();               // 403
$response->assertNotFound();                // 404
$response->assertRedirect(route('...'));    // Destino da redirecção
$response->assertSessionHasErrors(['campo']);
$response->assertSee('texto');
$response->assertDontSee('texto');
$response->assertHeader('content-type', 'application/pdf');
```

## Asserções de base de dados

```php
$this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'joao@example.com']);
$this->assertSoftDeleted('users', ['id' => $user->id]);
```

## Factories

Oito factories em `database/factories/`: `UserFactory`, `ClientFactory`, `ProjectFactory`, `ProjectPhaseFactory`, `PhotoFactory`, `ExpenseFactory`, `DocumentFactory`, `PaymentFactory`.

`UserFactory` tem os estados `admin()`, `manager()`, `financial()` e `unverified()`:
```php
$admin = User::factory()->admin()->create();
$manager = User::factory()->manager()->create();
$financial = User::factory()->financial()->create();
$client = User::factory()->create(['role' => User::ROLE_CLIENT, 'client_id' => $client->id]);
```

As factories compõem-se por chave estrangeira, tanto por id como por instância:
```php
$project = Project::factory()->create(['client_id' => Client::factory(), 'progress' => 0]);
Expense::factory()->create(['project_id' => $project->id, 'category' => 'material', 'amount' => 750]);
```

## Simulação de armazenamento

`Storage::fake('local')` (e `Storage::fake('public')` quando o teste também tem de provar que **nada** foi escrito no disco público) é o padrão em todos os testes de upload e de media.

```php
public function test_documents_are_stored_on_private_disk(): void
{
    Storage::fake('local');
    Storage::fake('public');

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

**Miniaturas** — `PhotoGalleryTest` inspecciona os bytes gerados, não apenas o caminho:
```php
$photo = Photo::first();
$size = getimagesizefromstring(Storage::disk('local')->get($photo->thumbnail_path));

$this->assertSame(Thumbnail::MAX_EDGE, $size[0]);
$this->assertLessThanOrEqual(Thumbnail::MAX_EDGE, $size[1]);
$this->assertStringContainsString('/thumbs/', $photo->thumbnail_path);
```

**Media em falta** — o comportamento correcto é 404, e está coberto dos dois lados (portal e área interna):
```php
public function test_a_cover_without_a_file_returns_404(): void
{
    $this->actingAs($this->clientUser)
        ->get(route('portal.projects.cover', $this->project))
        ->assertNotFound();
}
```

## Simulação de notificações e eventos

```php
Notification::fake();
Event::fake();
```
`NotificationToggleTest` e `ProjectNotificationTest` usam `Notification::fake()` para verificar que a definição `notify_{tipo}` silencia (ou não) o envio; `EmailVerificationTest` usa `Event::fake()`.

## Testes de comandos artisan

`MigratePublicMediaTest` exercita o comando de migração de media com discos falsos:
```php
Storage::fake('local');
Storage::fake('public');

$this->artisan('obra360:media:migrate-public --commit --delete-source')->assertSuccessful();
$this->artisan('obra360:media:migrate-public')->assertSuccessful();       // dry-run
$this->artisan('obra360:media:migrate-public --delete-source')->assertFailed();  // exige --commit
```

## Testes de cache

`ClientLayoutCacheTest` prova a regra crítica do `ClientLayoutComposer` — nunca guardar modelos Eloquent — e reproduz a falha de produção com um `__PHP_Incomplete_Class` fabricado por `unserialize()`:

```php
private function incompleteClass(): object
{
    $class = 'App\\Models\\ClasseQueNaoExiste';

    return unserialize(sprintf('O:%d:"%s":1:{s:2:"id";i:1;}', strlen($class), $class));
}

public function test_a_corrupted_cache_value_does_not_break_the_page(): void
{
    foreach ($this->cacheKeys() as $key) {
        Cache::put($key, $this->incompleteClass(), 120);
    }

    $this->actingAs($this->clientUser)
        ->get(route('portal.dashboard'))
        ->assertOk();
}
```
O teste que percorre as chaves conta quantas foram escritas e falha se não encontrar nenhuma — impede que a asserção passe por não ter nada para verificar.

## Testes de validação

```php
public function test_create_with_invalid_role_fails_validation(): void
{
    $this->actingAs($this->adminUser())
        ->post(route('users.store'), $this->validCreatePayload(['role' => 'superuser']))
        ->assertSessionHasErrors('role');

    $this->assertDatabaseMissing('users', ['email' => 'joao@example.com']);
}

public function test_receipt_upload_rejects_unapproved_file_types(): void
{
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

## Testes de autorização e de âmbito

```php
public function test_manager_cannot_access_admin_only_settings_or_clients(): void
{
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)->get(route('admin.settings.index'))->assertForbidden();
    $this->actingAs($manager)->get(route('admin.clients.index'))->assertForbidden();
}

public function test_manager_only_sees_assigned_projects(): void
{
    $manager = User::factory()->manager()->create();
    $ownProject = Project::factory()->create(['name' => 'Assigned project', 'manager_id' => $manager->id]);
    Project::factory()->create(['name' => 'Other manager project']);

    $this->actingAs($manager)
        ->get(route('admin.projects.index'))
        ->assertOk()
        ->assertSee($ownProject->name)
        ->assertDontSee('Other manager project');
}

public function test_nested_phase_cannot_be_updated_through_another_project(): void
{
    $this->actingAs($admin)
        ->put(route('admin.projects.phases.update', [$projectA, $phase]), [...])
        ->assertNotFound();
}
```

## Testes de auditoria

```php
public function test_updating_records_before_and_after(): void
{
    $this->actingAs(User::factory()->admin()->create());
    $project = Project::factory()->create(['name' => 'Antes']);

    $project->update(['name' => 'Depois']);

    $log = AuditLog::where('action', 'updated')->latest('id')->first();

    $this->assertSame('Antes', $log->changes['de']['name']);
    $this->assertSame('Depois', $log->changes['para']['name']);
}
```
Cobre também que uma actualização sem alterações reais não gera registo e que os campos redigidos nunca aparecem em `changes`.

## Testes JSON

Não existem rotas `api/*`. As respostas JSON que existem vêm de rotas web (`Client\ProjectController@messages` com `wantsJson()`, `@storeMessage`, `Admin\MessageController@messages`, `portal.messages.mark-read`) e são testadas com `get()`/`post()` normais.

## Cobertura

- Sem limiar imposto no CI; `phpunit.xml` declara `<source>` sobre `app/`, o que basta para gerar relatório quando pedido
- Relatório local:
  ```bash
  ./vendor/bin/phpunit --coverage-html coverage/
  ```

## Testes no CI

`.github/workflows/laravel.yml`, job `test` (PHP 8.4, Node 24):
```bash
php artisan migrate --force          # DB_CONNECTION=sqlite, ficheiro database/database.sqlite
./vendor/bin/pint --test             # Estilo
npm run build                        # Build de assets
php artisan test                     # Suite completa
composer audit --abandoned=report    # Advisories PHP (pacotes abandonados não falham o build)
npm audit --audit-level=moderate     # Advisories npm
```
O job `deploy` depende do `test` e só corre em push para `main`.

## Referência rápida de asserções

| Asserção | Objectivo |
|----------|-----------|
| `$response->assertOk()` | 200 |
| `$response->assertForbidden()` | 403 |
| `$response->assertNotFound()` | 404 |
| `$response->assertRedirect(route(...))` | Destino da redirecção |
| `$response->assertHeader('content-type', ...)` | Cabeçalho da resposta |
| `$response->assertSee('texto')` / `assertDontSee()` | Conteúdo visível |
| `$response->assertSessionHasErrors(['campo'])` | Erros de validação |
| `$this->assertDatabaseHas('tabela', [...])` | Registo existe |
| `$this->assertDatabaseMissing('tabela', [...])` | Registo não existe |
| `$this->assertSoftDeleted('tabela', ['id' => 1])` | Registo removido em soft delete |
| `$this->assertAuthenticated()` / `assertGuest()` | Estado de sessão |
| `Storage::disk('local')->assertExists($path)` | Ficheiro escrito |
| `Storage::disk('public')->assertMissing($path)` | Ficheiro não escrito no disco público |
| `$this->artisan('...')->assertSuccessful()` / `assertFailed()` | Código de saída de um comando |
| `Notification::assertSentTo()` / `assertNotSentTo()` | Notificações enviadas ou silenciadas |

---

*Análise de testes: 2026-08-22*
