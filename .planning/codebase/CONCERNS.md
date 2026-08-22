# Preocupações do Código

**Data da análise:** 2026-08-22

Este documento lista apenas o que ainda está aberto e é verificável no código. As preocupações fechadas desde a análise de 20/08 estão resumidas no fim.

## Decisões por tomar

**Papel `financial` tratado como gestor:**
- Problema: o `financial` entra na área interna (`role:admin,manager,financial` em `routes/web.php`) e escreve em despesas e pagamentos (`role:admin,financial`), mas o `AuthorizesProjectAccess` restringe-o exactamente da mesma forma que um `manager` — só chega a obras onde `manager_id` é o próprio. Isto é um marcador de posição, não um modelo de acesso decidido
- Ficheiros: `app/Traits/AuthorizesProjectAccess.php` (o próprio comentário diz "If financial users need unrestricted project access, add a financial_id column to projects and update this check accordingly"), `routes/web.php`, `app/Providers/AppServiceProvider.php` (Gate `access-financial`), `docs/production-readiness.md`
- Efeito: na prática, um utilizador `financial` que não seja `manager_id` de nenhuma obra não consegue lançar despesas em obra nenhuma. O `ReportController::index()` aplica-lhe o mesmo filtro. Em contrapartida, `routes/channels.php` autoriza-o em **qualquer** canal `project.{id}`, e o `Admin\MessageController` não lhe aplica restrição nenhuma — o modelo é inconsistente entre superfícies
- Caminho de resolução: decisão de produto sobre o que o papel `financial` deve ver. Depois disso, ou se separa a área financeira das rotas de obra, ou se acrescenta uma coluna própria a `projects` e se alinha o trait, os canais e o `MessageController`

## Dívida técnica

**Registos de media sem ficheiro em produção:**
- Problema: a base de dados de produção foi povoada a partir de um ambiente local e trouxe registos de `photos`, `documents` e capas cujo ficheiro nunca chegou ao servidor
- Ficheiros: `tests/Feature/MissingMediaTest.php` documenta o caso ("Produção tinha oito registos de media cujo ficheiro nunca chegou ao servidor — vieram de uma base de dados de outro ambiente")
- Estado: o sintoma está corrigido no código — `mediaResponse()` em `Admin\ProjectPhotoController` e em `Client\ProjectController`, e os downloads de documento, fazem `abort_unless(Storage::disk('local')->exists($path), 404)` antes de responder, pelo que um registo órfão devolve 404 e não 500. Os ficheiros em falta foram enviados manualmente a 22/08 (acção operacional, sem rasto no repositório)
- Por fazer: não existe verificação automática que detecte registos de media sem ficheiro correspondente. A única ferramenta próxima é `obra360:media:migrate-public`, que assinala `missing_public` ao percorrer os caminhos

**Limpeza de media em soft delete:**
- Problema: `Project`, `Photo`, `Document` e `Expense` usam `SoftDeletes`, mas o ficheiro em disco só é apagado quando o `destroy` do controller corre. Apagar uma obra (`Admin\ProjectController@destroy`) não toca em nenhum ficheiro das suas fotos, recibos ou documentos
- Ficheiros: `app/Http/Controllers/Admin/ProjectController.php` (`destroy` só faz `$project->delete()`), `app/Models/Project.php`, `app/Models/Photo.php`, `app/Models/Document.php`
- Efeito: ficheiros órfãos acumulam-se em `storage/app/private/projects/{id}/`
- Caminho: um observer de `forceDeleted` ou um job agendado de limpeza; a decisão está registada em `docs/production-readiness.md`

**Apagar media em dois discos:**
- Problema: as rotinas de remoção continuam a chamar `Storage::disk('public')->delete()` a seguir a `Storage::disk('local')->delete()`, embora a escrita e a leitura já só usem o disco privado
- Ficheiros: `app/Http/Controllers/Admin/ProjectPhotoController.php` (linhas 58-59), `app/Http/Controllers/Admin/ProjectDocumentController.php` (53-54), `app/Http/Controllers/Admin/ProjectExpenseController.php` (76-77), `app/Http/Controllers/Admin/ProjectController.php` (141-142, substituição de capa)
- Efeito: é limpeza defensiva de ficheiros legados, não um defeito de correcção; passa a ser I/O inútil assim que a migração para o disco privado estiver confirmada em todos os ambientes

**Coluna `photos.external_url` sem utilização:**
- Problema: a coluna existe e está em `#[Fillable]`, mas nada no código a escreve ou a lê
- Ficheiros: `database/migrations/2026_06_27_200001_add_missing_columns.php`, `app/Models/Photo.php`
- Nota: ao contrário de 20/08, `media_type` e `thumbnail_path` **já não são código morto** — são preenchidos no upload, usados em `Project::latestImage()`, na geração de miniaturas e nas rotas de media
- Caminho: implementar media externa ou remover a coluna por migração

