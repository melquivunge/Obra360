# Obra360 — Portal de Acompanhamento de Obras

Portal web para empresas de construção, reforma e fiscalização partilharem o progresso das obras com os seus clientes de forma transparente, visual e profissional.

A empresa gere clientes, obras, fases, fotos, despesas, pagamentos, documentos e atualizações. O cliente entra no portal e vê **apenas as suas obras**, com progresso, fotos e resumo financeiro em tempo real — sem precisar de perguntar por WhatsApp.

> **Valor central:** o cliente faz login e percebe imediatamente o estado da obra (fase atual, % de progresso, fotos recentes, quanto já foi gasto).

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.3+ (8.4 em Docker, 8.5 em produção), Laravel 13 |
| Frontend | Blade + Tailwind CSS v4 + Alpine.js, bundling com Vite 8 |
| Tempo real | Laravel Reverb + Laravel Echo (mensagens do chat) |
| Base de dados | MySQL 8 (SQLite em CI) |
| Filas | `database` |
| Ficheiros | Disco `local` privado (Flysystem) — servido por rotas autenticadas, pronto para S3 |
| Notificações | E-mail, Web Push (VAPID/PWA) e canal WhatsApp Business API |
| Auth | Laravel Breeze (Blade) |
| Qualidade | PHPUnit, Laravel Pint, GitHub Actions |

Não há SPA: tudo é renderizado no servidor.

---

## Funcionalidades por papel

Existem quatro papéis (`app/Models/User.php`): `admin`, `manager`, `financial`, `client`. O acesso é imposto por middleware (`EnsureUserRole`, `EnsureUserIsActive`) e pelo trait `AuthorizesProjectAccess`.

**Administrador** — `/admin`
- Gestão de utilizadores (criar, editar, ativar/desativar, soft delete)
- Gestão de clientes e vinculação de utilizadores a clientes
- Tudo o que gestor e financeiro fazem
- Configurações do sistema (nome da empresa, moeda, visibilidade financeira para o cliente, comentários)

**Gestor da Obra** — `/admin`
- CRUD de obras, fases (com % e ordenação), fotos, documentos e atualizações manuais
- Responde às mensagens dos clientes

**Financeiro** — `/admin`
- Despesas por obra (categoria, fornecedor, comprovativo)
- Pagamentos (registo, edição, marcar como pago)
- Resumo financeiro automático: gasto total, saldo, % do orçamento

**Cliente** — `/portal`
- Dashboard com as suas obras: estado, fase atual, progresso, último update, foto de capa
- Detalhe da obra: galeria de fotos, fases, resumo financeiro (se ativado), documentos libertados, histórico de atualizações
- Conversa por obra com a empresa (mensagens em tempo real)
- PWA instalável, com subscrição de notificações push

Alterações relevantes (foto nova, mudança de fase, documento libertado) geram automaticamente uma entrada no histórico via `ProjectUpdateObserver`, que depois notifica os utilizadores do cliente.

> **Ressalva sobre notificações.** `ProjectUpdateNotification` envia por `WhatsAppChannel` e `WebPushChannel` — não por e-mail.
> O WhatsApp só dispara se `WHATSAPP_PHONE_ID` e `WHATSAPP_TOKEN` estiverem definidos.
> O Web Push está **inativo**: o pacote `minishlink/web-push` não está instalado, por isso o canal regista
> "WebPush skipped" e não entrega nada. As subscrições continuam a ser guardadas.
> Para o ativar: `composer require minishlink/web-push` e definir as chaves VAPID.

---

## Setup local

### Com Docker (recomendado)

