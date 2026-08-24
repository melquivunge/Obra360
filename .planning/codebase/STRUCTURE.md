# Estrutura do Código

**Data da análise:** 2026-08-24

## Mapa de directórios

```
Obra360/
├── app/                          # Código da aplicação (PHP)
│   ├── Channels/                 # Canais de notificação (WhatsApp, WebPush)
│   ├── Console/
│   │   └── Commands/             # Comandos artisan (4)
│   ├── Events/                   # Eventos de broadcasting
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/            # Área interna (15 controllers)
│   │   │   ├── Auth/             # Autenticação (8 controllers)
│   │   │   ├── Client/           # Portal do cliente (4 controllers)
│   │   │   └── Portal/           # Subscrições push (1 controller)
│   │   ├── Middleware/           # EnsureUserRole, EnsureUserIsActive, SecurityHeaders, ExplainTruncatedUpload
│   │   ├── Requests/             # Classes FormRequest
│   │   └── View/
│   │       └── Composers/        # ClientLayoutComposer
│   ├── Models/                   # Modelos Eloquent (17)
│   ├── Notifications/            # ProjectUpdateNotification
│   ├── Observers/                # ProjectUpdateObserver, AuditObserver, MediaCleanupObserver
│   ├── Policies/                 # UserPolicy
│   ├── Providers/                # AppServiceProvider
│   ├── Support/                  # helpers.php (money), Thumbnail.php (GD), PhaseTemplates.php
│   ├── Traits/                   # AuthorizesProjectAccess
│   └── View/
│       └── Components/           # Componentes de layout
│
├── bootstrap/
│   ├── app.php                   # Configuração da aplicação
│   └── providers.php             # Lista de service providers
│
├── config/                       # Configuração (app, auth, database, filesystems, services, ...)
│
├── database/
│   ├── factories/                # 12 factories
│   ├── migrations/               # 26 migrações
│   └── seeders/                  # DatabaseSeeder
│
├── docker/
│   ├── nginx/                    # default.conf
│   └── php/                      # local.ini
│
├── docs/                         # production-readiness.md, registo-2026-08-21.md
│
├── lang/
│   ├── pt_BR.json                # Traduções do framework e de autenticação
│   └── pt_BR/validation.php      # Mensagens de validação (APP_LOCALE=pt_BR)
│
├── public/
│   ├── build/assets/             # Assets compilados pelo Vite
│   └── index.php                 # Ponto de entrada HTTP
│
├── resources/
│   ├── css/app.css               # Entrada Tailwind v4 + tokens de tema
│   ├── js/app.js                 # Entrada JS (Alpine, Echo/Reverb, sidebar, flash)
│   └── views/                    # 70 templates Blade
│       ├── admin/
│       │   ├── audit/            # Página /admin/auditoria
│       │   ├── clients/          # CRUD de clientes
│       │   ├── projects/         # CRUD de obras
│       │   ├── reports/          # index, show, pdf + partials/
│       │   ├── settings/         # Formulário de definições
│       │   ├── dashboard.blade.php
│       │   └── messages.blade.php
│       ├── auth/                 # login, forgot-password, reset-password, verify-email, confirm-password
│       ├── client/
│       │   ├── projects/         # Detalhe da obra
│       │   ├── messages/         # Fio de mensagens
│       │   └── dashboard.blade.php
│       ├── components/           # 26 componentes + layouts/
│       │   └── layouts/          # admin, client, guest
│       ├── errors/               # 413.blade.php (envio acima do limite)
│       ├── layouts/              # app, guest, navigation (legado do Breeze)
│       ├── profile/              # edit + partials/
│       └── users/                # Gestão de utilizadores
│
├── routes/
│   ├── web.php                   # Rotas web agrupadas por papel
│   ├── auth.php                  # Rotas de autenticação (sem registo público)
│   └── channels.php              # Autorização de canais de broadcasting
│
├── storage/
│   ├── app/
│   │   ├── private/              # Disco `local` — toda a media de obras
│   │   └── public/               # Disco `public` — logótipo da empresa
│   ├── framework/
│   └── logs/
│
├── tests/
│   ├── Feature/                  # 55 arquivos (9 em Auth/)
│   ├── Unit/                     # 2 arquivos
│   └── TestCase.php
│
├── .planning/codebase/           # Documentos de análise (gerados)
├── .planning/phases/             # Documentos de fase
├── .github/workflows/laravel.yml # CI/CD
├── .github/dependabot.yml        # Actualizações automáticas de dependências
├── docker-compose.yml
├── Dockerfile
├── vite.config.js
├── artisan
├── composer.json / composer.lock
├── package.json / package-lock.json
└── phpunit.xml
```

## Propósito dos directórios

