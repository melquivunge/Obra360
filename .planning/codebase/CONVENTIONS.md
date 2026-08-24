# Convenções de Código

**Data da análise:** 2026-08-24

## Padrões de nomenclatura

**Ficheiros:**
- PascalCase para ficheiros de classe: `User.php`, `ProjectPhotoController.php`, `AuditObserver.php`, `Thumbnail.php`, `UserManagementTest.php`
- snake_case para configuração: `phpunit.xml`, `docker-compose.yml`
- kebab-case para componentes Blade: `text-input.blade.php`, `primary-button.blade.php`, `data-table.blade.php`, `flash-message.blade.php`
- `app/Support/helpers.php` é a excepção deliberada: ficheiro sem classe, carregado por `autoload.files`

**Funções e métodos:**
- camelCase: `storeUpdate()`, `recalcProgress()`, `expensesByCategory()`, `authorizeProjectAccess()`, `assertBelongsToProject()`, `settingKey()`
- Nomes descrevem o comportamento: `progressIsManual()`, `budgetUsedPercent()`, `isClientVisible()`, `invalidateUserSessions()`, `guardCriticalAdminChange()`
- Funções globais em snake_case: `money()`, `currency_symbol()`
- Métodos de teste: prefixo `test_` e snake_case — `test_a_cover_without_a_file_returns_404()`, `test_admin_can_download_every_report_as_pdf()`, `test_an_update_that_changes_nothing_is_not_recorded()`

**Variáveis e propriedades:**
- camelCase para variáveis locais: `$totalSpent`, `$lateProjects`, `$canViewFinancials`, `$isLastActiveAdmin`
- Métodos privados **sem** prefixo de sublinhado: `private function invalidateUserSessions()`, `private function mediaResponse()`, `private function photoPath()`
- Constantes de classe em UPPER_SNAKE_CASE: `User::ROLE_ADMIN`, `Project::STATUSES`, `ProjectPhase::STATUSES`, `Expense::CATEGORIES`, `Document::TYPES`, `ProjectUpdate::TYPES`, `AuditLog::ACTIONS`, `AuditLog::LABELS`, `ReportController::TYPES`, `Thumbnail::MAX_EDGE`

**Classes:**
- PascalCase, com sufixo pelo papel: `*Controller`, `*Factory`, `*Request`, `*Test`, `*Observer`, `*Channel`, `*Policy`
- Controllers de recurso aninhado levam o prefixo do pai: `ProjectPhaseController`, `ProjectPhotoController`, `ProjectExpenseController`, `ProjectDocumentController`
- Namespace por área: `Admin\`, `Client\`, `Auth\`, `Portal\`

**Nomes de rota:**
- Nomes em inglês, agrupados por ponto e prefixados pelo grupo: `admin.projects.show`, `admin.projects.photos.store`, `admin.reports.pdf`, `admin.audit.index`, `portal.dashboard`, `portal.projects.messages`
- Segmentos compostos em kebab-case: `users.toggle-active`, `admin.projects.payments.mark-paid`, `portal.messages.mark-read`
- **URLs em português:** `/obras`, `/clientes`, `/fotos`, `/documentos`, `/despesas`, `/pagamentos`, `/fases`, `/mensagens`, `/relatorios`, `/configuracoes`, `/auditoria`, `/usuarios`

## Estilo de código

**Formatação:**
- Laravel Pint (`laravel/pint` v1.30.5) é o formatador oficial
- Verificado no CI com `./vendor/bin/pint --test`; correcção manual com `./vendor/bin/pint`
- 4 espaços, sem tabs (`.editorconfig`)
- Fim de linha LF, UTF-8, sem espaços em fim de linha, nova linha final

**Linting:**
- Não há ESLint nem Prettier; o Pint é o único formatador imposto
- O CSS e o JS seguem o comportamento padrão do Vite, sem linter no CI

**Funcionalidades de PHP moderno:**
- PHP 8.3+ exigido pelo `composer.json`; 8.4 no Docker e no CI; 8.5 em produção
- **Atributos PHP 8** nos modelos, em vez de arrays de propriedades:
  ```php
  #[Fillable(['name', 'email', 'password', 'role', 'phone', 'active', 'client_id'])]
  #[Hidden(['password', 'remember_token'])]
  class User extends Authenticatable
  ```
- **Promoção de propriedades no construtor** em uso: `public function __construct(public Message $message)` (`MessageSent`), `public function __construct(private readonly ProjectUpdate $update)` (`ProjectUpdateNotification`)
- **Expressões `match`** em uso: `ReportController::data()` escolhe o conjunto de dados por tipo de relatório
- **Enums nativos não são usados** — os conjuntos fechados vivem em constantes de classe (`Project::STATUSES` e afins)
- Atributos de teste do PHPUnit 12: `#[DataProvider('reportTypes')]` em `ReportTest`

