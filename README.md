# Obra360 — Portal de Acompanhamento de Obras

Portal web para empresas de construção, reforma, arquitetura e design de interiores compartilharem o andamento do trabalho com os seus clientes de forma transparente, visual e profissional.

A empresa gerencia clientes, obras e projetos, fases, fotos, despesas, pagamentos, documentos, plantas e atualizações. O cliente entra no portal e vê **apenas o que é dele**, com progresso, fotos, plantas e resumo financeiro em tempo real — sem precisar perguntar por WhatsApp.

> **Valor central:** o cliente faz login e entende imediatamente o estado do trabalho (fase atual, % de progresso, fotos recentes, quanto já foi gasto, qual a planta aprovada).

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.3+ (8.4 em Docker, 8.5 em produção), Laravel 13 |
| Frontend | Blade + Tailwind CSS v4 + Alpine.js, bundling com Vite 8 |
| Tempo real | Laravel Reverb + Laravel Echo (chat das obras) |
| Banco de dados | MySQL 8 (SQLite em CI) |
| Filas | `database` |
| Arquivos | Disco `local` privado (Flysystem) — servidos por rotas autenticadas, pronto para S3 |
| Notificações | E-mail, Web Push (VAPID/PWA) e canal WhatsApp Business API |
| Relatórios | DomPDF (`barryvdh/laravel-dompdf`) |
| 3D | `@google/model-viewer`, carregado sob demanda |
| Auth | Laravel Breeze (Blade) |
| Qualidade | PHPUnit, Laravel Pint, GitHub Actions |

Não há SPA: tudo é renderizado no servidor. A interface está em português do Brasil.

---

## Tipos de trabalho

Um projeto tem um **tipo** (`Project::TYPES`), e é ele que governa o vocabulário e o que a interface mostra:

- **Construção** e **Reforma** — obras, com fases de execução e progresso derivado da média das fases
- **Projeto de arquitetura** e **Design de interiores** — serviços de projeto, com etapas de entrega e plantas versionadas

Não há entidade separada: as quatro compartilham cliente, orçamento, prazo, documentos, pagamentos, mensagens e portal. O que muda é o vocabulário — um cliente que contratou só o projeto nunca vê a palavra "obra" — e as funcionalidades que fazem sentido em cada caso.

Uma obra pode ficar **ligada ao projeto de arquitetura que lhe deu origem**, e passa a mostrar as plantas aprovadas desse projeto. Sem cópia de arquivos: a planta tem um dono só, e uma correção aparece imediatamente na obra.

---

## Plantas

Uma planta é um desenho identificado ("Planta baixa — piso 0"), não um arquivo. Cada revisão é uma **versão numerada**: enviar um arquivo novo cria a versão seguinte, a anterior fica marcada como substituída e continua legível.

- **Formatos aceitos:** PDF, JPG, PNG, WEBP, DWG, DXF, SKP, RVT, IFC, GLB, GLTF, ZIP
- **Visualização no navegador:** PDF e imagens abrem direto; GLB e GLTF abrem em visualizador 3D com rotação e zoom. Revit, SketchUp e IFC apenas baixam — convertê-los no servidor não é viável em hospedagem compartilhada
- **Endereço externo:** uma versão pode apontar para um link (Drive, WeTransfer) em vez de um arquivo, para desenhos que passem o limite de upload do servidor
- **Aprovação do cliente:** o cliente aprova a versão atual no portal, e fica registrado quem aprovou e quando — no histórico do projeto e no log de auditoria. Uma versão aprovada não é aprovada de novo nem alterada
- **Comentários por versão:** cliente e equipe conversam ao lado do desenho. Ficam presos à versão, então um pedido feito sobre a v2 continua legível na v2 depois de sair a v3
- **Revisões do contrato:** se o contrato fixa um número de revisões, o sistema conta as versões enviadas ao cliente além da primeira de cada planta, e mostra o saldo aos dois lados. Exceder nunca impede um envio

O cliente só vê versões que lhe foram **enviadas** — um rascunho de trabalho não existe para ele.

---

## Funcionalidades por papel

Quatro papéis (`app/Models/User.php`): `admin`, `manager`, `financial`, `client`. O acesso é imposto por middleware (`EnsureUserRole`, `EnsureUserIsActive`), pelo trait `AuthorizesProjectAccess` e pelos gates `manage-projects`, `access-financial` e `manage-users`.

**Administrador** — `/admin`
- Gestão de usuários (criar, editar, ativar/desativar, exclusão reversível)
- Gestão de clientes e vinculação de usuários a clientes
- Tudo o que gestor e financeiro fazem
- Configurações (nome da empresa, logotipo, moeda, visibilidade financeira, comentários, envio de arquivos pelo cliente, modo de progresso, notificações por tipo de evento)
- Log de auditoria de tudo o que é criado, alterado ou removido

