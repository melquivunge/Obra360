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
- [~] **FASE-04**: Progress percentage can be updated manually or derived from completed phases (configurable per system settings) _(2026-08-20: progresso é recalculado a partir das fases (`Project::recalcProgress`), mas não é configurável nas definições)_
- [x] **FASE-05**: Changing a phase status triggers an automatic entry in the project update history

### Photo Management

- [x] **FOTO-01**: Administrator/Manager can upload photos (JPG, JPEG, PNG, WebP) linked to a project and optionally to a phase
- [x] **FOTO-02**: Each photo has a title/caption, description, date, and the user who uploaded it
- [ ] **FOTO-03**: System generates thumbnails on upload for gallery performance _(2026-08-20: a coluna `photos.thumbnail_path` existe mas nada a preenche)_
- [ ] **FOTO-04**: Photos are displayed in a gallery filterable by date, phase, most recent, oldest _(2026-08-20: a galeria não tem filtros por data ou fase)_
- [x] **FOTO-05**: Client can only view photos of their own projects
- [x] **FOTO-06**: Upload validates file type and enforces a maximum file size

### Expense Management

- [x] **DESP-01**: Administrator/Financial can register an expense for a project with: category, description, amount, date, supplier, optional receipt file, notes
- [x] **DESP-02**: Expense categories: Material, Mão de obra, Transporte, Equipamentos, Documentação, Licenças, Serviços terceirizados, Alimentação, Outros
- [ ] **DESP-03**: Administrator/Financial can edit an expense _(2026-08-20: não existe rota de edição de despesa (só criar e apagar))_
- [x] **DESP-04**: Administrator/Financial can delete an expense (soft delete with confirmation)
- [x] **DESP-05**: Uploading a receipt file is optional and validated for allowed types

### Financial Summary

- [x] **FIN-01**: System automatically calculates: total spent (sum of all project expenses), remaining balance (budget − total spent), % of budget used
- [ ] **FIN-02**: Financial summary shows breakdown by expense category _(2026-08-20: sem repartição por categoria)_
- [ ] **FIN-03**: Financial summary shows breakdown by month _(2026-08-20: sem repartição por mês)_
- [x] **FIN-04**: If budget is zero or null, financial section renders without crashing
- [x] **FIN-05**: System settings control whether clients can see the financial summary (toggle per company config)
- [ ] **FIN-06**: Currency formatting uses the currency configured in system settings _(2026-08-20: a definição `currency` é guardada mas nunca usada na formatação)_

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

- [~] **DASH-01**: Admin dashboard shows KPI cards: total projects, active, completed, paused; total clients; total budgeted; total spent; projects with delays; projects near completion _(2026-08-20: faltam os KPIs de obras atrasadas e obras perto da conclusão)_
- [x] **DASH-02**: Admin dashboard shows recent: expenses, photos, updates
- [x] **DASH-03**: Client dashboard shows cards for each of their projects with: name, location, status, current phase, progress %, total spent, last update date, main photo, detail button
- [x] **DASH-04**: Client project detail page shows: name, location, description, status, current phase, progress %, dates, financial summary, recent photos, full gallery, phase list, update history, released documents, comments (if enabled)

### Email Notifications

- [~] **NOTF-01**: System sends email to client when: new photo added, new update published, phase changed, document released to client, project completed _(2026-08-20: entregue como Web Push + WhatsApp, não e-mail)_
- [ ] **NOTF-02**: Administrator can enable or disable email notifications per event type _(2026-08-20: sem toggles por tipo de evento)_
- [~] **NOTF-03**: Client only receives notifications for their own projects _(2026-08-20: o scoping por cliente está correto, mas o canal é push/WhatsApp e não e-mail)_

### Reports

- [ ] **REL-01**: System generates a general project report (summary, phases, status) viewable on screen and exportable as PDF _(2026-08-20: DomPDF não instalado)_
- [ ] **REL-02**: System generates a financial report (expenses by category, by month, totals) viewable on screen and exportable as PDF _(2026-08-20: DomPDF não instalado)_
- [ ] **REL-03**: System generates a photo report (gallery list with dates and phases) viewable on screen and exportable as PDF _(2026-08-20: DomPDF não instalado)_
- [ ] **REL-04**: System generates a phases report (phase status and dates) viewable on screen and exportable as PDF _(2026-08-20: DomPDF não instalado)_

### System Settings

- [x] **CONF-01**: Administrator can configure: company name, logo, default currency, contact info, email send address
- [x] **CONF-02**: Administrator can enable/disable client comments
- [x] **CONF-03**: Administrator can enable/disable client financial summary visibility
- [ ] **CONF-04**: Administrator can enable/disable email notifications per event type _(2026-08-20: sem toggles de notificação por tipo de evento)_

### Security & Cross-Cutting

- [x] **SEC-01**: All sensitive routes are protected by auth middleware and role-based policies/gates
- [x] **SEC-02**: Client users cannot access any project, photo, document, or financial data belonging to other clients
- [x] **SEC-03**: CSRF protection enabled on all forms
- [x] **SEC-04**: Upload validation enforces allowed MIME types and maximum file sizes
- [~] **SEC-05**: Activity log records important actions: project create/edit, photo upload, expense register, document upload, status changes, deletions _(2026-08-20: só existe `project_updates`; não há log de auditoria dedicado nem ecrã de admin)_
- [x] **SEC-06**: Soft delete used on: projects, clients, users, expenses, documents, photos
- [x] **SEC-07**: All pages paginate large lists

### UI / UX