**Sem CSP:**
- Problema: o `SecurityHeaders` define nosniff, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `X-Permitted-Cross-Domain-Policies` e HSTS sobre HTTPS, mas não define `Content-Security-Policy`
- Ficheiros: `app/Http/Middleware/SecurityHeaders.php` — o comentário no próprio ficheiro explica porquê: as vistas Blade usam estilos e handlers inline em todo o lado, e uma política com `unsafe-inline` não compraria nada
- Caminho: passagem de nonce sobre as vistas antes de introduzir a política

## Estrangulamentos de desempenho

**Listagem de mensagens ordenada em memória:**
- Problema: `Admin\MessageController::index()` carrega **todas** as obras visíveis, cada uma com a última mensagem, e só depois ordena a colecção com `sortByDesc()` em PHP
- Ficheiros: `app/Http/Controllers/Admin/MessageController.php` (linhas 14-28) — não há paginação nesta rota
- Efeito: o custo cresce linearmente com o número de obras, em memória e em tempo de resposta
- Caminho: trazer a data da última mensagem para a consulta e ordenar em SQL, com paginação

**Detalhe da obra carrega relações sem limite:**
- Problema: `Admin\ProjectController@show` faz `$project->load(['client', 'manager', 'phases', 'expenses', 'documents', 'updates.user', 'messages.user', 'payments'])` — nem `updates` nem `messages` têm limite
- Ficheiros: `app/Http/Controllers/Admin/ProjectController.php` (linha 85)
- Contraste: o lado do cliente já se protege — `Client\ProjectController@show` limita mensagens a 100 e fotos a 200
- Efeito: numa obra com histórico longo, a página interna cresce sem tecto

**Limites de consulta em números mágicos:**
- Problema: 100, 200, 24, 20, 8, 6 e 5 estão escritos directamente no código
- Ficheiros: `app/Http/Controllers/Client/ProjectController.php` (100 e 200), `app/Http/Controllers/Admin/MessageController.php` (200), `app/Http/Controllers/Admin/ReportController.php` (`PDF_PHOTO_LIMIT = 24`), `app/Http/Controllers/Admin/DashboardController.php` (5 e 8), `app/Http/View/Composers/ClientLayoutComposer.php` (6 e 5)
- Nota: `PDF_PHOTO_LIMIT` e `Thumbnail::MAX_EDGE` já são constantes nomeadas; os restantes não

## Limites de escala

**Crescimento das tabelas de sessão, fila e auditoria:**
- Problema: `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` e `CACHE_STORE=database` no `.env.example`, e a nova tabela `audit_logs` recebe uma linha por cada escrita nos nove modelos observados. `routes/console.php` só define o comando `inspire` — **não há nenhuma tarefa agendada** de limpeza
- Ficheiros: `.env.example`, `routes/console.php`, `database/migrations/2026_08_21_100000_create_audit_logs_table.php`
- Efeito: as tabelas crescem indefinidamente; a de auditoria é a que cresce mais depressa
- Caminho: agendar `queue:prune-failed`, recolha de sessões e uma política de retenção para `audit_logs`

**Armazenamento num disco único:**
- Problema: toda a media vive no disco `local` (`storage/app/private`), num alojamento partilhado
- Ficheiros: `config/filesystems.php` (o disco `s3` está declarado), `composer.json`
- Estado: o Flysystem já abstrai o acesso e **todo o código usa `Storage::disk('local')`**, nunca caminhos absolutos — a mudança para S3 é de configuração, não de reescrita. Falta o passo concreto: `league/flysystem-aws-s3-v3` não está instalado (confirmado em `composer.lock`, onde aparece apenas como sugestão do framework), as variáveis `AWS_*` do `.env.example` estão vazias, e as rotas de media servem os bytes através do PHP em vez de URLs assinados
- Caminho: instalar o driver, apontar o disco, correr uma migração de ficheiros equivalente à do disco público e decidir entre continuar a servir por rota autenticada ou passar a URLs assinados

## Dependências em risco

**Entrega por WhatsApp:**
- Problema: o canal depende da Meta Graph API (`WHATSAPP_PHONE_ID`, `WHATSAPP_TOKEN`)
- Ficheiros: `app/Channels/WhatsAppChannel.php`
- Estado real: quando as credenciais faltam, o canal **sai em silêncio, sem registar nada** (linhas 21-23) — não há sinal de que a notificação não foi entregue. Quando a API responde com erro, o canal regista um aviso com o telefone em hash e lança, para o worker tentar de novo; falhas de ligação também são relançadas. Uma falha permanente da API acaba em job falhado, sem alerta
- Caminho: registar quando as credenciais faltam e monitorizar a tabela `failed_jobs`
- Mitigação já presente: o `ProjectUpdateNotification` acrescenta o canal `mail` sempre que o destinatário tem um e-mail válido, pelo que o WhatsApp deixou de ser o único caminho

