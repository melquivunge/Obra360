# Requirements: Portal de Acompanhamento de Obras (Obra360)

**Defined:** 2026-06-27
**Core Value:** A client logs in, immediately sees the current state of their construction project (phase, progress %, recent photos, money spent), and feels informed — without needing to contact the company.

## v1 Requirements

### Authentication

- [ ] **AUTH-01**: User can log in with email and password and be redirected to the correct dashboard based on their role
- [ ] **AUTH-02**: User can log out from any page and session is destroyed
- [ ] **AUTH-03**: User can request password reset via email link and set a new password
- [ ] **AUTH-04**: Inactive users cannot log in and see an appropriate error message
- [ ] **AUTH-05**: Passwords are stored hashed (bcrypt); plain-text passwords are never stored

### User Management

- [ ] **USR-01**: Administrator can create a user with name, email, phone, role (admin/manager/financial/client), and password
- [ ] **USR-02**: Administrator can edit user details and change their role
- [ ] **USR-03**: Administrator can activate or deactivate a user account
- [ ] **USR-04**: Administrator can delete a user (soft delete with confirmation)
- [ ] **USR-05**: System enforces 4 roles: Administrador, Gestor da Obra, Financeiro, Cliente

### Client Management

- [ ] **CLI-01**: Administrator can create a client with name/razão social, email, phone, fiscal document (CPF/CNPJ/NIF), address, city, country, notes, and status
- [ ] **CLI-02**: Administrator can edit client details
- [ ] **CLI-03**: Administrator can activate or deactivate a client
- [ ] **CLI-04**: A client account can be linked to one or more user logins
- [ ] **CLI-05**: Client users can only see data linked to their client account

### Project Management

- [ ] **PROJ-01**: Administrator can create a project (obra) with: name, internal code, linked client, location, description, start date, expected end date, total budget, status, progress %, responsible manager, and notes
- [ ] **PROJ-02**: Administrator can edit project details
- [ ] **PROJ-03**: Project status can be: Planejada, Em andamento, Pausada, Aguardando aprovação, Concluída, Cancelada
- [ ] **PROJ-04**: Administrator can soft-delete a project with confirmation
- [ ] **PROJ-05**: Manager (Gestor) can view and update projects assigned to them
- [ ] **PROJ-06**: Projects list can be filtered by client and by status

### Phase Management

- [ ] **FASE-01**: Administrator/Manager can create phases for a project with: name, description, status, percentage, sort order, start date, expected end date, real end date
- [ ] **FASE-02**: Phase status can be: Pendente, Em andamento, Concluída, Pausada, Aguardando aprovação
- [ ] **FASE-03**: Phases are displayed in configurable sort order
- [ ] **FASE-04**: Progress percentage can be updated manually or derived from completed phases (configurable per system settings)
- [ ] **FASE-05**: Changing a phase status triggers an automatic entry in the project update history

### Photo Management

- [ ] **FOTO-01**: Administrator/Manager can upload photos (JPG, JPEG, PNG, WebP) linked to a project and optionally to a phase
- [ ] **FOTO-02**: Each photo has a title/caption, description, date, and the user who uploaded it
- [ ] **FOTO-03**: System generates thumbnails on upload for gallery performance
- [ ] **FOTO-04**: Photos are displayed in a gallery filterable by date, phase, most recent, oldest
- [ ] **FOTO-05**: Client can only view photos of their own projects
- [ ] **FOTO-06**: Upload validates file type and enforces a maximum file size

### Expense Management

- [ ] **DESP-01**: Administrator/Financial can register an expense for a project with: category, description, amount, date, supplier, optional receipt file, notes
- [ ] **DESP-02**: Expense categories: Material, Mão de obra, Transporte, Equipamentos, Documentação, Licenças, Serviços terceirizados, Alimentação, Outros
- [ ] **DESP-03**: Administrator/Financial can edit an expense
- [ ] **DESP-04**: Administrator/Financial can delete an expense (soft delete with confirmation)
- [ ] **DESP-05**: Uploading a receipt file is optional and validated for allowed types

### Financial Summary

- [ ] **FIN-01**: System automatically calculates: total spent (sum of all project expenses), remaining balance (budget − total spent), % of budget used
- [ ] **FIN-02**: Financial summary shows breakdown by expense category
- [ ] **FIN-03**: Financial summary shows breakdown by month
- [ ] **FIN-04**: If budget is zero or null, financial section renders without crashing
- [ ] **FIN-05**: System settings control whether clients can see the financial summary (toggle per company config)
- [ ] **FIN-06**: Currency formatting uses the currency configured in system settings

### Update History