## Organização de imports

**Ordem (PHP):** declaração de namespace, depois um único bloco `use` ordenado alfabeticamente (é o Pint que o mantém assim), misturando `App\` e `Illuminate\`.

Exemplo de `app/Http/Controllers/Admin/ProjectPhotoController.php`:
```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Support\Thumbnail;
use App\Traits\AuthorizesProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
```

**Mapeamento PSR-4 (`composer.json`):**
- `App\` → `app/`
- `Database\Factories\` → `database/factories/`
- `Database\Seeders\` → `database/seeders/`
- `Tests\` → `tests/`
- `autoload.files`: `app/Support/helpers.php`

**Frontend (JavaScript/ESM):**
- Módulos ES: `import Alpine from 'alpinejs'`, `import Echo from 'laravel-echo'`, `import Pusher from 'pusher-js'`
- `Alpine.start()` corre antes da ligação ao Reverb, que por sua vez está dentro de `try/catch` para não bloquear a UI se falhar

## Declarações de tipo de retorno

A prática não é uniforme e a diferença é intencionalmente visível:

- **Com tipo de retorno:** modelos (`: BelongsTo`, `: HasMany`, `: bool`, `: float`, `: array`, `: void`), `FormRequest` (`: bool`, `: array`), `UserController` (`: View`, `: RedirectResponse`, `: void`), middleware (`: Response`), observers, canais, `Thumbnail` (`: ?string`, `: string`), comandos (`: int`), `ClientLayoutComposer` (`: void`, `: array`, `: int`, `: mixed`, `: bool`)
- **Sem tipo de retorno:** todos os métodos públicos dos controllers de `Admin/` e `Client/` — devolvem `View`, `RedirectResponse` ou `JsonResponse` conforme o caso, mas a assinatura não o declara. Os métodos privados desses mesmos controllers já declaram (`private function mediaResponse(string $path): StreamedResponse`)

## Blocos PHPDoc

- Usados em classes e métodos cuja intenção não se lê no código
- Tipos genéricos documentados: `@extends Factory<User>`, `@use HasFactory<UserFactory>`, `@return array<string, mixed>`, `@return array<int, array{key: string, label: string, total: float, percent: float}>`
- Muitos blocos explicam a decisão, não a mecânica — por exemplo, o cabeçalho de `ClientLayoutComposer` explica porque é que nunca se guardam modelos Eloquent na cache, e o de `AuditObserver` explica porque é que o observer é genérico

**Comentários:**
- Poucos comentários inline; o código é escrito para se ler sozinho
- Referências a requisitos são frequentes e servem de rasto: `SEC-05`, `FOTO-03`, `FOTO-04`, `FASE-04`, `FIN-02`, `FIN-03`, `FIN-04`, `NOTF-01/03`, `CONF-04`, `DASH-01`, `AUTH-01`, `AUTH-04`, `T-04-02`, `SEC-01`, `D-16`
- Marcador `ponytail:` para decisões de engenharia deliberadas com alternativa registada (`Thumbnail`, `helpers.php`, `SecurityHeaders`, `Project::expensesByMonth()`)
- Os comentários mais recentes estão em português; os mais antigos em inglês

## Desenho dos modelos

**Padrão:**
- Extensão de `Illuminate\Database\Eloquent\Model` ou `Illuminate\Foundation\Auth\User`
- `#[Fillable]` e `#[Hidden]` em vez de `$fillable`/`$hidden`
- `protected function casts(): array` explícito

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

**Relações:**
- Sempre com tipo de retorno: `: BelongsTo`, `: HasMany`, `: HasOne`
- Ordenação por omissão na própria relação: `hasMany(Photo::class)->latest()`, `hasMany(ProjectPhase::class)->orderBy('sort_order')`, `hasMany(Message::class)->oldest()`
- Onde essa ordenação atrapalha uma agregação, é anulada com `->reorder()` (ver `Project::expensesByCategory()` e `filteredPhotos()`)

**Ganchos de ciclo de vida:**
- `protected static function booted()` em `ProjectPhase` recalcula o progresso da obra em `saved` e `deleted`

**Soft deletes:**
- `User`, `Project`, `Client`, `Photo`, `Document`, `Expense` e `Plan`
- `PlanVersion` **não** usa soft delete, de propósito: uma versão substituída continua legível para sempre (PLAN-04)
- Consulta com `withTrashed()` / `onlyTrashed()`

