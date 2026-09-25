# Preocupações do Código

**Data da análise:** 2026-08-24

Este documento lista apenas o que ainda está aberto e é verificável no código. O que foi fechado desde a análise de 22/08 está resumido no fim.

## Dívida técnica

**Migração para S3 por concluir:**
- Problema: toda a mídia vive no disco `local` (`storage/app/private`), num alojamento compartilhado onde o disco é finito
- Estado: o Flysystem já abstrai o acesso e todo o código usa `Storage::disk('local')`, nunca caminhos absolutos — a mudança é de configuração, não de reescrita. Falta o passo concreto: `league/flysystem-aws-s3-v3` **não está instalado** (em `composer.lock` aparece apenas como sugestão do framework, e `vendor/league/` só tem `flysystem` e `flysystem-local`), as variáveis `AWS_*` do `.env.example` estão vazias, e as rotas de mídia servem os bytes através do PHP em vez de URLs assinadas
- Arquivos: `config/filesystems.php` (o disco `s3` está declarado), `composer.json`, `.env.example`
- Caminho: instalar o driver, apontar o disco, migrar os arquivos e decidir entre continuar a servir por rota autenticada ou passar a URLs assinadas

**Assimetria na remoção de arquivos:**
- Problema: excluir uma obra ou uma planta é reversível e os arquivos ficam no disco até o prazo de retenção expirar (`MediaCleanupObserver` em `forceDeleting`, acionado por `obra360:trash:prune`). Excluir **uma foto, um documento ou uma despesa** continua a apagar o arquivo do disco no mesmo instante, embora a linha seja apenas soft delete
- Arquivos: `app/Http/Controllers/Admin/ProjectPhotoController.php` (linhas 57-59), `ProjectDocumentController.php` (53), `ProjectExpenseController.php` (75-77) — todos chamam `Storage::disk('local')->delete()` antes do `$model->delete()`; `app/Observers/MediaCleanupObserver.php` só reage a `Project` e `Plan`
- Efeito: restaurar uma foto excluída devolve a linha e deixa a galeria a apontar para o vazio — exactamente o cenário que o observador existe para evitar nas obras
- Caminho: estender o `MediaCleanupObserver` a `Photo`, `Document` e `Expense`, e tirar a exclusão do arquivo dos controladores

**Sem detecção automática de registros de mídia sem arquivo:**
- Problema: a base de produção foi povoada a partir de outro ambiente e trouxe registros de `photos`, `documents` e capas cujo arquivo nunca chegou ao servidor
- Estado: o sintoma está tratado — `mediaResponse()` e os downloads verificam `Storage::disk('local')->exists()` e devolvem 404 em vez de 500 (`tests/Feature/MissingMediaTest.php`), e as vistas caem para a imagem padrão (`resources/views/components/project-cover.blade.php`). Os oito registros de 22/08 foram enviados à mão
- Por fazer: não existe comando nem verificação agendada que percorra as tabelas e assinale registros sem arquivo correspondente. A ferramenta mais próxima continua a ser `obra360:media:migrate-public`, que assinala `missing_public` ao percorrer os caminhos

**Nenhum aviso quando há comentário novo numa planta:**
- Problema: `Admin\ProjectPlanVersionController::storeComment()` e `Client\PlanController::storeComment()` gravam o `PlanComment` e devolvem `back()->with('success', ...)`. Nenhum dos dois notifica a outra parte nem escreve no histórico do projeto
- Arquivos: `app/Http/Controllers/Admin/ProjectPlanVersionController.php` (128-143), `app/Http/Controllers/Client/PlanController.php` (`storeComment`), `app/Notifications/` (só existe `ProjectUpdateNotification`)
- Efeito: um comentário do cliente numa revisão só é visto por quem voltar à página da planta. A aprovação, em contraste, entra no histórico e na auditoria (`ProjectUpdate::log(..., 'planta_aprovada')`)
- Caminho: registrar um `ProjectUpdate` no comentário, que já traz o canal de notificação atrás

**`Admin\ProjectController` voltou a crescer:**
- Problema: 455 linhas. Foi dividido a 22/08 (ficou com 206) e recuperou o tamanho com o separador das plantas, a paginação por aba, a ligação ao projeto de origem e as regras de validação da origem
- Arquivos: `app/Http/Controllers/Admin/ProjectController.php`
- Nota: ao contrário da versão de 20/08, o que lá está é coeso — CRUD da obra, paginação dos separadores da própria página e a lógica da ligação de origem; fases, fotos, despesas, documentos, plantas e orçamento têm controladores próprios