## Diversos

**Texto de interface sem sistema de tradução:**
- Problema: o texto das vistas, as mensagens flash e a maioria das mensagens de validação estão escritos literalmente em português no Blade e nos controllers; não passam por `__()`
- Ficheiros: todas as vistas de `resources/views/`, os `->with('success', '...')` dos controllers
- Estado: existe `lang/pt_BR.json` com as mensagens do framework, de autenticação e de validação — o mecanismo está montado, mas só cobre essa camada. `APP_LOCALE` no `.env.example` está a `en`
- Nota adicional: a ortografia oscila entre pt-BR ("Usuário", "Atualização", "Configurações salvas") e pt-PT ("Utilizador", "Actualização", "Equipa"), inclusive dentro do mesmo fluxo — `ProjectUpdateNotification::toWhatsApp()` escreve "Actualização" enquanto `ProjectUpdate::TYPES` diz "Atualização geral"
- Caminho: fixar a variante e extrair as strings

**Limitação de taxa só do lado do cliente:**
- Problema: as rotas de escrita do portal estão limitadas (`throttle:20,1` nas mensagens, `throttle:60,1` em marcar como lidas, `throttle:10,1` e `throttle:30,1` nas subscrições push, `throttle:6,1` na verificação de e-mail), mas **nenhuma rota de escrita da área interna** tem limite — upload de fotos, documentos, despesas, pagamentos e fases não têm `throttle`
- Ficheiros: `routes/web.php`
- Efeito: o vector é interno (exige um utilizador `admin`, `manager` ou `financial` autenticado), mas um upload em massa não tem tecto
- Caminho: aplicar `throttle` às rotas POST/PUT/DELETE com upload

---

## Fechado desde 20/08/2026

| Preocupação de 20/08 | O que a fechou |
|---|---|
| Não havia edição de despesas | `admin.projects.expenses.update` em `routes/web.php` → `Admin\ProjectExpenseController@update`, com `tests/Feature/ExpenseUpdateTest.php` |
| Não havia exportação PDF | `barryvdh/laravel-dompdf` v3.1.2, `Admin\ReportController` com quatro tipos de relatório e `@pdf`, vistas em `resources/views/admin/reports/`, `tests/Feature/ReportTest.php` |
| Não havia trilho de auditoria | Tabela `audit_logs`, `App\Models\AuditLog`, `App\Observers\AuditObserver` sobre nove modelos, `/admin/auditoria` (`Admin\AuditLogController`), `tests/Feature/AuditLogTest.php` |
| `photos.thumbnail_path` nunca preenchida | `App\Support\Thumbnail` (GD puro), preenchida no upload, comando `obra360:photos:thumbnails` para o histórico, `tests/Feature/PhotoGalleryTest.php` |
| Definição `currency` nunca aplicada | `money()` e `currency_symbol()` em `app/Support/helpers.php`, carregados por `autoload.files` e usados em todas as vistas que mostram valores |
| Fallback de disco `local` → `public` na leitura de media | Removido. `mediaResponse()` lê só do disco privado e devolve 404 quando o ficheiro não existe; comando `obra360:media:migrate-public` migra capas, fotos, miniaturas e documentos; `tests/Feature/MissingMediaTest.php` e `MigratePublicMediaTest.php` |
| `ProjectController` com 400 linhas | Dividido em `ProjectController` (206 linhas, CRUD + galeria filtrada + actualizações), `ProjectPhaseController`, `ProjectPhotoController`, `ProjectExpenseController` e `ProjectDocumentController` |
| Lógica de negócio sem testes | A suite tem hoje **221 testes**, com cobertura de obras, fases, despesas, pagamentos, fotos, documentos, relatórios, auditoria, notificações e KPIs |
| Sem mecanismo automático de actualização de dependências | `.github/dependabot.yml` (composer, npm e github-actions, semanal/mensal, com grupo `security-and-patches`) |
| Progresso da obra sem modo configurável | Definição `progress_mode` (`derivado` \| `manual`), `Project::progressIsManual()` e `recalcProgress()`, `tests/Feature/ProgressModeTest.php` (FASE-04) |
| `minishlink/web-push` não instalado, canal era um no-op | Instalado (v8.0.0); o `WebPushChannel` entrega quando há chaves VAPID e subscrições |
| 500 no portal do cliente depois de um deploy | `ClientLayoutComposer` deixou de guardar modelos Eloquent na cache; guarda só escalares e arrays de escalares e valida a forma do que lê, com `tests/Feature/ClientLayoutCacheTest.php` a reproduzir o `__PHP_Incomplete_Class` |

---

*Auditoria de preocupações: 2026-08-22*
