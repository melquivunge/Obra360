# External Integrations

**Analysis Date:** 2026-08-20 — *revisto e corrigido em 2026-08-24; as correcções estão em português do Brasil, como o resto de `.planning/codebase/`*

## APIs & External Services

**Meta WhatsApp Business API:**
- Service: WhatsApp Business API (Graph API v18.0)
- What it's used for: Send WhatsApp notifications to users/clients about project updates
- SDK/Client: `guzzlehttp/guzzle` (HTTP client)
- Auth: Environment variables `WHATSAPP_PHONE_ID` and `WHATSAPP_TOKEN`
- Implementation: `app/Channels/WhatsAppChannel.php`
  - Endpoint: `https://graph.facebook.com/v18.0/{$phoneId}/messages`
  - Message type: Text messages with optional preview URL
  - Error handling: Retries on HTTP failures and connection errors; logs warnings with hashed phone numbers
- **Correcção (24/08):** sem credenciais o canal deixou de sair calado. Regista um aviso por notificação não entregue, nomeando qual das duas definições falta e nada mais — nem o token, nem o número do remetente, nem o do destinatário (`tests/Feature/WhatsAppChannelTest.php`)
  - Phone normalization: Requires 10+ digits, stored as digits-only

**Web Push Notifications (VAPID):**
- Service: Browser native Web Push API (no external provider; self-hosted push)
- What it's used for: Send push notifications to PWA users who have subscribed
- SDK/Client: `minishlink/web-push` v8.0.0 — **instalado desde 21/08** (antes o canal era um no-op). O `WebPushChannel` ainda guarda em `class_exists(WebPush::class)`, mas o pacote existe
- Auth: VAPID public/private keys (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`)
- Implementation: `app/Channels/WebPushChannel.php`
  - Generates VAPID keys via: `php artisan tinker` → `[Minishlink\WebPush\VAPID::createVapidKeys()['publicKey'], Minishlink\WebPush\VAPID::createVapidKeys()['privateKey']]`
  - Subscription storage: `push_subscriptions` table via `app/Models/PushSubscription.php`
  - Automatic cleanup: Expired subscriptions are deleted on failed delivery reports

## Data Storage

**Databases:**
- Type: MySQL 8.0 (primary production database)
  - Connection: `DB_CONNECTION=mysql`, `DB_HOST=db`, `DB_DATABASE=obra360`
  - Client: Laravel Eloquent ORM
  - Port: 3306
  - Docker container: `obra360_db`

- Type: SQLite (testing only, via GitHub Actions CI)
  - Database file: `database/database.sqlite`
  - Used by: PHPUnit tests (isolated from production data)

**Tables (Key entities from migrations):**
- `users` - Admin and client user accounts with roles
- `clients` - Client company contacts
- `projects` - Construction projects
- `project_phases` - Project phases (foundation, structure, finishing, etc.)
- `photos` - Project photos with URLs
- `expenses` - Project expenses/costs
- `documents` - Project documents (contracts, invoices, etc.)
- `project_updates` - Timeline updates sent to clients
- `messages` - Client-company communication
- `payments` - Payment records
- `push_subscriptions` - PWA push notification subscriber endpoints
- `jobs` - Queue job records (database queue driver)
- `failed_jobs` - Failed job records (queue retries)
- `sessions` - User session records (database session driver)
- `cache` - Cache entries (database cache driver)
- `audit_logs` — trilho de auditoria, uma linha por escrita nos onze modelos observados (retenção de 365 dias por `obra360:audit:prune`)
- `plans`, `plan_versions`, `plan_comments` — plantas, revisões numeradas e comentários presos a cada versão
- `budget_items` — orçamento por linhas (ADV-04)
- `projects.type`, `projects.source_project_id` e `projects.revisions_included` — colunas acrescentadas pelos serviços de arquitetura e design

**File Storage:**
- Type: Local filesystem (default, no S3 currently deployed)
- Implementation: League Flysystem with local disk driver
- Storage paths:
  - Private: `storage/app/private/` — **toda a mídia das obras e projetos**: `covers/`, `projects/{id}/photos/`, `.../photos/thumbs/`, `.../receipts/`, `.../documents/` e `.../plans/{plan}/`. Servida só por rota autenticada
  - Public: `storage/app/public/` — **apenas a logomarca da empresa** (`branding/`). A mídia legada foi migrada em 22/08 e o fallback de leitura foi removido
- Symlink: `public/storage` → `storage/app/public` (created by `artisan storage:link`)
- Upload limits (PHP config):
  - Max file size: 64MB
  - Max post size: 64MB
- S3 Support: o disco está declarado e o código nunca usa caminhos absolutos, mas `league/flysystem-aws-s3-v3` **não está instalado** — em `composer.lock` aparece só como sugestão do framework. Falta o driver e as credenciais

**Caching:**
- Driver: Database (default)
- Table: `cache` and `cache_locks` (Laravel cache tables)
- Config: `CACHE_STORE=database`
- Alternative: Memcached (config available, not enabled)

## Authentication & Identity

**Auth Provider:**
- Type: Custom with Laravel Breeze scaffolding
- Implementation: Laravel built-in authentication + session-based (no OAuth/SAML)
- User model: `app/Models/User.php` com quatro papéis — `admin`, `manager`, `financial`, `client` (`User::ROLE_*`). O financeiro lê todas as obras e escreve despesas e cobranças em qualquer uma; o gestor está limitado às obras que gere
- Session storage: Database driver (`SESSION_DRIVER=database`)
- Session lifetime: 120 minutes (`SESSION_LIFETIME=120`)
- Session encryption: Enabled (`SESSION_ENCRYPT=true`)
- Password hashing: Bcrypt (Laravel default, `BCRYPT_ROUNDS=12`)

## Monitoring & Observability

**Error Tracking:**
- Type: None detected (no Sentry, Rollbar, etc.)
- Fallback: Built-in Laravel error handling via `bootstrap/app.php`
- Error responses: HTML for web requests; JSON conditionally for `api/*` routes (but API routes not yet implemented)

**Logs:**
- Driver: Stack (file-based by default)
- Config: `LOG_CHANNEL=stack`, `LOG_STACK=single`, `LOG_LEVEL=warning`
- Location: `storage/logs/`
- Real-time tailing: `php artisan pail` (Laravel Pail)
- Logging library: Monolog v3.10.0

**Application Health:**
- Health endpoint: GET `/up` (Laravel built-in)
- Returns: 200 OK if application is healthy
- Used by: Docker health checks, load balancer probes

## CI/CD & Deployment

**Hosting:**
- Platform: HostGator shared hosting (cPanel)
- Domain: `obra360app.com`
- PHP version: 8.5 (ea-php85, set via cPanel API `LangPHP php_set_vhost_versions`)
- Deployment path: `~/obra360app.com/`

**CI Pipeline:**
- Service: GitHub Actions
- Workflow file: `.github/workflows/laravel.yml`
- Trigger: Push to `main` branch or pull request to `main`
- Jobs:
  1. **Test** (runs on every push and PR):
     - PHP 8.4 setup with extensions (mbstring, pdo_mysql, gd, zip, etc.)
     - Node 24 setup
     - Composer install, npm ci
     - PHP style check via `./vendor/bin/pint --test`
     - Frontend asset build via `npm run build`
     - Database migrations on SQLite
     - Laravel tests via `php artisan test`
     - Composer audit (dependencies) and npm audit (frontend dependencies)
  2. **Deploy** (runs only on successful test + main branch push):
     - Builds production assets (Node 24)
     - Compresses project to tar.gz excluding `.git`, `.github`, `node_modules`, `storage/*`, `.env`
     - Uploads to HostGator via SCP (SSH private key from secrets)
     - Extracts on server, runs artisan optimize, migrate, cache
     - Caches PHP version to 8.5 via cPanel uapi

## Environment Configuration

**Required env vars (from .env.example):**

*Application:*
- `APP_NAME` - Application name
- `APP_ENV` - Environment (local, production)
- `APP_KEY` - Encryption key (generated by `artisan key:generate`)
- `APP_DEBUG` - Debug mode (false in production)
- `APP_URL` - Public URL
- `APP_LOCALE`, `APP_FALLBACK_LOCALE` - Language settings

*Database:*
- `DB_CONNECTION` (mysql)
- `DB_HOST` (db in Docker)
- `DB_PORT` (3306)
- `DB_DATABASE` (obra360)
- `DB_USERNAME`, `DB_PASSWORD` (obra360_user)
- `DB_ROOT_PASSWORD` (MySQL root password for Docker)

*Queue & Cache:*
- `QUEUE_CONNECTION` (database)
- `CACHE_STORE` (database)

*Session:*
- `SESSION_DRIVER` (database)
- `SESSION_LIFETIME` (120)
- `SESSION_ENCRYPT` (true)

*Broadcasting & WebSockets:*
- `BROADCAST_CONNECTION` (log for dev, reverb for production)
- `REVERB_ALLOWED_ORIGINS` - CORS origins for WebSocket connections
- `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_APP_ID` - Reverb credentials
- `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` - Reverb server connection

*Frontend (Vite):*
- `VITE_APP_NAME` - Passed to frontend
- `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` - WebSocket connection for frontend

*WhatsApp:*
- `WHATSAPP_PHONE_ID` - Meta Business phone ID
- `WHATSAPP_TOKEN` - Meta Business API access token

*Web Push (PWA):*
- `VAPID_PUBLIC_KEY` - VAPID public key for push notifications
- `VAPID_PRIVATE_KEY` - VAPID private key for push notifications
- `VAPID_SUBJECT` - VAPID subject (mailto: email)

*Mail:*
- `MAIL_MAILER` (log for dev, smtp/postmark/resend for production)
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` (if SMTP)

*AWS (S3, optional):*
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`

**Secrets location:**
- GitHub Secrets (used by CI/CD workflow):
  - `SSH_HOST`, `SSH_PORT`, `SSH_USERNAME` - HostGator connection
  - `SSH_PRIVATE_KEY` - GitHub Actions SSH key (base64 encoded)
- `.env` file (version controlled as `.env.example`, actual `.env` never committed)
- Docker secrets: Passed via `docker-compose.yml` environment variables from `.env`

## Webhooks & Callbacks

**Incoming:**
- Nenhum externo. Existe uma rota interna de recolha: `POST /_csp/report` (`CspReportController`), para onde o navegador envia as violações de Content-Security-Policy; escreve um aviso por violação em `storage/logs`

**Outgoing:**
- WhatsApp notifications: Sent via `app/Channels/WhatsAppChannel.php` to Meta Graph API
- Web Push notifications: Sent via `app/Channels/WebPushChannel.php` to browser push service
- Broadcast events: Sent via Reverb to connected WebSocket clients in real-time

## Real-time Features

**WebSocket Broadcasting:**
- Server: Laravel Reverb (primary)
- Fallback: Pusher.js (if Reverb unavailable)
- Client: Laravel-Echo + Pusher.js (configured in `resources/js/app.js`)
- Use case: Real-time project updates, chat messages, notifications
- Non-blocking: Echo initialization wrapped in try-catch to prevent UI blocking

**Service Worker & PWA:**
- File: `public/sw.js`
- Manifest: `public/manifest.json`
- Features:
  - Offline support (via cache strategy — currently basic, not fully offline)
  - Push notification handling: Shows native OS notifications with vibration, actions
  - Notification click handling: Opens target URL or focuses existing window
  - App icons: 192x192 and 512x512 PNG (maskable + regular)
- Installation: Linked in HTML via `<link rel="manifest" href="/manifest.json">` and `<script>navigator.serviceWorker.register('/sw.js')</script>`

---

*Integration audit: 2026-08-20 — revisto em 2026-08-24*