## Segurança

**CSP em modo de relato em produção — resolvido:**
- Era: `'report_only' => env('CSP_REPORT_ONLY', env('APP_ENV') === 'production')` — em produção o cabeçalho saía como `Content-Security-Policy-Report-Only` e o navegador não bloqueava nada
- Agora: a omissão é impor em todos os ambientes (`filter_var(env('CSP_REPORT_ONLY', false), FILTER_VALIDATE_BOOLEAN)`). Voltar a relato para calibrar é deliberado e explícito: `CSP_REPORT_ONLY=true`
- Arquivos: `config/security.php`, `app/Http/Middleware/SecurityHeaders.php`, `app/Http/Controllers/CspReportController.php`, `.env.example`

**Cedências dentro da própria política:**
- `style-src` mantém `'unsafe-inline'`: as vistas da área interna desenham-se com atributos `style=` em todo o lado. Apertá-lo obriga a reescrever as vistas
- `script-src` mantém `'unsafe-eval'`: o Alpine 3 avalia as expressões dos `x-data`/`x-on` com `new Function`. Sai no dia em que se passar à build `@alpinejs/csp`, que obriga a registrar cada componente em JavaScript
- Arquivos: `app/Http/Middleware/SecurityHeaders.php` (método `policy()`), onde as duas cedências estão comentadas como deliberadas
- Nota: `script-src` **não** tem `'unsafe-inline'` — cada `<script>` legítimo leva nonce por requisição, e `tests/Feature/InlineScriptFreeViewsTest.php` impede a reintrodução de blocos sem nonce

## Limites de escala

**Tabela `cache` sem varrimento:**
- Problema: `CACHE_STORE=database` e as linhas expiradas só saem quando a chave é lida outra vez. O Laravel não traz limpeza em massa para o store de base de dados (`cache:prune-stale-tags` é só Redis), e `routes/console.php` não agenda nada para esta tabela
- Arquivos: `.env.example`, `routes/console.php`, `docs/production-readiness.md` (secção *Known gap*)
- Efeito: limitado na prática pelo conjunto pequeno e fixo de chaves que a aplicação usa (`ClientLayoutComposer`), mas nada o varre

**O agendador depende de uma entrada de cron criada à mão:**
- Problema: `routes/console.php` agenda quatro tarefas de retenção, mas o agendador do Laravel não é um daemon — só corre quando algo chama `schedule:run`. Em cPanel isso é uma entrada de cron criada fora do repositório
- Arquivos: `routes/console.php` (o próprio comentário o diz), `docs/production-readiness.md` (*The cron entry*)
- Efeito: sem essa entrada nenhuma das tarefas corre e `audit_logs` cresce sem limite. O repositório não tem como registrar se ela existe em produção — é verificável apenas no servidor, com `php artisan schedule:list` e observando `storage/logs`

## Diversos

**Texto de interface sem sistema de tradução:**
- Problema: o texto das vistas, as mensagens flash e a maioria das mensagens de validação estão escritos literalmente no Blade e nos controladores; não passam por `__()`
- Estado: a variante está fixada e o idioma da aplicação corrigido — `config/app.php` tem `'locale' => env('APP_LOCALE', 'pt_BR')`, `.env.example` tem `APP_LOCALE=pt_BR`, e existem `lang/pt_BR.json` e `lang/pt_BR/validation.php`. A oscilação pt-BR/pt-PT no texto visível foi resolvida
- Resíduo verificável: `resources/views/components/model-viewer.blade.php` (linha 24) ainda diz «o ficheiro continua disponível para baixar» — o único termo pt-PT que sobra em texto visível. Os restantes casos de «ficheiro»/«projecto» estão em comentários de código, que não chegam ao ecrã
- Caminho: extrair as strings só se alguma vez existir um segundo idioma; hoje o custo não compra nada

---

## Fechado desde 22/08/2026

