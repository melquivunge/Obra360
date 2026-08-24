# Stack Tecnológica

**Data da análise:** 2026-08-24

## Linguagens

**Principais:**
- PHP 8.3+ (exigido pelo `composer.json`), PHP 8.4 (Docker e CI), PHP 8.5 (produção HostGator, forçada no deploy via `uapi LangPHP php_set_vhost_versions version=ea-php85`) — lógica de servidor, routing, ORM, filas, WebSockets
- JavaScript (módulos ES) — ponto de entrada em `resources/js/app.js`
- CSS via Tailwind v4 — ponto de entrada em `resources/css/app.css`

## Runtime

**Ambiente:**
- PHP-FPM 8.4 (contentor Docker `obra360_app`, imagem `php:8.4-fpm`)
- Node.js 24-Alpine (fase de build do `Dockerfile`), Node 24 no CI (`.github/workflows/laravel.yml`)
- Composer 2 (`COPY --from=composer:latest` no `Dockerfile`)
- npm (só ferramenta de build; não é servidor em runtime)

**Gestor de pacotes:**
- Composer 2 — dependências PHP
  - Lockfile: `composer.lock` presente e versionado
  - O `vendor/` vai no pacote de deploy (`composer install --no-dev --optimize-autoloader` antes do `tar`)
  - `autoload.files` carrega `app/Support/helpers.php` em todos os processos
- npm — dependências Node
  - Lockfile: `package-lock.json` presente e versionado; `npm ci` é usado no `Dockerfile` e no CI
  - O pacote de deploy exclui `.git`, `.github`, `.env`, `node_modules`, `storage/app`, `storage/logs` e `*.docx`

## Frameworks

**Núcleo:**
- Laravel v13.26.1 (`laravel/framework`, requisito `^13.8`) — routing, Eloquent, filas, eventos, broadcasting, mail, validação
- Laravel Reverb v1.11.1 (`laravel/reverb`) — servidor WebSocket de primeira parte para broadcasting
- Laravel Breeze v2.4.2 (`laravel/breeze`, só em `require-dev`) — scaffolding de autenticação. O registo público está desactivado: não existe `RegisteredUserController` nem rota `/register` (ver `routes/auth.php` e `tests/Feature/Auth/RegistrationTest.php`)

**Frontend:**
- Vite 8 (`vite` ^8.2.1) — bundler; configuração em `vite.config.js`
- `laravel-vite-plugin` ^3.2 — liga o Vite ao manifest de assets do Laravel
- Tailwind CSS v4 (`tailwindcss` ^4.0.0, `@tailwindcss/vite` ^4.3.3) — integrado como plugin do Vite
- Alpine.js ^3.16.2 — usado em modais, dropdowns, separadores e toggles. É a razão de `script-src` manter `'unsafe-eval'` na CSP: o Alpine 3 avalia as expressões dos atributos com `new Function`
- `@google/model-viewer` ^4.3.1 — visualizador 3D para GLB e GLTF; importado sob demanda em `resources/js/app.js`, só quando a página tem um `<model-viewer>`, porque traz um motor WebGL de cerca de 1 MB

**Broadcasting/WebSockets (cliente):**
- `laravel-echo` ^2.4.0 — cliente de broadcasting; instanciado com `broadcaster: 'reverb'` em `resources/js/app.js`, dentro de um `try/catch` para não bloquear a UI
- `pusher-js` ^8.6.0 — cliente do protocolo Pusher que o Echo usa para falar com o Reverb

**Relatórios PDF:**
- `barryvdh/laravel-dompdf` v3.1.2 (sobre `dompdf/dompdf` v3.1.6) — exportação PDF dos relatórios em `App\Http\Controllers\Admin\ReportController::pdf()`; `setIsRemoteEnabled(false)` impede o PDF de ir buscar recursos remotos

**Web Push:**
- `minishlink/web-push` v8.0.0 — instalado. `app/Channels/WebPushChannel.php` continua a guardar em `class_exists(WebPush::class)`, mas o pacote existe, pelo que o envio ocorre quando `VAPID_PUBLIC_KEY` e `VAPID_PRIVATE_KEY` estão definidas e o utilizador tem registos em `push_subscriptions`

**Testes:**
- PHPUnit 12.5.30 (`phpunit/phpunit`) — configuração em `phpunit.xml`
- Mockery 1.6.14 (`mockery/mockery`)
- FakerPHP 1.24.1 (`fakerphp/faker`)

**Build/Dev:**
- Laravel Pint v1.30.5 (`laravel/pint`) — formatador PHP; `./vendor/bin/pint --test` no CI
- Laravel Pail v1.2.7 (`laravel/pail`) — leitura de logs em tempo real
- Laravel Pao v1.1.4 (`laravel/pao`)
- Concurrently ^10.0.5 — corre `artisan serve`, `queue:listen`, `pail`, `npm run dev` e `reverb:start` em paralelo (script `dev` do `composer.json`)
- Nunomaduro Collision v8.9.5 — formatação de erros em CLI

## Dependências-chave

**Críticas:**
- `laravel/framework` v13.26.1
- `laravel/reverb` v1.11.1 — WebSockets (mensagens em tempo real)
- `barryvdh/laravel-dompdf` v3.1.2 — geração de PDF
- `minishlink/web-push` v8.0.0 — notificações push
- `guzzlehttp/guzzle` 7.15.3 — cliente HTTP; usado em `app/Channels/WhatsAppChannel.php` contra a Meta Graph API

