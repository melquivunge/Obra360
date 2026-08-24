---
gsd_state_version: 1.0
milestone: v1.0
milestone_name: milestone
status: executing
stopped_at: v1 completo e serviços de arquitetura/design entregues; falta a migração para S3 e passar a CSP a imposição
last_updated: "2026-08-24"
last_activity: 2026-08-24 -- orçamento por linhas comparado com o gasto real (#34)
progress:
  total_phases: 6
  completed_phases: 6
  total_plans: 8
  completed_plans: 8
  percent: 100
---

# Estado do Projeto

## Referência

Ver: `.planning/PROJECT.md` (atualizado em 2026-08-21)
Requisitos da funcionalidade nova: `.planning/requisitos-arquitetura-design.md` (2026-08-23)

**Valor central:** o cliente entra, vê imediatamente o estado atual do que contratou — fase, percentual, fotos recentes, dinheiro gasto — e sente-se informado, sem precisar ligar para a empresa.

**Foco atual:** o produto deixou de ser só acompanhamento de obra. Passou a cobrir também os serviços de projeto — arquitetura e design de interiores — com plantas versionadas, aprovação pelo cliente e continuidade até a obra.

## Posição atual

Fases 1 a 6 entregues. Os 83 requisitos v1 estão feitos, 0 parciais, 0 por construir.
Sobre eles foram entregues os 27 requisitos dos serviços de arquitetura e design (TIPO, PLAN, PORT, OBRA, LIM) e dois requisitos v2 (ADV-03, ADV-04).

Progresso v1: [██████████] 100%

**Suíte de testes: 540 testes, 1609 asserções, todos passando** (`php artisan test`, 2026-08-24). Eram 192 em 22/08.

> **Modo de trabalho:** as fases 2 a 6 e todo o trabalho posterior a 21/08 foram
> construídos diretamente, fora do fluxo GSD, por decisão do usuário. Não existem
> artefatos PLAN.md/SUMMARY.md para eles. O que está abaixo foi reconstruído a
> partir do código e do histórico de commits.

## O que foi construído entre 23/08 e 24/08

Vinte commits, do `8161493` ao `78d0f59`.

### Serviços de arquitetura e design de interiores — completo

| Commit | Entregue |
|--------|----------|
| `c95c51d` | Tipo de projeto: `Project::TYPES` (construção, remodelação, projeto de arquitetura, design de interiores), `SITE_TYPES`, `SOURCE_TYPES`, migração com `construcao` por omissão, filtro por tipo na listagem (TIPO-01..05) |
| `ca99568` | Plantas com versões numeradas na área interna: `Plan`, `PlanVersion`, `Admin\ProjectPlanController`, `Admin\ProjectPlanVersionController`, disco privado, treze formatos aceitos, endereço externo em alternativa ao arquivo, soft delete que preserva o histórico (PLAN-01..09) |
| `5b3e97d` | Portal do cliente: ver, baixar e aprovar. Recusa por 404 o que ainda não lhe foi enviado, pré-visualização de PDF e imagens no navegador, aprovação sob `lockForUpdate` que grava quem e quando, entra no histórico e na auditoria (PORT-01..06) |
| `4a92f6c` | `projects.source_project_id`: a obra fica ligada ao projeto que lhe deu origem, mostra as plantas aprovadas dele sem copiar arquivos, e a continuidade aparece nos dois sentidos, no portal inclusive (OBRA-01..04) |
| `cc566bf` | Comentários presos a cada versão (`PlanComment`) e contagem das revisões incluídas no contrato (`projects.revisions_included`, `revisionsUsed()`, `revisionsRemaining()`, `revisionLimitExceeded()`) |
| `9baed6c` | Visualizador 3D no navegador para GLB e GLTF (`<x-model-viewer>`, `@google/model-viewer` carregado sob demanda) |
| `d93782c` | Etapas de entrega pré-definidas por tipo (`App\Support\PhaseTemplates`), as de arquitetura seguindo a NBR 13532 |

Os limites do servidor (LIM-01..03) estão cobertos: `PlanVersion::maxUploadKilobytes()` lê as duas diretivas do PHP em tempo de execução e alimenta a validação, o texto do formulário e as mensagens de erro; `App\Http\Middleware\ExplainTruncatedUpload` e `resources/views/errors/413.blade.php` explicam os três modos de falha; o procedimento de cPanel, com custo e risco, está em `docs/production-readiness.md`.

### Correções e dívida fechada

| Commit | Fechado |
|--------|---------|
| `8161493` | Ordem das rotas — criar uma obra pela interface dava 404 — e limite de taxa em todas as escritas da área interna |
| `01e98a6` | Tarefas agendadas: `auth:clear-resets`, `queue:prune-failed`, `obra360:audit:prune` (365 dias) e `obra360:trash:prune` (90 dias, com remoção dos arquivos do disco) |
| `5e5082f`, `6925443` | Capa da obra visível nas páginas da obra; imagem padrão para quem não tem capa ou tem o arquivo em falta |
| `b3cdef9` | Erros de validação em toda a área interna — `<x-form-errors>` e `<x-field-error>`, com `_form` a distinguir formulários da mesma página |
| `d75bc4d` | Vocabulário da área interna segue o tipo do registro: «obra» para construção e remodelação, «projeto» para arquitetura e design |
| `c40d93d` | Interface padronizada em português do Brasil e `APP_LOCALE=pt_BR` — o arquivo de traduções nunca era lido |
| `5f74374` | Paginação da lista de mensagens e de todos os separadores da página da obra, com a ordenação movida para SQL |
| `27ce944` | Limpeza dos arquivos na exclusão definitiva, aviso do canal de WhatsApp sem credenciais, remoção da coluna morta `photos.external_url` |
| `5c8992f` | Content-Security-Policy com nonce por requisição, imposta em local e nos testes, em modo de relato em produção |
| `079d889` | Papel financeiro decidido e implementado: lê todas as obras, escreve despesas e cobranças em qualquer uma, não toca na estrutura |
| `e030950` | ADV-03 — o cliente envia fotos e documentos pelo portal, com interruptor nas configurações |
| `78d0f59` | ADV-04 — orçamento por linhas (`BudgetItem`), comparado com o gasto real por categoria |

## Trabalho em aberto

1. **Migração para S3.** `league/flysystem-aws-s3-v3` não está instalado — em `composer.lock` aparece só como sugestão do framework — e as variáveis `AWS_*` do `.env.example` estão vazias. À espera de credenciais. O código já usa `Storage::disk('local')` em todo o lado, nunca caminhos absolutos, portanto a mudança é de configuração.
2. **Passar a CSP a imposição em produção.** Hoje `config/security.php` põe `report_only` a verdadeiro quando `APP_ENV=production`. Falta observar os relatórios em `storage/logs` e pôr `CSP_REPORT_ONLY=false`.
3. **Assimetria na remoção de arquivos.** Excluir uma obra ou uma planta guarda os arquivos até o prazo de retenção; excluir uma foto, um documento ou uma despesa continua a apagar o arquivo no mesmo instante, apesar de a linha ser só soft delete.
4. **Notificar a outra parte quando há comentário novo numa planta.** Hoje o comentário é gravado e mais nada — nem histórico, nem notificação.
5. **`style-src` mantém `'unsafe-inline'`** (atributos `style=` nas vistas) **e `script-src` mantém `'unsafe-eval'`** (Alpine 3 avalia as expressões com `new Function`). As duas cedências estão documentadas no próprio middleware.
6. **Multi-empresa/SaaS (ADV-02) e app móvel (INT-03)** continuam em escopo v2, não iniciados.

## Contexto acumulado

### Decisões

- Um serviço de projeto é um **tipo** na tabela `projects`, não uma entidade separada — evita duplicar documentos, cobranças, mensagens, portal e relatórios.
- Uma planta é uma entidade própria com versões numeradas e aprovação. `plan_versions` não usa soft delete de propósito: uma versão substituída continua legível para sempre.
- A obra **fica ligada** ao projeto de origem e lê as plantas aprovadas onde elas vivem. Nada é copiado, portanto nada diverge.
- Arquivos 3D: baixar sempre, visualizar só formatos leves (GLB, GLTF). Converter formatos proprietários no servidor é inviável em alojamento compartilhado.
- O limite de envio fica nos 64 MB e o endereço externo é o caminho normal para Revit, SketchUp e IFC grandes — a alternativa custa concorrência de processos PHP-FPM, não disco.
- O financeiro tem âmbito definido pelo middleware `role:`, não por obra: ele nunca é gestor de obra nenhuma, e restringi-lo por `manager_id` dava 403 nas rotas que lhe são explicitamente atribuídas.
- Os arquivos só saem do disco quando a exclusão passa a definitiva, num observador de `forceDeleting` e não no comando — o `forceDelete` pode vir de qualquer lado.

### Blockers/Concerns

- Nenhuma tarefa agendada corre sem uma entrada de cron criada à mão em produção, fora do repositório. Ver `docs/production-readiness.md`.
- A tabela `cache` não é varrida por nada: o Laravel não traz limpeza em massa para o store de base de dados.

## Continuidade

Última sessão: 2026-08-24
Parado em: orçamento por linhas entregue; documentos de planejamento atualizados ao estado real.

Próximo passo: instalar o driver de S3 quando houver credenciais, e observar os relatórios de CSP em produção antes de passar a imposição.
