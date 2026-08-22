# Arquitectura

**Data da análise:** 2026-08-22

## Visão do padrão

**Geral:** MVC em camadas, com controlo de acesso por papel e broadcasting de eventos em tempo real

**Características principais:**
- Renderização no servidor (Blade) — não existe SPA nem camada de API (`bootstrap/app.php` só devolve JSON para `api/*`, e não há rotas `api/*`)
- Quatro papéis (`admin`, `manager`, `financial`, `client`), aplicados por middleware, por Gates e pelo trait `AuthorizesProjectAccess`
- Isolamento por cliente: o portal só devolve obras cujo `client_id` coincide com o do utilizador autenticado
- Mensagens em tempo real por canais privados de broadcasting (`project.{projectId}`)
- Notificações accionadas por observer (`ProjectUpdateObserver` → `ProjectUpdateNotification` → canais WhatsApp, WebPush e `mail`)
- Trilho de auditoria genérico (`AuditObserver` sobre nove modelos → tabela `audit_logs`)
- Soft deletes em `User`, `Project`, `Client`, `Photo`, `Document`, `Expense`
- Media servida exclusivamente do disco privado `local`, através de rotas autenticadas

## Camadas

**Camada HTTP:**
- Objectivo: recebe pedidos, encaminha para controllers, aplica middleware
- Localização: `routes/web.php`, `routes/auth.php`, `bootstrap/app.php`
- Contém: definições de rota, alias de middleware (`role`), grupo `web` com `EnsureUserIsActive` e `SecurityHeaders`, configuração de excepções, registo do comando `MigratePublicProjectMedia`
- Depende de: middleware, controllers, modelos

**Camada de middleware:**
- Localização: `app/Http/Middleware/`
- Contém: `EnsureUserRole.php` (403 quando o papel não está na lista), `EnsureUserIsActive.php` (termina a sessão de utilizadores desactivados), `SecurityHeaders.php` (nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy, X-Permitted-Cross-Domain-Policies e HSTS só sobre HTTPS)

**Autorização e traits:**
- Localização: `app/Traits/AuthorizesProjectAccess.php`, `app/Policies/UserPolicy.php`
- `authorizeProjectAccess()` restringe `manager` e `financial` às obras onde são `manager_id`
- `assertBelongsToProject()` devolve 404 quando o filho de uma rota aninhada não pertence à obra do URL
- `Admin\MessageController` tem uma verificação própria e privada que só cobre o papel `manager`

**Camada de controllers:**
- Localização: `app/Http/Controllers/`
- `Admin/` (12): `DashboardController`, `ProjectController`, `ProjectPhaseController`, `ProjectPhotoController`, `ProjectExpenseController`, `ProjectDocumentController`, `PaymentController`, `ClientController`, `MessageController`, `SettingController`, `ReportController`, `AuditLogController`
- `Auth/` (8): sessão, confirmação de palavra-passe, verificação de e-mail, reposição e alteração de palavra-passe
- `Client/` (2): `DashboardController`, `ProjectController`
- `Portal/` (1): `PushSubscriptionController`
- Raiz: `Controller.php` (base abstracta), `ProfileController.php`, `UserController.php`
- O `Admin\ProjectController` ficou com o CRUD da obra, a galeria filtrada (`filteredPhotos()`) e `storeUpdate()`; fases, fotos, despesas e documentos foram extraídos para controllers próprios

**Camada de modelos (Eloquent):**
- Localização: `app/Models/` — 13 modelos: `User`, `Client`, `Project`, `ProjectPhase`, `Photo`, `Expense`, `Document`, `ProjectUpdate`, `Message`, `Payment`, `Setting`, `PushSubscription`, `AuditLog`
- `Project` concentra a lógica de negócio: `recalcProgress()`, `progressIsManual()`, `totalSpent()`, `remainingBalance()`, `budgetUsedPercent()`, `expensesByCategory()`, `expensesByMonth()`
- `ProjectPhase::booted()` chama `recalcProgress()` da obra em `saved` e `deleted`

