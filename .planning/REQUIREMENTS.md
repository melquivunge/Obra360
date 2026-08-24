# Requirements: Portal de Acompanhamento de Obras (Obra360)

**Defined:** 2026-06-27
**Core Value:** A client logs in, immediately sees the current state of their construction project (phase, progress %, recent photos, money spent), and feels informed — without needing to contact the company.

## v1 Requirements

### Authentication

- [x] **AUTH-01**: User can log in with email and password and be redirected to the correct dashboard based on their role
- [x] **AUTH-02**: User can log out from any page and session is destroyed
- [x] **AUTH-03**: User can request password reset via email link and set a new password
- [x] **AUTH-04**: Inactive users cannot log in and see an appropriate error message
- [x] **AUTH-05**: Passwords are stored hashed (bcrypt); plain-text passwords are never stored

### User Management

- [x] **USR-01**: Administrator can create a user with name, email, phone, role (admin/manager/financial/client), and password
- [x] **USR-02**: Administrator can edit user details and change their role
- [x] **USR-03**: Administrator can activate or deactivate a user account
- [x] **USR-04**: Administrator can delete a user (soft delete with confirmation)
- [x] **USR-05**: System enforces 4 roles: Administrador, Gestor da Obra, Financeiro, Cliente

### Client Management

- [x] **CLI-01**: Administrator can create a client with name/razão social, email, phone, fiscal document (CPF/CNPJ/NIF), address, city, country, notes, and status
- [x] **CLI-02**: Administrator can edit client details
- [x] **CLI-03**: Administrator can activate or deactivate a client
- [x] **CLI-04**: A client account can be linked to one or more user logins
- [x] **CLI-05**: Client users can only see data linked to their client account

### Project Management

- [x] **PROJ-01**: Administrator can create a project (obra) with: name, internal code, linked client, location, description, start date, expected end date, total budget, status, progress %, responsible manager, and notes
- [x] **PROJ-02**: Administrator can edit project details
- [x] **PROJ-03**: Project status can be: Planejada, Em andamento, Pausada, Aguardando aprovação, Concluída, Cancelada
- [x] **PROJ-04**: Administrator can soft-delete a project with confirmation
- [x] **PROJ-05**: Manager (Gestor) can view and update projects assigned to them
- [x] **PROJ-06**: Projects list can be filtered by client and by status

### Phase Management

- [x] **FASE-01**: Administrator/Manager can create phases for a project with: name, description, status, percentage, sort order, start date, expected end date, real end date
- [x] **FASE-02**: Phase status can be: Pendente, Em andamento, Concluída, Pausada, Aguardando aprovação
- [x] **FASE-03**: Phases are displayed in configurable sort order
- [x] **FASE-04**: Progress percentage can be updated manually or derived from completed phases (configurable per system settings)
- [x] **FASE-05**: Changing a phase status triggers an automatic entry in the project update history

### Photo Management

- [x] **FOTO-01**: Administrator/Manager can upload photos (JPG, JPEG, PNG, WebP) linked to a project and optionally to a phase
- [x] **FOTO-02**: Each photo has a title/caption, description, date, and the user who uploaded it
- [x] **FOTO-03**: System generates thumbnails on upload for gallery performance
- [x] **FOTO-04**: Photos are displayed in a gallery filterable by date, phase, most recent, oldest
- [x] **FOTO-05**: Client can only view photos of their own projects
- [x] **FOTO-06**: Upload validates file type and enforces a maximum file size

### Expense Management

- [x] **DESP-01**: Administrator/Financial can register an expense for a project with: category, description, amount, date, supplier, optional receipt file, notes
- [x] **DESP-02**: Expense categories: Material, Mão de obra, Transporte, Equipamentos, Documentação, Licenças, Serviços terceirizados, Alimentação, Outros
- [x] **DESP-03**: Administrator/Financial can edit an expense
- [x] **DESP-04**: Administrator/Financial can delete an expense (soft delete with confirmation)
- [x] **DESP-05**: Uploading a receipt file is optional and validated for allowed types

### Financial Summary