| Preocupação de 22/08 | O que a fechou |
|---|---|
| Papel `financial` como decisão por tomar | Decidido e implementado: o financeiro lê todas as obras e escreve despesas e cobranças em qualquer uma, e nunca toca na estrutura. `AuthorizesProjectAccess` restringe só o `manager`; `routes/web.php` define o âmbito pelos grupos `role:`; `routes/channels.php` e `Admin\MessageController` já concordam com esse modelo. `tests/Feature/FinancialRoleTest.php` fixa-o nos dois sentidos; a decisão está em `docs/production-readiness.md` (*Access and deletion decisions*) |
| Limpeza de mídia em soft delete: nada apagava os arquivos de uma obra excluída | `App\Observers\MediaCleanupObserver` em `forceDeleting` de `Project` e `Plan`, mais o comando `obra360:trash:prune` (retenção de 90 dias) agendado às segundas às 04:30. `tests/Feature/PruneTrashedProjectsTest.php` |
| Apagar mídia em dois discos | As chamadas `Storage::disk('public')->delete()` que seguiam cada exclusão no disco privado foram removidas. Em `app/` só resta uma, no `SettingController`, que apaga a logomarca da empresa — um recurso público de verdade, não mídia legada |
| Coluna `photos.external_url` sem utilização | Removida por migração (`2026_08_24_100000_drop_external_url_from_photos_table.php`). O endereço externo passou a existir onde tem sentido: `plan_versions.external_url` (PLAN-08), com validação e mensagens próprias |
| Sem CSP | `SecurityHeaders` passou a emitir a política, com nonce por requisição registado no Vite e lido pelo `@cspNonce`. `config/security.php` controla ligar/desligar, relato/imposição e origens extra; `CspReportController` recebe as violações. `tests/Feature/ContentSecurityPolicyTest.php` e `InlineScriptFreeViewsTest.php`. A omissão passou a ser imposição em todos os ambientes, produção incluída |
| Listagem de mensagens ordenada em memória | `Admin\MessageController::index()` traz a data da última mensagem numa subconsulta correlacionada, ordena em SQL e pagina (`PROJECTS_PER_PAGE = 20`). `tests/Feature/MessageListPaginationTest.php` |
| Detalhe da obra carregava relações sem limite | `Admin\ProjectController@show` pagina despesas, cobranças, documentos, histórico, mensagens e fotos, uma consulta por separador (`RECORDS_PER_PAGE = 50`, `MESSAGES_PER_PAGE = 100`, `PHOTOS_PER_PAGE = 60`); os totais vêm de agregações em SQL, não da página visível. `tests/Feature/AdminProjectShowLimitsTest.php` |
| Limites de consulta em números mágicos | Todos passaram a constantes nomeadas: `ProjectController::{MESSAGES,PHOTOS,RECORDS}_PER_PAGE`, `MessageController::{PROJECTS_PER_PAGE,CONVERSATION_MESSAGE_LIMIT}`, `Client\ProjectController::{SHOW_MESSAGE_LIMIT,SHOW_PHOTO_LIMIT,CONVERSATION_MESSAGE_LIMIT}`, `DashboardController::{RECENT_PROJECTS_LIMIT,RECENT_UPDATES_LIMIT}`, `ClientLayoutComposer::{UNREAD_PREVIEW_LIMIT,RECENT_UPDATES_LIMIT}` |
| Crescimento das tabelas de sessão, fila e auditoria sem tarefa agendada | `routes/console.php` agenda `auth:clear-resets` (diária), `queue:prune-failed --hours=168` (semanal), `obra360:audit:prune --commit` (diária, retenção de 365 dias) e `obra360:trash:prune --commit` (semanal). A tabela `sessions` é varrida por sorteio pelo próprio Laravel. `tests/Feature/PruneAuditLogsTest.php` |
| Canal de WhatsApp saía em silêncio sem credenciais | `WhatsAppChannel::send()` regista um aviso por notificação não entregue, nomeando qual das duas definições falta e nada mais. `tests/Feature/WhatsAppChannelTest.php` |
| Limitação de taxa só do lado do cliente | As escritas da área interna passaram a ter limite: `throttle:120,1` nas escritas leves, `throttle:30,1` no envio de documentos e plantas, `throttle:20,1` nos envios de fotos e nas rotas com arquivo. `tests/Feature/InternalWriteRateLimitTest.php` |
| 404 ao criar uma obra pela interface | Ordem das rotas corrigida em `routes/web.php` — um segmento literal declarado depois de um parâmetro nunca era alcançado. `tests/Feature/RouteOrderTest.php` fixa a ordem |
| Erros de validação visíveis num só sítio | Componentes `<x-form-errors>` e `<x-field-error>`, com o campo `_form` a distinguir formulários da mesma página. `tests/Feature/ValidationFeedbackTest.php` |

---

*Auditoria de preocupações: 2026-08-24*