**Suporte (funções e utilitários):**
- Localização: `app/Support/`
- `helpers.php` — carregado por `autoload.files`; define `currency_symbol()` e `money()`, que leem a definição `currency` (BRL, USD, EUR, GBP, AOA, MZN) e formatam com agrupamento de dígitos pt-BR
- `Thumbnail.php` — geração de miniaturas em GD puro, aresta máxima 480 px, JPEG a qualidade 78, escrita em `<dir>/thumbs/<nome>.jpg` no mesmo disco; falha em silêncio (`Log::warning`) para nunca partir um upload

**Eventos e notificações:**
- `app/Events/MessageSent.php` (`ShouldBroadcast`)
- `app/Notifications/ProjectUpdateNotification.php` (`ShouldQueue`) — `via()` devolve `[WhatsAppChannel, WebPushChannel]` e acrescenta `mail` quando o destinatário tem um e-mail válido
- `app/Observers/ProjectUpdateObserver.php` — em `ProjectUpdate::created`, verifica a definição `notify_{tipo}` e notifica os utilizadores do cliente, excepto quem originou a acção
- `app/Observers/AuditObserver.php` — genérico; grava `created`, `updated`, `deleted` e `restored`, redige credenciais e dados de pagamento (`password`, `remember_token`, `two_factor_*`, `pix_key`, `pix_qrcode`, `barcode`) e ignora `created_at`/`updated_at`

**Canais de broadcasting:**
- `routes/channels.php` — `project.{projectId}` autorizado a `admin` e `financial` (qualquer obra), a `manager` (obras suas) e a `client` (obras do seu cliente); `App.Models.User.{id}` para o próprio

**Canais de notificação:**
- `app/Channels/WhatsAppChannel.php` — Meta Graph API via Guzzle
- `app/Channels/WebPushChannel.php` — usa `minishlink/web-push`; sai cedo se faltarem as chaves VAPID ou se o utilizador não tiver subscrições

**Camada de vistas:**
- `resources/views/` — 59 ficheiros Blade, organizados por papel (`admin/`, `client/`, `auth/`, `profile/`, `users/`) mais `components/`
- `admin/reports/` tem `index`, `show`, `pdf` e os parciais `geral`, `financeiro`, `fotos`, `fases`, `fases-table`
- `admin/audit/index.blade.php` é a página `/admin/auditoria`

**Componentes e view composers:**
- `app/View/Components/` — `AdminLayout` → `components.layouts.admin`, `ClientLayout` → `components.layouts.client`, `GuestLayout` → `components.layouts.guest`, `AppLayout` → `layouts.app`
- `app/Http/View/Composers/ClientLayoutComposer.php` — ligado a `components.layouts.client` no `AppServiceProvider`

**Service provider:**
- `app/Providers/AppServiceProvider.php` — regista `ProjectUpdateObserver` em `ProjectUpdate`, regista `AuditObserver` em `Project`, `Client`, `User`, `Expense`, `Payment`, `Photo`, `Document`, `ProjectPhase` e `Setting`, liga o view composer e define os Gates `access-admin`, `manage-users` e `access-financial`
- `ProjectUpdate` está deliberadamente fora da auditoria — é conteúdo visível ao cliente, já presente no histórico da obra

**Base de dados e seeding:**
- `database/migrations/` — 18 migrações (4 do framework, 14 de domínio, a última `2026_08_21_100000_create_audit_logs_table.php`)
- `database/factories/` — 8 factories: `User`, `Client`, `Project`, `ProjectPhase`, `Photo`, `Expense`, `Document`, `Payment`
- `database/seeders/DatabaseSeeder.php` — cria o administrador inicial e as definições base; lança excepção se `SEED_ADMIN_PASSWORD` faltar em produção

**Consola:**
- `app/Console/Commands/MigratePublicProjectMedia.php` — `obra360:media:migrate-public`, migra capas, fotos, miniaturas e documentos do disco `public` para o `local`; dry-run por omissão, `--commit` copia, `--delete-source` (que exige `--commit`) apaga a origem depois de verificar a cópia
- `app/Console/Commands/GeneratePhotoThumbnails.php` — `obra360:photos:thumbnails`, gera miniaturas em falta (`--force` regenera as existentes), ignorando vídeos
- `routes/console.php` — comando `inspire` de exemplo

## Fluxos de dados

**Criação de obra (admin/gestor):**