- [x] **FIN-01**: System automatically calculates: total spent (sum of all project expenses), remaining balance (budget − total spent), % of budget used
- [x] **FIN-02**: Financial summary shows breakdown by expense category
- [x] **FIN-03**: Financial summary shows breakdown by month
- [x] **FIN-04**: If budget is zero or null, financial section renders without crashing
- [x] **FIN-05**: System settings control whether clients can see the financial summary (toggle per company config)
- [x] **FIN-06**: Currency formatting uses the currency configured in system settings

### Update History

- [x] **UPD-01**: System automatically creates a history entry when: a photo is added, a phase changes, a document is added, project status changes, an expense is registered
- [x] **UPD-02**: Administrator/Manager can create a manual update (title, description, date, type)
- [x] **UPD-03**: Update types: Atualização geral, Nova foto, Nova despesa, Mudança de fase, Documento adicionado, Alteração de status
- [x] **UPD-04**: Client can see the full update history for their projects

### Document Management

- [x] **DOC-01**: Administrator/Manager can upload documents (PDF, DOCX, XLSX, JPG, PNG) linked to a project
- [x] **DOC-02**: Each document has: name, type (Contrato/Orçamento/Planta/Comprovante/Licença/Relatório/Recibo/Outros), visibility (Interno/Cliente), date, notes
- [x] **DOC-03**: Documents marked "Interno" are not visible to clients
- [x] **DOC-04**: Client can view and download documents marked "Cliente" for their projects only
- [x] **DOC-05**: Upload validates file type and size

### Comments / Messaging

- [x] **MSG-01**: Client can send a question/comment linked to a specific project
- [x] **MSG-02**: Administrator/Manager can reply to a client message within the project
- [x] **MSG-03**: All messages are threaded per project and visible to both parties
- [x] **MSG-04**: Comment functionality can be enabled or disabled via system settings

### Dashboards

- [x] **DASH-01**: Admin dashboard shows KPI cards: total projects, active, completed, paused; total clients; total budgeted; total spent; projects with delays; projects near completion
- [x] **DASH-02**: Admin dashboard shows recent: expenses, photos, updates
- [x] **DASH-03**: Client dashboard shows cards for each of their projects with: name, location, status, current phase, progress %, total spent, last update date, main photo, detail button
- [x] **DASH-04**: Client project detail page shows: name, location, description, status, current phase, progress %, dates, financial summary, recent photos, full gallery, phase list, update history, released documents, comments (if enabled)

### Email Notifications

- [x] **NOTF-01**: System sends email to client when: new photo added, new update published, phase changed, document released to client, project completed
- [x] **NOTF-02**: Administrator can enable or disable email notifications per event type
- [x] **NOTF-03**: Client only receives notifications for their own projects

### Reports

- [x] **REL-01**: System generates a general project report (summary, phases, status) viewable on screen and exportable as PDF
- [x] **REL-02**: System generates a financial report (expenses by category, by month, totals) viewable on screen and exportable as PDF
- [x] **REL-03**: System generates a photo report (gallery list with dates and phases) viewable on screen and exportable as PDF
- [x] **REL-04**: System generates a phases report (phase status and dates) viewable on screen and exportable as PDF

### System Settings

- [x] **CONF-01**: Administrator can configure: company name, logo, default currency, contact info, email send address
- [x] **CONF-02**: Administrator can enable/disable client comments
- [x] **CONF-03**: Administrator can enable/disable client financial summary visibility
- [x] **CONF-04**: Administrator can enable/disable email notifications per event type

### Security & Cross-Cutting

- [x] **SEC-01**: All sensitive routes are protected by auth middleware and role-based policies/gates
- [x] **SEC-02**: Client users cannot access any project, photo, document, or financial data belonging to other clients
- [x] **SEC-03**: CSRF protection enabled on all forms
- [x] **SEC-04**: Upload validation enforces allowed MIME types and maximum file sizes
- [x] **SEC-05**: Activity log records important actions: project create/edit, photo upload, expense register, document upload, status changes, deletions
- [x] **SEC-06**: Soft delete used on: projects, clients, users, expenses, documents, photos
- [x] **SEC-07**: All pages paginate large lists

### UI / UX