**Gestor** — `/admin`, limitado aos projetos que gerencia
- CRUD de obras e projetos, fases (com % e ordenação), fotos com miniaturas e galeria filtrável, documentos, plantas e atualizações manuais
- Etapas de entrega pré-definidas por tipo, aplicáveis em um clique (arquitetura segue a NBR 13532)
- Quatro relatórios por projeto (geral, financeiro, fotos, fases), na tela e em PDF
- Responde às mensagens dos clientes

**Financeiro** — `/admin`, em **todos** os projetos
- Despesas por projeto (criar, editar, excluir) com categoria, fornecedor e comprovante
- Pagamentos (registro, edição, marcar como pago)
- Orçamento por linhas, comparado com o gasto real por categoria
- Resumo financeiro automático: gasto total, saldo, % do orçamento, distribuição por categoria e por mês
- Lê o resto do projeto para contexto, mas não altera a estrutura

**Cliente** — `/portal`
- Painel com os seus trabalhos: estado, fase atual, progresso, última atualização, capa
- Detalhe: galeria, fases, resumo financeiro (se ativado), documentos liberados, plantas enviadas, histórico
- Aprova plantas e comenta versões
- Envia fotos e documentos (pode ser desligado nas configurações)
- Conversa por projeto com a empresa, em tempo real
- PWA instalável com notificações push

Alterações relevantes geram entrada no histórico via `ProjectUpdateObserver`, que depois notifica os usuários do cliente.

> **Notificações.** `ProjectUpdateNotification` envia por e-mail, Web Push e WhatsApp.
> Cada tipo de evento pode ser silenciado nas Configurações. Quando faltam
> credenciais o canal registra um aviso no log em vez de sair calado.

---

## Segurança

- **Content-Security-Policy** com nonce por requisição. `script-src` não permite script inline; os handlers em linha foram eliminados das telas e há teste que varre os arquivos para impedir que voltem. A política é **imposta** por omissão em todos os ambientes; `CSP_REPORT_ONLY=true` volta a modo de relato — registra violações sem bloquear — para calibrá-la sem deploy. Há também um interruptor de emergência que remove o cabeçalho
- `style-src` mantém `'unsafe-inline'` e `script-src` mantém `'unsafe-eval'` (Alpine avalia expressões com `new Function`); ambos documentados no código, com o caminho para apertar
- Toda a mídia vive no disco privado e sai apenas por rota autenticada. Um registro cujo arquivo não existe responde 404
- Limite de requisições em todas as escritas, calibrado por custo: 20/min no upload de fotos, 30/min em documentos, 120/min no que grava uma linha
- Cabeçalhos: nosniff, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS sobre HTTPS
- Validação de extensão por lista fechada, com bloqueio de upload de PHP

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

`composer dev` sobe em paralelo: `artisan serve`, `queue:listen`, `pail` (logs), `vite` e `reverb:start`.

### Usuário inicial

O seeder cria um administrador. Defina antes:

```env
SEED_ADMIN_EMAIL=admin@example.com
SEED_ADMIN_PASSWORD=...
```

Sem `SEED_ADMIN_PASSWORD` em ambiente local é gerada uma senha temporária impressa no console. Em produção o seed falha se a variável não estiver definida.

---

## Variáveis de ambiente relevantes

Além das padrão do Laravel (`APP_*`, `DB_*`, `MAIL_*`):

| Variável | Para quê |
|---|---|
| `APP_LOCALE` | `pt_BR` — mensagens de validação e do framework em português |
| `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD` | Admin criado pelo seeder |
| `FILESYSTEM_DISK` | `local` — mídia privada servida por rotas autenticadas |
| `QUEUE_CONNECTION` | `database` (notificações e envios) |
| `BROADCAST_CONNECTION`, `REVERB_*`, `REVERB_ALLOWED_ORIGINS` | WebSockets do chat |
| `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` | Cliente Echo no navegador |
| `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` | Web Push do PWA |
| `WHATSAPP_PHONE_ID`, `WHATSAPP_TOKEN` | Canal WhatsApp Business API |
| `CSP_ENABLED` | Interruptor de emergência da política de segurança |
| `CSP_REPORT_ONLY` | omissão `false` (impõe); `true` relata sem bloquear |
| `CSP_REPORT_URI` | Destino dos relatórios de violação |

Se as variáveis do Reverb não estiverem definidas, o Echo falha em silêncio e o resto do app continua funcionando.

---

## Comandos

```bash
composer dev                              # ambiente de desenvolvimento completo
composer test                             # limpa config + php artisan test
./vendor/bin/pint                         # formatação PHP (--test para verificar)
npm run build                             # build de produção dos assets

php artisan obra360:media:migrate-public  # mídia legada do disco público → privado
php artisan obra360:photos:thumbnails     # miniaturas para fotos antigas
php artisan obra360:audit:prune           # retenção do log de auditoria (365 dias)
php artisan obra360:trash:prune           # arquivos de projetos excluídos (90 dias)
```