- [ ] **UPD-01**: System automatically creates a history entry when: a photo is added, a phase changes, a document is added, project status changes, an expense is registered
- [ ] **UPD-02**: Administrator/Manager can create a manual update (title, description, date, type)
- [ ] **UPD-03**: Update types: Atualização geral, Nova foto, Nova despesa, Mudança de fase, Documento adicionado, Alteração de status
- [ ] **UPD-04**: Client can see the full update history for their projects

### Document Management

- [ ] **DOC-01**: Administrator/Manager can upload documents (PDF, DOCX, XLSX, JPG, PNG) linked to a project
- [ ] **DOC-02**: Each document has: name, type (Contrato/Orçamento/Planta/Comprovante/Licença/Relatório/Recibo/Outros), visibility (Interno/Cliente), date, notes
- [ ] **DOC-03**: Documents marked "Interno" are not visible to clients
- [ ] **DOC-04**: Client can view and download documents marked "Cliente" for their projects only
- [ ] **DOC-05**: Upload validates file type and size

### Comments / Messaging

- [ ] **MSG-01**: Client can send a question/comment linked to a specific project
- [ ] **MSG-02**: Administrator/Manager can reply to a client message within the project
- [ ] **MSG-03**: All messages are threaded per project and visible to both parties
- [ ] **MSG-04**: Comment functionality can be enabled or disabled via system settings

### Dashboards

- [ ] **DASH-01**: Admin dashboard shows KPI cards: total projects, active, completed, paused; total clients; total budgeted; total spent; projects with delays; projects near completion
- [ ] **DASH-02**: Admin dashboard shows recent: expenses, photos, updates
- [ ] **DASH-03**: Client dashboard shows cards for each of their projects with: name, location, status, current phase, progress %, total spent, last update date, main photo, detail button
- [ ] **DASH-04**: Client project detail page shows: name, location, description, status, current phase, progress %, dates, financial summary, recent photos, full gallery, phase list, update history, released documents, comments (if enabled)

### Email Notifications

- [ ] **NOTF-01**: System sends email to client when: new photo added, new update published, phase changed, document released to client, project completed
- [ ] **NOTF-02**: Administrator can enable or disable email notifications per event type
- [ ] **NOTF-03**: Client only receives notifications for their own projects

### Reports

- [ ] **REL-01**: System generates a general project report (summary, phases, status) viewable on screen and exportable as PDF
- [ ] **REL-02**: System generates a financial report (expenses by category, by month, totals) viewable on screen and exportable as PDF
- [ ] **REL-03**: System generates a photo report (gallery list with dates and phases) viewable on screen and exportable as PDF
- [ ] **REL-04**: System generates a phases report (phase status and dates) viewable on screen and exportable as PDF

### System Settings

- [ ] **CONF-01**: Administrator can configure: company name, logo, default currency, contact info, email send address
- [ ] **CONF-02**: Administrator can enable/disable client comments
- [ ] **CONF-03**: Administrator can enable/disable client financial summary visibility
- [ ] **CONF-04**: Administrator can enable/disable email notifications per event type

### Security & Cross-Cutting

- [ ] **SEC-01**: All sensitive routes are protected by auth middleware and role-based policies/gates
- [ ] **SEC-02**: Client users cannot access any project, photo, document, or financial data belonging to other clients
- [ ] **SEC-03**: CSRF protection enabled on all forms
- [ ] **SEC-04**: Upload validation enforces allowed MIME types and maximum file sizes
- [ ] **SEC-05**: Activity log records important actions: project create/edit, photo upload, expense register, document upload, status changes, deletions
- [ ] **SEC-06**: Soft delete used on: projects, clients, users, expenses, documents, photos
- [ ] **SEC-07**: All pages paginate large lists

### UI / UX

- [ ] **UX-01**: All screens are responsive and work correctly on mobile, tablet, and desktop
- [ ] **UX-02**: Admin layout has sidebar navigation with: Dashboard, Clientes, Obras, Fases, Fotos, Despesas, Documentos, Relatórios, Usuários, Configurações, Sair
- [ ] **UX-03**: Client layout has minimal navigation: Minhas Obras, Documentos, Mensagens, Meu Perfil, Sair
- [ ] **UX-04**: UI uses cards, progress bars, color-coded statuses, photo gallery, clean tables, and clear action buttons
- [ ] **UX-05**: All UI text and labels are in Portuguese (PT)

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

*Filled by roadmapper agent — 2026-06-27*