- [x] **UX-01**: All screens are responsive and work correctly on mobile, tablet, and desktop
- [x] **UX-02**: Admin layout has sidebar navigation with: Dashboard, Clientes, Obras, Fases, Fotos, Despesas, Documentos, Relatórios, Usuários, Configurações, Sair
- [x] **UX-03**: Client layout has minimal navigation: Minhas Obras, Documentos, Mensagens, Meu Perfil, Sair
- [x] **UX-04**: UI uses cards, progress bars, color-coded statuses, photo gallery, clean tables, and clear action buttons
- [x] **UX-05**: All UI text and labels are in Portuguese (PT)

## Serviços de arquitetura e design de interiores

*Definidos em 2026-08-23 (`.planning/requisitos-arquitetura-design.md`), entregues entre 23 e 24/08/2026. Escritos em português do Brasil, como o resto da interface.*

### Tipo de projeto

- [x] **TIPO-01**: um projeto tem um tipo entre construção, remodelação, arquitetura e design de interiores (`Project::TYPES`)
- [x] **TIPO-02**: todos os projetos existentes ficam como construção, sem intervenção manual (`default('construcao')` na migração `2026_08_23_100000`)
- [x] **TIPO-03**: o tipo é escolhido na criação e pode ser alterado na edição
- [x] **TIPO-04**: o tipo é visível na listagem e na página do projeto, no portal do cliente inclusive (`<x-project-type>`)
- [x] **TIPO-05**: a listagem pode ser filtrada por tipo (`Admin\ProjectController::index`)

### Plantas

- [x] **PLAN-01**: o arquiteto cria uma planta num projeto, com nome e disciplina (`Plan::DISCIPLINES`)
- [x] **PLAN-02**: cada planta tem versões numeradas a partir de 1; um arquivo novo cria a versão seguinte (`Plan::nextVersionNumber()`)
- [x] **PLAN-03**: uma versão guarda arquivo, nome original, formato, tamanho, quem carregou e nota de alterações
- [x] **PLAN-04**: a versão mais recente é a corrente; as anteriores ficam acessíveis e marcadas como substituídas (`superseded_at`, `PlanVersion::isSuperseded()`)
- [x] **PLAN-05**: uma planta tem estado — em elaboração, enviada ao cliente, aprovada, substituída (`Plan::STATUSES`)
- [x] **PLAN-06**: os arquivos vivem no disco privado e só saem por rota autenticada
- [x] **PLAN-07**: treze formatos aceitos — PDF, JPG, JPEG, PNG, WEBP, DWG, DXF, SKP, RVT, IFC, GLB, GLTF, ZIP (`PlanVersion::FORMATS`)
- [x] **PLAN-08**: uma versão pode guardar um endereço externo em vez do arquivo (`plan_versions.external_url`)
- [x] **PLAN-09**: excluir uma planta é reversível e não apaga o histórico de versões (`SoftDeletes` em `Plan`, ausente de propósito em `PlanVersion`)

### Portal do cliente

- [x] **PORT-01**: o cliente vê apenas as plantas que lhe foram enviadas; o que está em elaboração responde 404, não 403
- [x] **PORT-02**: o cliente baixa a versão corrente e as anteriores
- [x] **PORT-03**: PDF e imagens são vistos no navegador sem baixar (`Client\PlanController::preview`)
- [x] **PORT-04**: o cliente aprova a versão corrente; a aprovação grava quem e quando, sob `lockForUpdate`
- [x] **PORT-05**: a aprovação entra no histórico do projeto (`ProjectUpdate::log(..., 'planta_aprovada')`) e no registro de auditoria (`AuditObserver` sobre `PlanVersion`)
- [x] **PORT-06**: uma versão aprovada não é aprovada outra vez; uma versão substituída não pode ser aprovada

### Ligação à obra

- [x] **OBRA-01**: ao criar uma obra é possível ligá-la a um projeto de arquitetura ou design do mesmo cliente (`projects.source_project_id`)
- [x] **OBRA-02**: a obra mostra as plantas aprovadas da origem sem copiar arquivos (`Project::inheritedApprovedPlans()`)
- [x] **OBRA-03**: a partir do projeto vê-se a obra que dele resultou (`Project::derivedProjects()`)
- [x] **OBRA-04**: o cliente vê a continuidade nos dois sentidos no portal