Os dois comandos de limpeza rodam em **modo de ensaio por padrão** — contam e mostram, sem apagar. Só `--commit` escreve.

### Manutenção agendada

Três tarefas em `routes/console.php`: limpeza de tokens expirados (diária), poda de jobs falhados (semanal) e retenção do log de auditoria (diária). **O agendador do Laravel precisa de um cron chamando-o**, senão nada disso roda:

```
* * * * * /caminho/para/php /caminho/para/app/artisan schedule:run >> /dev/null 2>&1
```

Detalhes e o procedimento de produção em `docs/production-readiness.md`.

---

## Estrutura

```
app/
  Channels/       WebPushChannel, WhatsAppChannel
  Console/        obra360:media:migrate-public, obra360:photos:thumbnails,
                  obra360:audit:prune, obra360:trash:prune
  Events/         MessageSent (broadcast do chat)
  Http/
    Controllers/  Admin/, Client/, Portal/, Auth/
    Middleware/   EnsureUserRole, EnsureUserIsActive, SecurityHeaders,
                  ExplainTruncatedUpload
  Models/         Project, ProjectPhase, Photo, Expense, Payment, BudgetItem,
                  Document, Plan, PlanVersion, PlanComment, ProjectUpdate,
                  Message, Client, Setting, PushSubscription, AuditLog, User
  Observers/      ProjectUpdateObserver (histórico), AuditObserver (auditoria),
                  MediaCleanupObserver (arquivos na exclusão definitiva)
  Support/        helpers (money), Thumbnail, PhaseTemplates
  Traits/         AuthorizesProjectAccess
docker/           nginx + php config
docs/             notas de produção
public/           manifest.json + sw.js (PWA)
routes/web.php    /admin/* e /portal/*
```

Rotas e interface em português (`/admin/obras`, `/portal/obras/{id}`).

---

## Testes

```bash
composer test
```

**540 testes.** Autenticação e papéis, gestão de usuários e clientes, esquema, hardening de segurança e política de conteúdo, cálculos financeiros e orçamento por linhas, relatórios, fronteiras de acesso, auditoria, notificações, despesas e pagamentos, KPIs do painel, miniaturas e filtros da galeria, plantas e versões, portal do cliente, aprovação e comentários, ligação obra↔projeto, envios do cliente, paginação, feedback de validação e ordem das rotas.

Alguns testes existem para impedir regressões específicas: um varre as telas atrás de handlers inline (que a política de segurança proíbe), outro percorre todas as rotas atrás de segmentos literais escondidos por parâmetros — o defeito que já impediu criar obras pela interface.

---

## Deploy

`.github/workflows/laravel.yml` roda em cada push e PR para `main`:

1. **Testes & Qualidade** — Pint (`--test`), migrações em SQLite, `php artisan test`, build de assets, `composer audit` e `npm audit`.
2. **Deploy → HostGator** — apenas em push para `main` e só se o job anterior passar. Constrói assets de produção, instala `vendor` com `--no-dev --optimize-autoloader`, empacota em tar, envia por SCP e roda `migrate --force` + caches (`config`, `route`, `view`, `event`) + `storage:link` via SSH. O VHost é forçado para PHP 8.5 (`ea-php85`) pela API do cPanel.

Segredos necessários no repositório: `SSH_HOST`, `SSH_PORT`, `SSH_USERNAME`, `SSH_PRIVATE_KEY`. Recomenda-se ainda `SSH_KNOWN_HOSTS` — o conteúdo de `known_hosts` com as chaves do servidor, obtido e conferido fora do pipeline. Com ele o deploy falha se a chave do servidor mudar; sem ele o workflow recorre a `ssh-keyscan`, que confia no que o servidor responder na primeira ligação.

---

## Estado do projeto

Os 83 requisitos v1 estão implementados. Da lista v2, foram entregues antecipadamente WhatsApp, tempo real por WebSockets, Web Push, envio de arquivos pelo cliente e orçamento por linhas. Os serviços de arquitetura e design de interiores estão completos, do projeto à obra.

Por fazer:

- **Migração para S3.** A arquitetura já está pronta — o Flysystem abstrai e todo o código usa `Storage::disk('local')`, nunca caminhos absolutos. Falta instalar o driver e apontar as credenciais
- **Notificar a outra parte** quando há comentário novo em uma planta (hoje o sinal é ver o comentário ao abrir)
- **Alinhar a limpeza de arquivos individuais** com a política de exclusão definitiva: excluir uma foto ou documento um a um ainda apaga o arquivo na hora
- **Multi-empresa (SaaS)** e **app móvel nativo** — escopo v2, ambos projetos à parte

O planejamento vive em `.planning/` e as notas operacionais em `docs/production-readiness.md`.