| Requirement | Phase | Status |
|-------------|-------|--------|
| AUTH-01 | Phase 1 | Pending |
| AUTH-02 | Phase 1 | Pending |
| AUTH-03 | Phase 1 | Pending |
| AUTH-04 | Phase 1 | Pending |
| AUTH-05 | Phase 1 | Pending |
| USR-01 | Phase 1 | Pending |
| USR-02 | Phase 1 | Pending |
| USR-03 | Phase 1 | Pending |
| USR-04 | Phase 1 | Pending |
| USR-05 | Phase 1 | Pending |
| CLI-01 | Phase 2 | Pending |
| CLI-02 | Phase 2 | Pending |
| CLI-03 | Phase 2 | Pending |
| CLI-04 | Phase 2 | Pending |
| CLI-05 | Phase 4 | Pending |
| PROJ-01 | Phase 2 | Pending |
| PROJ-02 | Phase 2 | Pending |
| PROJ-03 | Phase 2 | Pending |
| PROJ-04 | Phase 2 | Pending |
| PROJ-05 | Phase 2 | Pending |
| PROJ-06 | Phase 2 | Pending |
| FASE-01 | Phase 2 | Pending |
| FASE-02 | Phase 2 | Pending |
| FASE-03 | Phase 2 | Pending |
| FASE-04 | Phase 2 | Pending |
| FASE-05 | Phase 2 | Pending |
| FOTO-01 | Phase 3 | Pending |
| FOTO-02 | Phase 3 | Pending |
| FOTO-03 | Phase 3 | Pending |
| FOTO-04 | Phase 3 | Pending |
| FOTO-05 | Phase 3 | Pending |
| FOTO-06 | Phase 3 | Pending |
| DESP-01 | Phase 3 | Pending |
| DESP-02 | Phase 3 | Pending |
| DESP-03 | Phase 3 | Pending |
| DESP-04 | Phase 3 | Pending |
| DESP-05 | Phase 3 | Pending |
| FIN-01 | Phase 3 | Pending |
| FIN-02 | Phase 3 | Pending |
| FIN-03 | Phase 3 | Pending |
| FIN-04 | Phase 3 | Pending |
| FIN-05 | Phase 3 | Pending |
| FIN-06 | Phase 3 | Pending |
| UPD-01 | Phase 3 | Pending |
| UPD-02 | Phase 3 | Pending |
| UPD-03 | Phase 3 | Pending |
| UPD-04 | Phase 3 | Pending |
| DOC-01 | Phase 3 | Pending |
| DOC-02 | Phase 3 | Pending |
| DOC-03 | Phase 3 | Pending |
| DOC-04 | Phase 3 | Pending |
| DOC-05 | Phase 3 | Pending |
| MSG-01 | Phase 4 | Pending |
| MSG-02 | Phase 4 | Pending |
| MSG-03 | Phase 4 | Pending |
| MSG-04 | Phase 4 | Pending |
| DASH-01 | Phase 2 | Pending |
| DASH-02 | Phase 2 | Pending |
| DASH-03 | Phase 4 | Pending |
| DASH-04 | Phase 4 | Pending |
| NOTF-01 | Phase 4 | Pending |
| NOTF-02 | Phase 4 | Pending |
| NOTF-03 | Phase 4 | Pending |
| REL-01 | Phase 5 | Pending |
| REL-02 | Phase 5 | Pending |
| REL-03 | Phase 5 | Pending |
| REL-04 | Phase 5 | Pending |
| CONF-01 | Phase 5 | Pending |
| CONF-02 | Phase 5 | Pending |
| CONF-03 | Phase 5 | Pending |
| CONF-04 | Phase 5 | Pending |
| SEC-01 | Phase 1 | Pending |
| SEC-02 | Phase 4 | Pending |
| SEC-03 | Phase 1 | Pending |
| SEC-04 | Phase 3 | Pending |
| SEC-05 | Phase 5 | Pending |
| SEC-06 | Phase 1 | Pending |
| SEC-07 | Phase 2 | Pending |
| UX-01 | Phase 1 | Pending |
| UX-02 | Phase 1 | Pending |
| UX-03 | Phase 1 | Pending |
| UX-04 | Phase 1 | Pending |
| UX-05 | Phase 1 | Pending |

**Coverage:**
- v1 requirements: 83 total (traceability table has 83 entries; initial estimate in file header was 79)
- Mapped to phases: 83
- Unmapped: 0

Phase breakdown:
- Phase 1 — Foundation: 18 requirements (AUTH-01-05, USR-01-05, SEC-01, SEC-03, SEC-06, UX-01-05)
- Phase 2 — Admin Core: 18 requirements (CLI-01-04, PROJ-01-06, FASE-01-05, DASH-01-02, SEC-07)
- Phase 3 — Project Content: 27 requirements (FOTO-01-06, DESP-01-05, FIN-01-06, UPD-01-04, DOC-01-05, SEC-04)
- Phase 4 — Client Portal: 11 requirements (DASH-03-04, MSG-01-04, NOTF-01-03, CLI-05, SEC-02)
- Phase 5 — Reports & Settings: 9 requirements (REL-01-04, CONF-01-04, SEC-05)

---
*Requirements defined: 2026-06-27*
*Last updated: 2026-06-27 — traceability table filled by roadmapper agent; 83/83 requirements mapped*