**app/Models/:**
- Entidades Eloquent — 17 modelos: `User`, `Client`, `Project`, `ProjectPhase`, `Photo`, `Expense`, `Document`, `ProjectUpdate`, `Message`, `Payment`, `Setting`, `PushSubscription`, `AuditLog`, `Plan`, `PlanVersion`, `PlanComment`, `BudgetItem`
- `Project.php` concentra o cálculo de progresso e as agregações financeiras
- `AuditLog.php` traz `ACTIONS` e `LABELS` (mapa de classe → etiqueta em português) e `UPDATED_AT = null` (a tabela só tem `created_at`)
- Configuração de campos por atributos PHP 8 (`#[Fillable]`, `#[Hidden]`)

**app/Http/Controllers/:**
- `Admin/` (15) — os doze anteriores mais `ProjectPlanController`, `ProjectPlanVersionController` e `ProjectBudgetItemController`
- `Auth/` (8) — sessão, confirmação de palavra-passe, verificação de e-mail (notificação e prompt), nova palavra-passe, alteração de palavra-passe, link de reposição, verificação
- `Client/` (4) — `DashboardController`, `ProjectController`, `PlanController` (baixar, pré-visualizar, aprovar e comentar plantas), `UploadController` (fotos e documentos enviados pelo cliente)
- `Portal/` (1) — `PushSubscriptionController`
- Raiz — `Controller.php`, `ProfileController.php`, `UserController.php`, `CspReportController.php`

**app/Http/Middleware/:**
- `EnsureUserRole.php` — `abort(403)` quando o papel não consta da lista da rota
- `EnsureUserIsActive.php` — termina a sessão de utilizadores desactivados
- `SecurityHeaders.php` — cabeçalhos de segurança em todo o grupo `web`, incluindo a Content-Security-Policy com nonce por requisição
- `ExplainTruncatedUpload.php` — ocupa a vaga do middleware de CSRF no grupo `web`; um envio truncado sem `Content-Length` chega sem token, e sem isto a resposta seria «419 — página expirada»

**app/Http/Requests/:**
- `StoreUserRequest.php`, `UpdateUserRequest.php`, `ProfileUpdateRequest.php`, `Auth/LoginRequest.php`

**app/Http/View/Composers/:**
- `ClientLayoutComposer.php` — mensagens não lidas, contagem para o badge, actualizações recentes e primeira obra do cliente. Guarda apenas escalares e arrays de escalares em cache e valida a forma do que lê

**app/View/Components/:**
- `AdminLayout.php`, `ClientLayout.php`, `GuestLayout.php` (apontam para `components/layouts/*`) e `AppLayout.php` (aponta para `layouts/app`)

**app/Events/:**
- `MessageSent.php` — `ShouldBroadcast`, canal privado `project.{id}`, evento `message.sent`

**app/Notifications/:**
- `ProjectUpdateNotification.php` — em fila; WhatsApp, WebPush e, quando há e-mail válido, `mail`

**app/Channels/:**
- `WhatsAppChannel.php`, `WebPushChannel.php`

**app/Observers/:**
- `ProjectUpdateObserver.php` — notificações por tipo de actualização, com interruptor por definição `notify_{tipo}`
- `AuditObserver.php` — trilho de auditoria genérico com redacção de campos sensíveis, sobre onze modelos
- `MediaCleanupObserver.php` — apaga os arquivos do disco em `forceDeleting` de `Project` e `Plan`, quando a exclusão passa a definitiva

**app/Policies/:**
- `UserPolicy.php`

**app/Support/:**
- `helpers.php` — `currency_symbol()` e `money()`, carregados por `autoload.files` do `composer.json`
- `Thumbnail.php` — miniaturas em GD puro (`MAX_EDGE = 480`, qualidade 78), gravadas em `thumbs/` ao lado do original
- `PhaseTemplates.php` — etapas de entrega pré-definidas por tipo de projeto; as de arquitetura seguem a NBR 13532

**app/Traits/:**
- `AuthorizesProjectAccess.php` — `authorizeProjectAccess()` (restringe só o `manager`), `authorizePlanReadAccess()` (abre ao gestor o projeto de origem da obra dele) e `assertBelongsToProject()`

**app/Providers/:**
- `AppServiceProvider.php` — observers, view composer e Gates

**app/Console/Commands/:**
- `MigratePublicProjectMedia.php` — `obra360:media:migrate-public`
- `GeneratePhotoThumbnails.php` — `obra360:photos:thumbnails`
- `PruneAuditLogs.php` — `obra360:audit:prune` (retenção de 365 dias)
- `PruneTrashedProjects.php` — `obra360:trash:prune` (reciclagem de 90 dias, apaga os arquivos)