**Lógica de domínio no modelo:**
- `Project` guarda o cálculo do progresso e as agregações financeiras, em vez de as espalhar pelos controllers

## Desenho dos controllers

**Padrão:**
- Estendem `App\Http\Controllers\Controller`
- `UserController` usa o trait `AuthorizesRequests` e chama `$this->authorize()`
- Os controllers de obra usam o trait `AuthorizesProjectAccess` e começam sempre por `$this->authorizeProjectAccess($project)`, seguido de `$this->assertBelongsToProject($project, $filho)` quando a rota é aninhada
- Métodos RESTful (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`); acções extra com nome explícito: `cover()`, `download()`, `markPaid()`, `toggleActive()`, `linkUser()`, `unlinkUser()`, `storeUpdate()`, `pdf()`
- Um controller por recurso aninhado: fases, fotos, despesas, documentos, plantas, versões de planta e linhas de orçamento têm classes próprias. O `Admin\ProjectController` fica com o CRUD da obra, a paginação de cada separador da página, a galeria filtrada, as actualizações manuais e as regras da ligação ao projeto de origem

## Validação de pedidos

Coexistem dois estilos, e a escolha segue a origem do código:

**`FormRequest` (`app/Http/Requests/`)** — usado na gestão de utilizadores, no perfil e no login. Regras em **sintaxe de array**, com `authorize()`, `rules()` e `messages()` em português:
```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', 'confirmed', 'min:8'],
    ];
}
```

**`$request->validate()` no controller** — usado em todo o resto (obras, fases, fotos, despesas, documentos, pagamentos, definições, relatórios). Regras em **string com barras verticais**:
```php
$data = $request->validate([
    'name' => 'required|string|max:200',
    'status' => 'required|in:'.implode(',', array_keys(Project::STATUSES)),
    'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
]);
```

**Regras recorrentes:**
- Conjuntos fechados derivados das constantes: `'in:'.implode(',', array_keys(Expense::CATEGORIES))`
- Uploads sempre com `mimes:` e `max:` explícitos — recibos `pdf,jpg,jpeg,png,webp|max:10240`, documentos `pdf,docx,xlsx,xls,doc,jpg,jpeg,png,webp|max:51200`, fotos e vídeos `jpeg,jpg,png,webp,gif,mp4,mov,avi|max:51200`
- Filhos validados contra o pai na própria regra: `'phase_id' => 'nullable|exists:project_phases,id,project_id,'.$project->id`

## Tratamento de erros

**Estratégia:**
- Configurado em `bootstrap/app.php` com `shouldRenderJsonWhen(fn ($r) => $r->is('api/*'))`
- Não existem classes de excepção próprias
- As excepções HTTP do framework tratam 401/403/404/422/500

**Padrões:**
- `abort(403)` no `EnsureUserRole`
- `abort_unless(...)` / `abort_if(...)` nos controllers, com o código explícito: 403 para acesso indevido, 404 para recursos de outra obra, para tipos de relatório desconhecidos e para media sem ficheiro em disco
- `ValidationException::withMessages()` no `LoginRequest`, com mensagens em português
- Falhas que não devem partir o pedido são apanhadas e registadas: `Thumbnail::generate()` devolve `null` e regista um aviso; o `ClientLayoutComposer` apanha `Throwable` em torno de cada leitura e escrita de cache

## Logging

- Sistema de logging do Laravel (Monolog), via `Illuminate\Support\Facades\Log`
- Leitura em tempo real durante o desenvolvimento: `php artisan pail`
- Usado com parcimónia e sempre com contexto: `Log::warning('Thumbnail generation failed', ['path' => ..., 'error' => ...])`
- Dados de pagamento nunca chegam ao trilho de auditoria (`AuditObserver::REDACTED`)

## Middleware

**Padrão:**
- Em `app/Http/Middleware/`, com `handle(Request $request, Closure $next): Response`
- Registo em `bootstrap/app.php`: `EnsureUserIsActive` e `SecurityHeaders` são anexados ao grupo `web`; `ExplainTruncatedUpload` ocupa a vaga do middleware de CSRF nesse grupo; `EnsureUserRole` fica com o alias `role`
- Aplicação nas rotas por `middleware(['auth', 'role:admin,manager'])`

## Componentes Blade

**Padrão:**
- Ficheiros em `resources/views/components/`, em kebab-case
- `@props` para declarar atributos esperados
- `{{ $attributes->merge([...]) }}` para deixar o chamador acrescentar classes Tailwind
- Conteúdo por `{{ $slot }}`

```blade
@props(['disabled' => false])
<input @disabled($disabled) {{ $attributes->merge(['class' => '...']) }}>
```

- Os layouts têm classe em `app/View/Components/` e são usados como `<x-admin-layout>`, `<x-client-layout>`, `<x-guest-layout>`

## Idioma e localização

**Dualidade deliberada:**
- **Identificadores PHP** (classes, métodos, variáveis): inglês — `Project`, `storeUpdate()`, `$manager`
- **URLs visíveis ao utilizador:** português — `/obras`, `/clientes`, `/relatorios`, `/auditoria`
- **Nomes de rota:** inglês — `admin.projects.show`
- **Texto de interface, mensagens flash e de validação:** português, escrito directamente nas vistas e nos controllers
- **Etiquetas de domínio:** constantes em português dentro das classes — `Project::STATUSES`, `AuditLog::LABELS`, `ReportController::TYPES`

**Ficheiros de tradução:**
- `lang/pt_BR.json` (framework e autenticação) e `lang/pt_BR/validation.php` (validação)
- As strings das vistas **não** passam por `__()`; estão escritas literalmente no Blade
- `APP_LOCALE` está a `pt_BR` no `.env.example` e é o valor por omissão em `config/app.php` — até 24/08 estava a `en` e os arquivos de tradução nunca eram lidos
- **Variante fixada em português do Brasil.** A oscilação pt-BR/pt-PT foi resolvida; sobra um único termo pt-PT em texto visível, «ficheiro», em `resources/views/components/model-viewer.blade.php`
- **O vocabulário segue o tipo do registro:** «obra» para construção e remodelação, «projeto» para arquitetura e design de interiores (`Project::noun()`, `nounWithArticle()`, `typeLabel()`). Onde o texto nomeia o conjunto — o menu, o título da listagem — o nome é fixo, «Obras e Projetos»

## Base de dados e consultas

**Query builder:**
- Interface fluente com `when()` para condicionais: `Project::query()->when($isManager, fn ($q) => $q->where('manager_id', auth()->id()))`
- Filtros de pesquisa condicionados por `$request->filled(...)`
- Paginação com `->paginate(n)->withQueryString()` — 20 em obras, clientes e relatórios, 50 na auditoria; `UserController` usa `paginate(15)` sem `withQueryString()`
- Agregações em SQL, não em memória: `selectRaw()` no dashboard e em `Project::expensesByCategory()` / `expensesByMonth()`
- `SUBSTR(spent_at, 1, 7)` em vez de `DATE_FORMAT`/`strftime`, para o mesmo SQL funcionar em MySQL e SQLite
- `(clone $statsQuery)` para reutilizar uma base de consulta sem a contaminar

## Padrões específicos

**Controlo de acesso por papel:**
- Constantes `User::ROLE_ADMIN|ROLE_MANAGER|ROLE_FINANCIAL|ROLE_CLIENT` e os métodos `isAdmin()`, `isManager()`, `isFinancial()`, `isClient()`
- Três níveis: middleware `role:` na rota, Gates (`access-admin`, `manage-users`, `manage-projects`, `access-financial`) e o trait `AuthorizesProjectAccess` ao nível da obra
- O trait restringe **apenas** o `manager`. O financeiro lê todas as obras e escreve despesas e cobranças em qualquer uma; o âmbito dele vem dos grupos `role:`, não de uma coluna por obra

**Definições:**
- Lidas por `Setting::get('chave', 'omissão')`, com cache `rememberForever`; escritas por `Setting::set()`, que invalida a chave
- Chaves em uso: `company_name`, `company_email`, `company_phone`, `allow_comments`, `allow_client_uploads`, `show_client_financials`, `currency`, `progress_mode`, `logo_url` e `notify_{tipo}` por cada tipo de `ProjectUpdate::TYPES`

**Formatação de dinheiro:**
- Nunca `number_format()` directo nas vistas — usa-se sempre `money($valor)`, que resolve o símbolo a partir da definição `currency`

**Limitação de taxa:**
- Login com bloqueio ao fim de 5 tentativas por `email|IP` (`LoginRequest`)
- `throttle:20,1` no envio de mensagens do cliente, `throttle:60,1` em marcar como lidas, `throttle:10,1`/`throttle:30,1` nas subscrições push, `throttle:6,1` nas rotas de verificação de e-mail
- **Todas as escritas da área interna têm tecto desde 24/08:** `throttle:120,1` nas escritas leves (uma linha por requisição), `throttle:30,1` no envio de documentos e de versões de planta, `throttle:20,1` nos envios de fotos

**Gestão de sessão:**
- Sessões em base de dados; invalidação manual com `DB::table('sessions')->where('user_id', $user->id)->delete()` ao desactivar um utilizador

---

*Análise de convenções: 2026-08-24*