```bash
cp .env.example .env
# preencher DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_ROOT_PASSWORD

docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

App em <http://localhost:8000>.

Serviços: `obra360_app` (PHP-FPM 8.4), `obra360_nginx` (porta 8000), `obra360_db` (MySQL 8).

### Sem Docker

```bash
composer setup     # install + .env + key + migrate + npm install + build
php artisan db:seed
composer dev
```

`composer dev` arranca em paralelo: `artisan serve`, `queue:listen`, `pail` (logs), `vite` e `reverb:start`.

### Utilizador inicial

O seeder cria um administrador. Define antes:

```env
SEED_ADMIN_EMAIL=admin@example.com
SEED_ADMIN_PASSWORD=...
```

Sem `SEED_ADMIN_PASSWORD` em ambiente local é gerada uma password temporária impressa na consola. Em produção o seed falha se a variável não estiver definida.

---

## Variáveis de ambiente relevantes

Além das padrão do Laravel (`APP_*`, `DB_*`, `MAIL_*`):

| Variável | Para quê |
|---|---|
| `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD` | Admin criado pelo seeder |
| `FILESYSTEM_DISK` | `local` — media privada servida por rotas autenticadas |
| `QUEUE_CONNECTION` | `database` (notificações e envios) |
| `BROADCAST_CONNECTION`, `REVERB_*`, `REVERB_ALLOWED_ORIGINS` | WebSockets do chat |
| `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` | Cliente Echo no browser |
| `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` | Web Push do PWA |
| `WHATSAPP_PHONE_ID`, `WHATSAPP_TOKEN` | Canal WhatsApp Business API |

Se as variáveis do Reverb não estiverem definidas, o Echo falha silenciosamente e o resto da app continua a funcionar.

---

## Comandos

```bash
composer dev                              # ambiente de desenvolvimento completo
composer test                             # limpa config + php artisan test
./vendor/bin/pint                         # formatação PHP (--test para verificar)
npm run build                             # build de produção dos assets

php artisan obra360:media:migrate-public  # ver docs/production-readiness.md
```

`obra360:media:migrate-public` move media legada de `storage/app/public` para o disco privado. Corre primeiro sem flags (dry-run), depois `--commit`, e só no fim `--commit --delete-source`.

---

## Estrutura

```
app/
  Channels/       WebPushChannel, WhatsAppChannel
  Console/        obra360:media:migrate-public
  Events/         MessageSent (broadcast do chat)
  Http/
    Controllers/  Admin/, Client/, Portal/, Auth/
    Middleware/   EnsureUserRole, EnsureUserIsActive
  Models/         Project, ProjectPhase, Photo, Expense, Payment,
                  Document, ProjectUpdate, Message, Client, Setting,
                  PushSubscription, User
  Observers/      ProjectUpdateObserver (histórico automático)
  Traits/         AuthorizesProjectAccess
docker/           nginx + php config
docs/             notas de produção
public/           manifest.json + sw.js (PWA)
routes/web.php    /admin/* e /portal/*
```

Rotas e UI estão em português (`/admin/obras`, `/portal/obras/{id}`).

---

## Testes

```bash
composer test
```

Cobertura atual em `tests/Feature`: autenticação, redirecionamento por papel, bloqueio de utilizador inativo, recuperação de password, gestão de utilizadores, esquema da base de dados e hardening de segurança.

---

## Deploy

`.github/workflows/laravel.yml` corre em cada push e PR para `main`:

1. **Testes & Qualidade** — Pint (`--test`), migrações em SQLite, `php artisan test`, build de assets, `composer audit` e `npm audit`.
2. **Deploy → HostGator** — apenas em push para `main` e só se o job anterior passar. Constrói assets de produção, instala `vendor` com `--no-dev --optimize-autoloader`, empacota em tar, envia por SCP e corre `migrate --force` + caches (`config`, `route`, `view`, `event`) + `storage:link` via SSH. O VHost é forçado para PHP 8.5 (`ea-php85`) pela API do cPanel.

Segredos necessários no repositório: `SSH_HOST`, `SSH_PORT`, `SSH_USERNAME`, `SSH_PRIVATE_KEY`.

---

## Estado do projeto

Implementado e em produção: autenticação e papéis, gestão de utilizadores e clientes, obras com fases, fotos, documentos, despesas, pagamentos, histórico de atualizações, portal do cliente, mensagens em tempo real, configurações, PWA e notificações push.

Por fazer:

- Relatórios em PDF (geral, financeiro, fotos, fases) — DomPDF ainda não instalado
- Log de auditoria dedicado (hoje o histórico vive em `project_updates`)
- Migração para S3 (arquitetura já preparada)
- App móvel nativa

O planeamento detalhado vive em `.planning/` (`PROJECT.md`, `ROADMAP.md`, `STATE.md`) e as notas operacionais em `docs/production-readiness.md`.