### Limites do servidor

- [x] **LIM-01**: o limite real de envio é apresentado antes de o envio falhar (`PlanVersion::maxUploadLabel()`, lido das diretivas do PHP em tempo de execução)
- [x] **LIM-02**: um arquivo acima do limite explica o limite e sugere o endereço externo, nos três modos de falha (`overLimitMessage()`, `ExplainTruncatedUpload`, `errors/413.blade.php`)
- [x] **LIM-03**: o procedimento de cPanel está documentado, com custo e risco (`docs/production-readiness.md`)

## v2 Requirements

### Integrations

- **INT-01**: WhatsApp integration for update notifications
- **INT-02**: Cloud storage (Amazon S3 / DigitalOcean Spaces / Cloudflare R2) for file uploads
- **INT-03**: Mobile app (iOS/Android)

### Advanced Features

- **ADV-01**: Real-time notifications via WebSockets/Pusher
- **ADV-02**: Multi-company / SaaS billing mode
- [x] **ADV-03**: Client can upload photos or documents (bidirectional) — entregue em 2026-08-24 (`app/Http/Controllers/Client/UploadController.php`, configuração `allow_client_uploads`, `tests/Feature/ClientUploadTest.php`)
- [x] **ADV-04**: Project budgeting line items (beyond single total budget) — entregue em 2026-08-24 (`App\Models\BudgetItem`, `Admin\ProjectBudgetItemController`, `Project::budgetVersusSpent()`, `tests/Feature/BudgetItemTest.php`)

## Out of Scope

| Feature | Reason |
|---------|--------|
| WhatsApp integration | RNF06 — future milestone, high complexity |
| Mobile app | Future milestone |
| Cloud storage (S3 etc.) | Architecture prepared but local storage for v1 |
| Real-time push notifications | Email only in v1 |
| Multi-tenant SaaS billing | Single-company installation |
| OAuth / social login | Email+password sufficient for v1 |
| Client uploading files | ~~Company controls content in v1~~ — revertido: entregue como ADV-03 em 2026-08-24, com interruptor nas configurações |

## Traceability

*Filled by roadmapper agent — 2026-06-27. Statuses reconciled against the codebase — 2026-08-20.*