- [x] **UX-01**: All screens are responsive and work correctly on mobile, tablet, and desktop
- [x] **UX-02**: Admin layout has sidebar navigation with: Dashboard, Clientes, Obras, Fases, Fotos, Despesas, Documentos, Relatórios, Usuários, Configurações, Sair
- [x] **UX-03**: Client layout has minimal navigation: Minhas Obras, Documentos, Mensagens, Meu Perfil, Sair
- [x] **UX-04**: UI uses cards, progress bars, color-coded statuses, photo gallery, clean tables, and clear action buttons
- [x] **UX-05**: All UI text and labels are in Portuguese (PT)

## v2 Requirements

### Integrations

- **INT-01**: WhatsApp integration for update notifications
- **INT-02**: Cloud storage (Amazon S3 / DigitalOcean Spaces / Cloudflare R2) for file uploads
- **INT-03**: Mobile app (iOS/Android)

### Advanced Features

- **ADV-01**: Real-time notifications via WebSockets/Pusher
- **ADV-02**: Multi-company / SaaS billing mode
- **ADV-03**: Client can upload photos or documents (bidirectional)
- **ADV-04**: Project budgeting line items (beyond single total budget)

## Out of Scope

| Feature | Reason |
|---------|--------|
| WhatsApp integration | RNF06 — future milestone, high complexity |
| Mobile app | Future milestone |
| Cloud storage (S3 etc.) | Architecture prepared but local storage for v1 |
| Real-time push notifications | Email only in v1 |
| Multi-tenant SaaS billing | Single-company installation |
| OAuth / social login | Email+password sufficient for v1 |
| Client uploading files | Company controls content in v1 |

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
| FASE-04 | Phase 2 | 🟡 Partial |
| FASE-05 | Phase 2 | ✅ Done |
| FOTO-01 | Phase 3 | ✅ Done |
| FOTO-02 | Phase 3 | ✅ Done |
| FOTO-03 | Phase 3 | ❌ Not built |
| FOTO-04 | Phase 3 | ❌ Not built |
| FOTO-05 | Phase 3 | ✅ Done |
| FOTO-06 | Phase 3 | ✅ Done |
| DESP-01 | Phase 3 | ✅ Done |
| DESP-02 | Phase 3 | ✅ Done |
| DESP-03 | Phase 3 | ❌ Not built |
| DESP-04 | Phase 3 | ✅ Done |
| DESP-05 | Phase 3 | ✅ Done |
| FIN-01 | Phase 3 | ✅ Done |
| FIN-02 | Phase 3 | ❌ Not built |
| FIN-03 | Phase 3 | ❌ Not built |
| FIN-04 | Phase 3 | ✅ Done |
| FIN-05 | Phase 3 | ✅ Done |
| FIN-06 | Phase 3 | ❌ Not built |
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
| DASH-01 | Phase 2 | 🟡 Partial |
| DASH-02 | Phase 2 | ✅ Done |
| DASH-03 | Phase 4 | ✅ Done |
| DASH-04 | Phase 4 | ✅ Done |
| NOTF-01 | Phase 4 | 🟡 Partial |
| NOTF-02 | Phase 4 | ❌ Not built |
| NOTF-03 | Phase 4 | 🟡 Partial |
| REL-01 | Phase 5 | ❌ Not built |
| REL-02 | Phase 5 | ❌ Not built |
| REL-03 | Phase 5 | ❌ Not built |
| REL-04 | Phase 5 | ❌ Not built |
| CONF-01 | Phase 5 | ✅ Done |
| CONF-02 | Phase 5 | ✅ Done |
| CONF-03 | Phase 5 | ✅ Done |
| CONF-04 | Phase 5 | ❌ Not built |
| SEC-01 | Phase 1 | ✅ Done |
| SEC-02 | Phase 4 | ✅ Done |
| SEC-03 | Phase 1 | ✅ Done |
| SEC-04 | Phase 3 | ✅ Done |
| SEC-05 | Phase 5 | 🟡 Partial |
| SEC-06 | Phase 1 | ✅ Done |
| SEC-07 | Phase 2 | ✅ Done |
| UX-01 | Phase 1 | ✅ Done |
| UX-02 | Phase 1 | ✅ Done |
| UX-03 | Phase 1 | ✅ Done |
| UX-04 | Phase 1 | ✅ Done |
| UX-05 | Phase 1 | ✅ Done |

**Coverage (reconciled 2026-08-20):**

- v1 requirements: 83
- ✅ Done: 66
- 🟡 Partial: 5 — FASE-04, DASH-01, NOTF-01, NOTF-03, SEC-05
- ❌ Not built: 12 — FOTO-03, FOTO-04, DESP-03, FIN-02, FIN-03, FIN-06, NOTF-02, REL-01, REL-02, REL-03, REL-04, CONF-04

Phase breakdown:
- Phase 1 — Foundation: 18/18 done
- Phase 2 — Admin Core: 16 done, 2 partial (FASE-04, DASH-01)
- Phase 3 — Project Content: 21 done, 6 not built
- Phase 4 — Client Portal: 8 done, 2 partial, 1 not built
- Phase 5 — Reports & Settings: 3 done, 1 partial (SEC-05), 5 not built

## Scope Changes Since Definition

Delivered although listed as v2 or out of scope:

| Item | Original classification | Status |
|------|------------------------|--------|
| INT-01 WhatsApp notifications | v2 / out of scope | ✅ Built (`app/Channels/WhatsAppChannel.php`) |
| ADV-01 Real-time notifications | v2 / out of scope | ✅ Built (Laravel Reverb + Echo, `MessageSent`) |
| Web Push / PWA | Out of scope ("email only in v1") | ✅ Built (`manifest.json`, `sw.js`, `PushSubscription`, VAPID) |
| Payments module | Never specified | ✅ Built (`Payment`, `Admin/PaymentController`) — needs requirements written retroactively |

---
*Requirements defined: 2026-06-27*
*Statuses reconciled against the deployed codebase: 2026-08-20*