1. `GET /admin/obras/nova` → `Admin\ProjectController@create` carrega clientes e gestores
2. `POST /admin/obras` → `@store` valida os dados e a capa (`image|mimes:jpg,jpeg,png,webp|max:4096`)
3. A capa é guardada em `covers/` no disco `local`; `progress` é fixado a 0
4. Se quem cria for `manager`, `manager_id` é forçado ao próprio
5. `Project::create()` e `ProjectUpdate::log($id, 'Obra criada', 'status')`
6. O `AuditObserver` grava a criação em `audit_logs`; o `ProjectUpdateObserver` notifica os utilizadores do cliente
7. Redirecção para `admin.projects.show`

**Upload de fotos (admin/gestor):**

1. `POST /admin/obras/{project}/fotos` → `Admin\ProjectPhotoController@store`
2. Validação: `photos` é um array com pelo menos um ficheiro `jpeg,jpg,png,webp,gif,mp4,mov,avi`, no máximo 51200 KB cada
3. Cada ficheiro vai para `projects/{id}/photos` no disco `local`
4. Se não for vídeo, `Thumbnail::generate()` escreve `projects/{id}/photos/thumbs/<nome>.jpg` e o caminho é guardado em `thumbnail_path`
5. `ProjectUpdate::log($id, 'Fotos adicionadas', 'foto')` dispara a notificação

**Leitura de media:**

1. `GET /admin/obras/{project}/fotos/{photo}` → `Admin\ProjectPhotoController@show` (ou o equivalente do portal, `Client\ProjectController@photo`)
2. Autorização, depois `assertBelongsToProject`
3. Com `?thumb=1`, devolve a miniatura se existir; caso contrário o original
4. `mediaResponse()` faz `abort_unless(Storage::disk('local')->exists($path), 404)` e só depois `Storage::disk('local')->response($path)` — não há fallback para o disco `public`, e um registo sem ficheiro é 404, não 500

**Acesso ao portal do cliente:**

1. Depois do login, o cliente é encaminhado para `portal.dashboard`
2. `Client\DashboardController@index` carrega `Project::where('client_id', $user->client_id)` com `latestImage` e calcula `cover_url`
3. `Client\ProjectController@show` aborta com 403 se `client_id` não corresponder; carrega fases, mensagens (limite 100), fotos (limite 200), documentos com `visibility = cliente` e, se `show_financials` da obra e a definição `show_client_financials` o permitirem, despesas e pagamentos

**Mensagens em tempo real:**

1. `POST /portal/obras/{project}/mensagens` (limitado a `throttle:20,1`) → `Client\ProjectController@storeMessage`
2. A definição `allow_comments` tem de estar a `1`, senão 403
3. `Message::create()` seguido de `broadcast(new MessageSent($msg))->toOthers()`
4. `MessageSent` transmite no `PrivateChannel("project.{id}")`; o Echo/Reverb entrega aos subscritores autorizados por `routes/channels.php`

**Relatórios e PDF:**

1. `GET /admin/relatorios` → `ReportController@index` lista as obras (restritas a `manager_id` para `manager` e `financial`) com pesquisa por nome
2. `GET /admin/obras/{project}/relatorios/{type}` → `@show`; `{type}` tem de estar em `ReportController::TYPES` (`geral`, `financeiro`, `fotos`, `fases`), senão 404
3. `@data()` agrega o que cada tipo precisa: fases, `totalSpent()`, `expensesByCategory()`, `expensesByMonth()`, despesas, pagamentos, fotos e as últimas 20 actualizações
4. `GET .../pdf` → `@pdf` gera o documento com DomPDF em A4, com recursos remotos desligados, e devolve `relatorio-{tipo}-{slug}-{data}.pdf`
5. As imagens são embebidas como data URI por `ReportController::embed()`, porque o PDF não consegue autenticar-se nas rotas de media; o limite é 24 fotos (`PDF_PHOTO_LIMIT`)

**Progresso da obra (FASE-04):**

1. A definição `progress_mode` vale `derivado` (omissão) ou `manual`
2. Em modo derivado, `ProjectPhase::booted()` chama `Project::recalcProgress()` sempre que uma fase é guardada ou removida, e o progresso passa a ser a média arredondada de `percentage`
3. Em modo manual, `recalcProgress()` sai imediatamente e `Admin\ProjectController@update` valida e grava o campo `progress` submetido no formulário

**Auditoria:**