| Requirement | Phase | Status |
|-------------|-------|--------|
| AUTH-01 | Phase 1 | ✅ Done |
| AUTH-02 | Phase 1 | ✅ Done |
| AUTH-03 | Phase 1 | ✅ Done |
| AUTH-04 | Phase 1 | ✅ Done |
| AUTH-05 | Phase 1 | ✅ Done |
| USR-01 | Phase 1 | ✅ Done |
| USR-02 | Phase 1 | ✅ Done |
| USR-03 | Phase 1 | ✅ Done |
| USR-04 | Phase 1 | ✅ Done |
| USR-05 | Phase 1 | ✅ Done |
| CLI-01 | Phase 2 | ✅ Done |
| CLI-02 | Phase 2 | ✅ Done |
| CLI-03 | Phase 2 | ✅ Done |
| CLI-04 | Phase 2 | ✅ Done |
| CLI-05 | Phase 4 | ✅ Done |
| PROJ-01 | Phase 2 | ✅ Done |
| PROJ-02 | Phase 2 | ✅ Done |
| PROJ-03 | Phase 2 | ✅ Done |
| PROJ-04 | Phase 2 | ✅ Done |
| PROJ-05 | Phase 2 | ✅ Done |
| PROJ-06 | Phase 2 | ✅ Done |
| FASE-01 | Phase 2 | ✅ Done |
| FASE-02 | Phase 2 | ✅ Done |
| FASE-03 | Phase 2 | ✅ Done |
| FASE-04 | Phase 2 | ✅ Done |
| FASE-05 | Phase 2 | ✅ Done |
| FOTO-01 | Phase 3 | ✅ Done |
| FOTO-02 | Phase 3 | ✅ Done |
| FOTO-03 | Phase 3 | ✅ Done |
| FOTO-04 | Phase 3 | ✅ Done |
| FOTO-05 | Phase 3 | ✅ Done |
| FOTO-06 | Phase 3 | ✅ Done |
| DESP-01 | Phase 3 | ✅ Done |
| DESP-02 | Phase 3 | ✅ Done |
| DESP-03 | Phase 3 | ✅ Done |
| DESP-04 | Phase 3 | ✅ Done |
| DESP-05 | Phase 3 | ✅ Done |
| FIN-01 | Phase 3 | ✅ Done |
| FIN-02 | Phase 3 | ✅ Done |
| FIN-03 | Phase 3 | ✅ Done |
| FIN-04 | Phase 3 | ✅ Done |
| FIN-05 | Phase 3 | ✅ Done |
| FIN-06 | Phase 3 | ✅ Done |
| UPD-01 | Phase 3 | ✅ Done |
| UPD-02 | Phase 3 | ✅ Done |
| UPD-03 | Phase 3 | ✅ Done |
| UPD-04 | Phase 3 | ✅ Done |
| DOC-01 | Phase 3 | ✅ Done |
| DOC-02 | Phase 3 | ✅ Done |
| DOC-03 | Phase 3 | ✅ Done |
| DOC-04 | Phase 3 | ✅ Done |
| DOC-05 | Phase 3 | ✅ Done |
| MSG-01 | Phase 4 | ✅ Done |
| MSG-02 | Phase 4 | ✅ Done |
| MSG-03 | Phase 4 | ✅ Done |
| MSG-04 | Phase 4 | ✅ Done |
| DASH-01 | Phase 2 | ✅ Done |
| DASH-02 | Phase 2 | ✅ Done |
| DASH-03 | Phase 4 | ✅ Done |
| DASH-04 | Phase 4 | ✅ Done |
| NOTF-01 | Phase 4 | ✅ Done |
| NOTF-02 | Phase 4 | ✅ Done |
| NOTF-03 | Phase 4 | ✅ Done |
| REL-01 | Phase 5 | ✅ Done |
| REL-02 | Phase 5 | ✅ Done |
| REL-03 | Phase 5 | ✅ Done |
| REL-04 | Phase 5 | ✅ Done |
| CONF-01 | Phase 5 | ✅ Done |
| CONF-02 | Phase 5 | ✅ Done |
| CONF-03 | Phase 5 | ✅ Done |
| CONF-04 | Phase 5 | ✅ Done |
| SEC-01 | Phase 1 | ✅ Done |
| SEC-02 | Phase 4 | ✅ Done |
| SEC-03 | Phase 1 | ✅ Done |
| SEC-04 | Phase 3 | ✅ Done |
| SEC-05 | Phase 5 | ✅ Done |
| SEC-06 | Phase 1 | ✅ Done |
| SEC-07 | Phase 2 | ✅ Done |
| UX-01 | Phase 1 | ✅ Done |
| UX-02 | Phase 1 | ✅ Done |
| UX-03 | Phase 1 | ✅ Done |
| UX-04 | Phase 1 | ✅ Done |
| UX-05 | Phase 1 | ✅ Done |
| TIPO-01 | Fora do roadmap (23-24/08) | ✅ Feito |
| TIPO-02 | Fora do roadmap (23-24/08) | ✅ Feito |
| TIPO-03 | Fora do roadmap (23-24/08) | ✅ Feito |
| TIPO-04 | Fora do roadmap (23-24/08) | ✅ Feito |
| TIPO-05 | Fora do roadmap (23-24/08) | ✅ Feito |
| PLAN-01 | Fora do roadmap (23-24/08) | ✅ Feito |
| PLAN-02 | Fora do roadmap (23-24/08) | ✅ Feito |
| PLAN-03 | Fora do roadmap (23-24/08) | ✅ Feito |
| PLAN-04 | Fora do roadmap (23-24/08) | ✅ Feito |
| PLAN-05 | Fora do roadmap (23-24/08) | ✅ Feito |
| PLAN-06 | Fora do roadmap (23-24/08) | ✅ Feito |
| PLAN-07 | Fora do roadmap (23-24/08) | ✅ Feito |
| PLAN-08 | Fora do roadmap (23-24/08) | ✅ Feito |
| PLAN-09 | Fora do roadmap (23-24/08) | ✅ Feito |
| PORT-01 | Fora do roadmap (23-24/08) | ✅ Feito |
| PORT-02 | Fora do roadmap (23-24/08) | ✅ Feito |
| PORT-03 | Fora do roadmap (23-24/08) | ✅ Feito |
| PORT-04 | Fora do roadmap (23-24/08) | ✅ Feito |
| PORT-05 | Fora do roadmap (23-24/08) | ✅ Feito |
| PORT-06 | Fora do roadmap (23-24/08) | ✅ Feito |
| OBRA-01 | Fora do roadmap (23-24/08) | ✅ Feito |
| OBRA-02 | Fora do roadmap (23-24/08) | ✅ Feito |
| OBRA-03 | Fora do roadmap (23-24/08) | ✅ Feito |
| OBRA-04 | Fora do roadmap (23-24/08) | ✅ Feito |
| LIM-01 | Fora do roadmap (23-24/08) | ✅ Feito |
| LIM-02 | Fora do roadmap (23-24/08) | ✅ Feito |
| LIM-03 | Fora do roadmap (23-24/08) | ✅ Feito |
| ADV-03 | Fora do roadmap (24/08) | ✅ Feito |
| ADV-04 | Fora do roadmap (24/08) | ✅ Feito |