**database/migrations/:**
- 26 migrações: 3 do framework (`0001_*`), domínio em `2026_06_27_*`, `2026_08_21_100000_create_audit_logs_table` e o bloco dos serviços de projeto — `2026_08_23_100000_add_type_to_projects_table`, `2026_08_23_200001_create_plans_table`, `2026_08_23_200002_create_plan_versions_table`, `2026_08_23_300001_add_source_project_id_to_projects_table`, `2026_08_24_100000_drop_external_url_from_photos_table`, `2026_08_24_200000_add_revisions_included_to_projects_table`, `2026_08_24_210000_create_plan_comments_table` e `2026_08_24_300000_create_budget_items_table`

**database/factories/:**
- `UserFactory` (com estados `admin()`, `manager()`, `financial()`, `unverified()`), `ClientFactory`, `ProjectFactory` (com estado `architecture()`), `ProjectPhaseFactory`, `PhotoFactory`, `ExpenseFactory`, `DocumentFactory`, `PaymentFactory`, `PlanFactory`, `PlanVersionFactory`, `PlanCommentFactory`, `BudgetItemFactory`

**database/seeders/:**
- `DatabaseSeeder.php` — administrador inicial e definições base; exige `SEED_ADMIN_PASSWORD` em produção

**resources/views/**

- **admin/** — `dashboard.blade.php` (KPIs, obras atrasadas e quase concluídas), `messages.blade.php`, `projects/` (index, create, edit, show), `clients/` (index, create, edit), `settings/index.blade.php`, `audit/index.blade.php`, `reports/` (`index`, `show`, `pdf` e `partials/geral|financeiro|fotos|fases|fases-table`)
- **client/** — `dashboard.blade.php`, `projects/show.blade.php`, `messages/index.blade.php`
- **auth/** — `login`, `forgot-password`, `reset-password`, `verify-email`, `confirm-password`. Não existe `register.blade.php`
- **profile/** — `edit.blade.php` e `partials/` (informação, palavra-passe, remoção de conta)
- **components/layouts/** — `admin.blade.php`, `client.blade.php`, `guest.blade.php`
- **layouts/** — `app.blade.php`, `guest.blade.php`, `navigation.blade.php` (herdados do Breeze)
- **components/** (26 arquivos, mais `layouts/`) — os anteriores mais `form-errors` e `field-error` (erros de validação em toda a área interna), `project-cover` (capa com imagem padrão), `project-type`, `plan-comments`, `revision-counter`, `budget-plan`, `model-viewer` (visualizador 3D) e `password-input`
- **users/** — `index`, `create`, `edit`

**routes/web.php:**
- Grupos de middleware:
  - `role:admin,manager,financial` + prefixo `admin` — dashboard, relatórios, leitura de obras, capa e fotos, mensagens
  - `role:admin` + prefixo `admin` — auditoria, clientes, definições
  - `role:admin,manager` + prefixo `admin` — CRUD de obras, fases, fotos, documentos, plantas e versões, actualizações manuais
  - `role:admin,financial` + prefixo `admin` — despesas e pagamentos
  - `role:admin` — recurso `usuarios` (nomes `users.*`) e `users.toggle-active`
  - `role:client` + prefixo `portal` — dashboard, obra, capa, fotos, mensagens, download de documentos, plantas (baixar, pré-visualizar, aprovar, comentar), envio de fotos e documentos, subscrições push
  - `auth` — perfil
- URLs em português, nomes de rota em inglês
- Todas as escritas têm `throttle`: `120,1` nas leves, `30,1` no envio de documentos e plantas, `20,1` nos envios de fotos

**routes/channels.php:**
- `project.{projectId}` e `App.Models.User.{id}`

**routes/auth.php:**
- Login, logout, reposição e alteração de palavra-passe, verificação de e-mail, confirmação de palavra-passe. **Sem rota de registo**

**lang/:**
- `pt_BR.json` — mensagens do framework e de autenticação
- `pt_BR/validation.php` — mensagens de validação
- `config/app.php` tem `'locale' => env('APP_LOCALE', 'pt_BR')` e `.env.example` tem `APP_LOCALE=pt_BR`: até 24/08 a aplicação corria em `en` e nada disto era lido

**storage/app/private/:**
- Raiz do disco `local` (`config/filesystems.php`), com `serve => true`
- Contém `covers/`, `projects/{id}/photos/`, `projects/{id}/photos/thumbs/`, `projects/{id}/receipts/`, `projects/{id}/documents/` e `projects/{id}/plans/{plan}/`

**storage/app/public/:**
- Raiz do disco `public`, exposto por `/storage`
- Guarda apenas a logomarca da empresa (`branding/`, escrita por `Admin\SettingController@update`). A mídia legada foi migrada em 22/08 e as chamadas defensivas ao disco `public` nas remoções foram retiradas em 24/08

**tests/Feature/ e tests/Unit/:**
- 57 arquivos de teste (55 em `Feature/`, 9 dos quais em `Feature/Auth/`, e 2 em `Unit/`); **540 testes e 1609 asserções, todos passando** (`php artisan test`, 2026-08-24)

## Localizações-chave

**Pontos de entrada:**
- `public/index.php` — HTTP
- `artisan` — CLI
- `routes/web.php` — rotas
- `bootstrap/app.php` — configuração da aplicação

**Configuração:**
- `config/app.php`, `config/database.php`, `config/auth.php`, `config/filesystems.php` (discos `local`, `public`, `s3` — o driver `league/flysystem-aws-s3-v3` ainda não está instalado), `config/services.php` (WhatsApp, VAPID), `config/security.php` (CSP e origem do Reverb)
- `.env` (não versionado; modelo em `.env.example`)
- `vite.config.js`

**Lógica central:**
- `app/Models/Project.php` — progresso e agregações financeiras
- `app/Http/Controllers/Admin/` — escrita na área interna
- `app/Support/` — formatação de moeda e miniaturas
- `app/Providers/AppServiceProvider.php` — observers e Gates

**Testes:**
- `tests/Feature/`, `tests/Unit/`, `phpunit.xml`

## Convenções de nomenclatura

**Ficheiros:**
- Classes em PascalCase, uma por ficheiro
- Migrações com prefixo de data (`2026_08_21_100000_create_audit_logs_table.php`)
- Vistas Blade em snake_case com sufixo `.blade.php`
- Componentes Blade em kebab-case (`text-input.blade.php`)

**Directórios:**
- PascalCase nos namespaces (`Controllers`, `Models`, `Observers`, `Support`)
- snake_case nos directórios de vistas

**Classes:**
- Sufixo pelo papel: `*Controller`, `*Factory`, `*Seeder`, `*Request`, `*Policy`, `*Observer`, `*Provider`, `*Channel`
- Controllers de recurso aninhado com o prefixo do pai: `ProjectPhaseController`, `ProjectPhotoController`, `ProjectExpenseController`, `ProjectDocumentController`

**Métodos:**
- camelCase; declaração de tipo de retorno onde o ficheiro já a usa (ver CONVENTIONS.md para a excepção dos controllers de `Admin/`)

**Nomes de rota:**
- Prefixados pelo grupo (`admin.*`, `portal.*`), recursos aninhados separados por ponto (`admin.projects.photos.store`)

**Tabelas:** snake_case no plural (`projects`, `project_updates`, `push_subscriptions`, `audit_logs`)

**Colunas:** snake_case (`manager_id`, `cover_image_path`, `thumbnail_path`, `auditable_type`)

## Onde acrescentar código novo

**Nova funcionalidade com entidade própria:**

1. **Modelo:** `app/Models/X.php` com `#[Fillable]`, relações e `casts()`; migração em `database/migrations/`
2. **Auditoria:** se a entidade for sensível, acrescentar a classe ao array de `AppServiceProvider::boot()` que regista o `AuditObserver`
3. **Controller:** `app/Http/Controllers/Admin/XController.php`; usar `AuthorizesProjectAccess` quando o recurso pertence a uma obra e chamar `assertBelongsToProject()` nas rotas aninhadas
4. **Rotas:** acrescentar ao grupo de papel adequado em `routes/web.php`, com URL em português e nome em inglês
5. **Validação:** `$request->validate()` no controller (padrão dominante) ou `FormRequest` em `app/Http/Requests/`
6. **Vistas:** `resources/views/admin/x/`, envolvidas em `<x-admin-layout>`
7. **Notificações:** acrescentar o tipo a `ProjectUpdate::TYPES` para ganhar o interruptor `notify_{tipo}` nas definições
8. **Testes:** `tests/Feature/XTest.php` com `RefreshDatabase`

**Componente Blade reutilizável:**
- Ficheiro em `resources/views/components/`, invocado por `<x-nome>`; para componentes com classe, `app/View/Components/`

**Utilitários:**
- Funções globais em `app/Support/helpers.php` (já carregado por `autoload.files`)
- Classes utilitárias em `app/Support/` (ver `Thumbnail`)

**Middleware:**
- `app/Http/Middleware/`, registado em `bootstrap/app.php` por `$middleware->alias()` ou `appendToGroup()`

**Comando artisan:**
- `app/Console/Commands/`, com `$signature` no espaço de nomes `obra360:`

## Directórios especiais

**storage/app/private/:** disco `local`, fora da raiz web; escrito e lido pela aplicação; não versionado

**storage/app/public/:** disco `public`, exposto por `/storage`; hoje só o logótipo é escrito aqui

**storage/framework/:** cache, sessões e vistas compiladas; não versionado

**bootstrap/cache/:** cache de configuração e rotas; não versionado

**public/build/assets/:** saída do Vite; não versionado

**.planning/:** artefactos de planeamento e análise

---

*Análise de estrutura: 2026-08-24*