1. Qualquer escrita nos nove modelos observados dispara `AuditObserver`
2. `updated` só grava quando `getChanges()` (já redigido) não fica vazio, e guarda `['de' => ..., 'para' => ...]`
3. Cada registo fica com `user_id`, `user_name` (preservado para sobreviver à remoção do utilizador), `action`, `auditable_type`, `auditable_id`, `description`, `changes` (JSON) e `ip_address`
4. `GET /admin/auditoria` (só `admin`) → `AuditLogController@index` filtra por acção, tipo e utilizador, 50 por página

**Estado de sessão:**

- Sessões na base de dados (tabela `sessions`), cifradas (`SESSION_ENCRYPT=true` no `.env.example`)
- `messages_last_viewed_at` é guardado na sessão pela rota `portal.messages.mark-read`
- Não há framework de estado no cliente

## Abstracções principais

**Modelos Eloquent:** padrão Active Record; atributos PHP 8 `#[Fillable]` e `#[Hidden]` em vez de arrays de propriedades; `protected function casts(): array` explícito

**Service providers:** `AppServiceProvider` com `register()` e `boot()`

**Migrações:** classe anónima que estende `Migration`, com `up()` e `down()`

**Traits:** `AuthorizesProjectAccess` — partilhado por `Admin\ProjectController`, `ProjectPhaseController`, `ProjectPhotoController`, `ProjectExpenseController`, `ProjectDocumentController`, `PaymentController` e `ReportController`

**Componentes de vista:** classes em `app/View/Components/` que devolvem um layout Blade

**View composers:** `ClientLayoutComposer::compose(View $view)`, registado por `View::composer()`

**Observers:** `ProjectUpdateObserver` (específico) e `AuditObserver` (genérico, um por nove modelos)

**Eventos:** `MessageSent` com `broadcastOn()`

**Notificações:** `ProjectUpdateNotification` com `via()`, `toMail()`, `toWhatsApp()` e `toWebPush()`

**Canais personalizados:** classes com um método `send(mixed $notifiable, Notification $notification)`

**Funções globais:** `money()` e `currency_symbol()` em `app/Support/helpers.php`, usadas em `admin/dashboard`, `admin/projects/index`, `admin/projects/show`, `client/dashboard`, `client/projects/show` e nos parciais de relatório

## Pontos de entrada

**Pedido web:** `public/index.php` — carrega o autoloader, cria a aplicação a partir de `bootstrap/app.php` e despacha o pedido

**CLI:** `artisan` — carrega o autoloader, arranca a aplicação e despacha o comando

**Health check:** `GET /up`, declarado em `bootstrap/app.php`

## Tratamento de erros

**Estratégia:** vistas de erro HTML; JSON apenas para `api/*` (`shouldRenderJsonWhen` em `bootstrap/app.php`)

**Padrões:**
- `abort(403)` no middleware e no trait de autorização
- `abort_unless(..., 404)` para filhos que não pertencem à obra e para media sem ficheiro
- `$request->validate()` lança `ValidationException` e devolve o formulário com erros
- `ValidationException::withMessages()` no `LoginRequest`, com mensagens em português
- Não existem classes de excepção próprias

## Preocupações transversais

**Logging:** Monolog através do Laravel; `Log::warning` em `Thumbnail`, `Log::info` no `WebPushChannel`

**Validação:** `$request->validate()` nos controllers; `FormRequest` em `app/Http/Requests/` para utilizadores, perfil e login

**Autenticação:** Laravel Breeze, sessão em base de dados; registo público desactivado

**Autorização:** middleware `role:`, Gates (`access-admin`, `manage-users`, `access-financial`), `UserPolicy` e o trait `AuthorizesProjectAccess`

**Tempo real:** Reverb + Laravel Echo; canais privados por obra

**Filas:** `ShouldQueue` em `ProjectUpdateNotification`; conector `database`; worker via `php artisan queue:work`

**Cache:** `Setting::get()` usa `rememberForever("setting:{$key}")`, invalidado por `Setting::set()`; o `ClientLayoutComposer` usa janelas de 30 s (mensagens não lidas e contagem), 60 s (actualizações) e 120 s (primeira obra), e **só guarda escalares e arrays de escalares** — nunca modelos Eloquent

**Auditoria:** `AuditObserver` sobre nove modelos, com redacção de credenciais e de dados de pagamento

---

*Análise de arquitectura: 2026-08-22*