**Cobertura (reconciliada em 2026-08-24):**

- Requisitos v1: 83 — ✅ 83 feitos, 🟡 0 parciais, ❌ 0 por construir
- Serviços de arquitetura e design: 27 — ✅ 27 feitos (TIPO 5, PLAN 9, PORT 6, OBRA 4, LIM 3)
- Requisitos v2 entregues antes do tempo: ADV-03 e ADV-04
- **Total verificado no código: 112 requisitos feitos**

As cinco fases do roadmap estão entregues. FASE-04 fechou em 2026-08-22: a
configuração `progress_mode` alterna entre o progresso derivado da média das
fases e um valor digitado à mão no formulário do projeto.

Quatro itens que `.planning/requisitos-arquitetura-design.md` tinha listado como
fora do âmbito foram entregues logo a seguir, sem identificador próprio:
comentários por versão de planta (`PlanComment`), contagem das revisões
incluídas no contrato (`projects.revisions_included`), visualizador 3D no
navegador para GLB e GLTF (`<x-model-viewer>`) e etapas de entrega
pré-definidas por tipo (`App\Support\PhaseTemplates`, arquitetura seguindo a
NBR 13532).

A suíte cobre 540 testes com 1609 asserções, todos passando em 2026-08-24.

## Scope Changes Since Definition

Delivered although listed as v2 or out of scope:

| Item | Original classification | Status |
|------|------------------------|--------|
| INT-01 WhatsApp notifications | v2 / out of scope | ✅ Built (`app/Channels/WhatsAppChannel.php`) |
| ADV-01 Real-time notifications | v2 / out of scope | ✅ Built (Laravel Reverb + Echo, `MessageSent`) |
| Web Push / PWA | Out of scope ("email only in v1") | ✅ Built (`manifest.json`, `sw.js`, `PushSubscription`, VAPID) |
| Payments module | Never specified | ✅ Built (`Payment`, `Admin/PaymentController`) — needs requirements written retroactively |
| ADV-03 Client uploads files | v2 / fora do âmbito | ✅ Entregue 24/08 (`Client\UploadController`, configuração `allow_client_uploads`) |
| ADV-04 Budget line items | v2 | ✅ Entregue 24/08 (`BudgetItem`, `Project::budgetVersusSpent()`) |
| Serviços de arquitetura e design | Nunca especificado em v1 | ✅ Entregue 23-24/08, com requisitos escritos antes (`.planning/requisitos-arquitetura-design.md`) |

---
*Requisitos definidos: 2026-06-27*
*Estados reconciliados com o código: 2026-08-24 — v1 completo, serviços de arquitetura e design entregues, ADV-03 e ADV-04 entregues*