**Infraestrutura e logging:**
- `monolog/monolog` 3.10.0 — logging PSR-3
- `laravel/tinker` v3.0.2 e `psy/psysh` v0.12.23 — REPL
- `vlucas/phpdotenv` v5.6.4 — leitura do `.env`

**Ficheiros e armazenamento:**
- `league/flysystem` 3.35.2 e `league/flysystem-local` 3.31.0
- `league/flysystem-aws-s3-v3` **não está instalado**. O disco `s3` está declarado em `config/filesystems.php`, mas usá-lo exige `composer require league/flysystem-aws-s3-v3`

**Utilitários:**
- `nesbot/carbon` 3.13.2 — datas
- Componentes Symfony v7.4.x (`symfony/routing` v7.4.15, entre outros) trazidos pelo Laravel

## Extensões PHP (instaladas no Docker)

- `pdo_mysql` — driver MySQL
- `mbstring` — strings multibyte (UTF-8)
- `exif` — metadados de imagem
- `pcntl` — controlo de processos (necessário aos workers de fila)
- `bcmath` — aritmética de precisão arbitrária
- `gd` — processamento de imagem; é o que `App\Support\Thumbnail` usa para gerar as miniaturas
- `zip` — arquivos

## Configuração

**Ambiente:**
- Configurado por `.env`; modelo em `.env.example`
- Aplicação: `APP_NAME`, `APP_KEY`, `APP_ENV`, `APP_URL`, `APP_DEBUG`, `APP_LOCALE` (`pt_BR` desde 24/08 — antes era `en`, e o `lang/pt_BR.json` nunca chegava a ser lido)
- Base de dados: `DB_CONNECTION` (mysql), `DB_HOST` (db), `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`
- Filas: `QUEUE_CONNECTION` (database)
- Cache: `CACHE_STORE` (database)
- Sessão: `SESSION_DRIVER` (database), `SESSION_LIFETIME` (120), `SESSION_ENCRYPT` (true)
- Broadcasting: `BROADCAST_CONNECTION` (log no exemplo), `REVERB_ALLOWED_ORIGINS`
- Ficheiros: `FILESYSTEM_DISK` (local)
- Mail: `MAIL_MAILER` (log no exemplo)
- WhatsApp: `WHATSAPP_PHONE_ID`, `WHATSAPP_TOKEN` (lidos por `config/services.php`)
- Web Push VAPID: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`
- Seeding: `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD` (o `DatabaseSeeder` recusa correr em produção sem a segunda)
- AWS: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_USE_PATH_STYLE_ENDPOINT` — todas vazias no exemplo, e o driver ainda não está instalado
- Content-Security-Policy (`config/security.php`): `CSP_ENABLED` (omissão: ligada), `CSP_REPORT_ONLY` (omissão: relato em produção, imposição fora dela), `CSP_REPORT_URI` (omissão: `/_csp/report`) e `CSP_EXTRA_{SCRIPT,STYLE,IMG,FONT,CONNECT}_SRC`
- Reverb visto pelo navegador (lido por `config/security.php` para o `connect-src`): `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`

**Build de frontend:**
- `vite.config.js`
  - Entradas: `resources/css/app.css`, `resources/js/app.js`
  - Plugin Tailwind CSS v4 activo
  - Fonte `Instrument Sans` (pesos 400/500/600) via helper `bunny` do `laravel-vite-plugin/fonts`
  - `refresh: true`; `server.watch.ignored` exclui `storage/framework/views`

**Configuração PHP (Docker):**
- `docker/php/local.ini`
- `upload_max_filesize = 64M`, `post_max_size = 64M`, `max_execution_time = 300`, `memory_limit = 256M`

**Docker Compose:**
- `docker-compose.yml`
- Serviços: `app` (PHP-FPM), `webserver` (Nginx Alpine), `db` (MySQL 8.0)
- Rede `obra360_network` (bridge), volume `db_data`
- Porta exposta: 8000 (Nginx)
- As variáveis de MySQL são obrigatórias (`${DB_DATABASE:?...}`) — o compose falha se faltarem no `.env`

**Dockerfile (multi-stage):**
- Fase `frontend`: `node:24-alpine`, `npm ci` a partir de `package.json` + `package-lock.json`, `npm run build`
- Fase de aplicação: `php:8.4-fpm`, copia `public/build` da fase anterior, `composer install --no-dev`
- Expõe a porta 9000; comando `php-fpm`

## Requisitos de plataforma

**Desenvolvimento:**
- Docker + Docker Compose (`docker-compose.yml`)
- PHP 8.3+ e Composer 2 para comandos artisan no host
- Node.js 24+ e npm para o build de frontend
- `composer run dev` levanta serve, queue, pail, vite e reverb em paralelo

**Produção (HostGator/cPanel):**
- PHP 8.5 (`/opt/cpanel/ea-php85/root/usr/bin/php`)
- Acesso SSH para o deploy do GitHub Actions
- Domínio: `obra360app.com`
- Pós-deploy: `optimize:clear`, `migrate --force`, `config:cache`, `route:cache`, `view:cache`, `event:cache`, `storage:link --force`

**CI/CD:**
- GitHub Actions (`.github/workflows/laravel.yml`)
- Job `test`: PHP 8.4, Node 24, SQLite, `pint --test`, `npm run build`, `php artisan test`, `composer audit --abandoned=report`, `npm audit --audit-level=moderate`
- Job `deploy`: só em push para `main`; build de assets, `tar` com `vendor/`, `scp` e comandos artisan por SSH
- `.github/dependabot.yml` está presente — as actualizações de dependências têm PR automático

---

*Análise de stack: 2026-08-24*
