# Roadmap: Portal de Acompanhamento de Obras (Obra360)

## Overview

Five coarse phases deliver a fully working client-facing construction portal on top of a bare Laravel 13 skeleton. Phase 1 wires authentication and lays the database foundation. Phase 2 builds every admin management screen. Phase 3 fills projects with content (photos, expenses, documents, updates). Phase 4 opens the client-facing portal and adds messaging and email notifications. Phase 5 closes the loop with PDF reports and system-wide settings.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Foundation** — Auth, users, full DB schema, base layouts, and role-based access control
- [ ] **Phase 2: Admin Core** — Client management, project management, phase management, and admin dashboard
- [ ] **Phase 3: Project Content** — Photos, expenses, financial summary, documents, and update history
- [ ] **Phase 4: Client Portal** — Client-facing views, project detail page, messaging, and email notifications
- [ ] **Phase 5: Reports & Settings** — PDF reports, system configuration, and activity log

## Phase Details

### Phase 1: Foundation
**Goal**: The application has secure authentication, four-role access control, the complete database schema, and the two layout shells (admin and client) that all subsequent phases build on.
**Depends on**: Nothing (first phase)
**Requirements**: AUTH-01, AUTH-02, AUTH-03, AUTH-04, AUTH-05, USR-01, USR-02, USR-03, USR-04, USR-05, SEC-01, SEC-03, SEC-06, UX-01, UX-02, UX-03, UX-04, UX-05
**Success Criteria** (what must be TRUE):
  1. A user can enter email and password on the login page and be redirected to the correct dashboard for their role (Administrador, Gestor da Obra, Financeiro, or Cliente)
  2. An admin can create, edit, activate/deactivate, and soft-delete user accounts in all four roles via a user management screen
  3. A user who clicks "Esqueci a senha" receives a reset link by email and can set a new password before the link expires
  4. An inactive user who attempts to log in is blocked and sees an appropriate Portuguese error message
  5. The admin layout renders the full Portuguese sidebar navigation and the client layout renders minimal Portuguese navigation — both are fully responsive on mobile
**Plans**: 8 plans
- [x] 01-01-PLAN.md — Install Laravel Breeze (Blade) auth scaffold; reconcile Tailwind v4 pipeline
- [x] 01-02-PLAN.md — Complete DB schema (10 tables) + extended User model (roles, soft deletes)
- [x] 01-03-PLAN.md — Admin + client layout shells and reusable UI components (badge, nav-item, data-table, flash)
- [x] 01-04-PLAN.md — Portuguese auth, role-based redirects, inactive-user blocking, role middleware + Gates
- [x] 01-05-PLAN.md — Admin-only user management CRUD (paginated list, forms, activate/deactivate, soft delete)
- [x] 01-06-PLAN.md — Full-suite run + human verification checkpoint (gaps found)
- [ ] 01-07-PLAN.md — Eliminate remaining English Breeze surfaces and complete auth identity
- [ ] 01-08-PLAN.md — Polish the Obra360 design system, admin sidebar, and user-management UI; repeat human sign-off
**UI hint**: yes

### Phase 2: Admin Core
**Goal**: Admin and manager staff can fully manage clients, projects, and project phases through a working Portuguese-language admin panel with a live KPI dashboard.
**Depends on**: Phase 1
**Requirements**: CLI-01, CLI-02, CLI-03, CLI-04, PROJ-01, PROJ-02, PROJ-03, PROJ-04, PROJ-05, PROJ-06, FASE-01, FASE-02, FASE-03, FASE-04, FASE-05, DASH-01, DASH-02, SEC-07
**Success Criteria** (what must be TRUE):
  1. Admin can create a client with full details (fiscal document, address, notes, status) and see it in a paginated, filterable clients list
  2. Admin can create a project linked to a client, assign a responsible manager, set budget, status, and dates, and find it in a paginated projects list filterable by client and status
  3. Admin/Manager can add phases to a project with name, status, percentage, sort order, and dates; phases display in configurable order and changing a phase status triggers an automatic update history entry
  4. Manager logs in and can view and update only the projects assigned to them, with no access to unrelated projects
  5. Admin dashboard shows live KPI cards (total/active/completed/paused projects, total clients, total budget, total spent) and recent activity (expenses, photos, updates)
**Plans**: TBD
**UI hint**: yes

### Phase 3: Project Content
**Goal**: Company staff can upload photos with gallery view, register expenses with receipt files and see an automatic financial summary, manage internal and client-visible documents, and see a full auto-generated update history — all scoped per project.
**Depends on**: Phase 2
**Requirements**: FOTO-01, FOTO-02, FOTO-03, FOTO-04, FOTO-05, FOTO-06, DESP-01, DESP-02, DESP-03, DESP-04, DESP-05, FIN-01, FIN-02, FIN-03, FIN-04, FIN-05, FIN-06, UPD-01, UPD-02, UPD-03, UPD-04, DOC-01, DOC-02, DOC-03, DOC-04, DOC-05, SEC-04
**Success Criteria** (what must be TRUE):
  1. Admin/Manager can upload photos (JPG/PNG/WebP) linked to a project and optionally a phase; thumbnails are generated automatically; photos display in a gallery filterable by date and phase
  2. Admin/Financial can register an expense with category, amount, supplier, and an optional receipt file; the financial summary updates automatically to show total spent, remaining balance, and percentage of budget used
  3. Financial summary shows breakdown by expense category and by month, and renders correctly on projects with no budget (zero or null)
  4. Admin can upload a document (PDF/DOCX/XLSX/image) with visibility set to "Interno" or "Cliente"; documents marked "Interno" are not accessible to client users
  5. System automatically creates an update history entry when a photo is uploaded, an expense is registered, a phase changes, a document is added, or project status changes; admin/manager can also post a manual update
**Plans**: TBD
**UI hint**: yes

### Phase 4: Client Portal
**Goal**: Clients can log in and see the full status of their own projects (phases, photos, financials, documents, updates), ask questions that staff answer, receive email notifications, and never see another client's data.
**Depends on**: Phase 3
**Requirements**: DASH-03, DASH-04, MSG-01, MSG-02, MSG-03, MSG-04, NOTF-01, NOTF-02, NOTF-03, CLI-05, SEC-02
**Success Criteria** (what must be TRUE):
  1. Client logs in and sees a dashboard showing cards for each of their projects with: name, location, status, current phase, progress %, total spent, last update date, and main photo
  2. Client clicks a project and sees the full detail page: current phase, progress bar, photo gallery, financial summary (if admin-enabled), released documents, full update history, and comments thread (if admin-enabled)
  3. Client can post a question on a project's comment thread; admin/manager can reply; both parties see the full threaded conversation
  4. System sends the client an email when a new photo is added, a phase changes, a document is released, or the project is completed — and only for their own projects
  5. A client user who navigates to another client's project URL, photo, document, or financial data receives a 403 error — no data leakage is possible
**Plans**: TBD
**UI hint**: yes

### Phase 5: Reports & Settings
**Goal**: Admins can view and export four types of per-project PDF reports, and control all system-wide settings including company branding, currency, client financial visibility, comment toggling, and email notification types.
**Depends on**: Phase 4
**Requirements**: REL-01, REL-02, REL-03, REL-04, CONF-01, CONF-02, CONF-03, CONF-04, SEC-05
**Success Criteria** (what must be TRUE):
  1. Admin can view and export as PDF: a general project report (summary, status, phases), a financial report (expenses by category and month), a photo report (gallery with dates and phases), and a phases report (status and dates)
  2. Admin updates company name, logo, default currency, and contact info in settings; changes appear immediately in the UI (currency symbols, logo in header)
  3. Admin can enable/disable client comments, client financial summary visibility, and email notifications per event type — changes take effect in the client portal immediately
  4. Activity log shows a paginated, timestamped list of key actions (project create/edit, photo upload, expense entry, document upload, status changes, deletions) accessible only to admins
**Plans**: TBD
**UI hint**: yes

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation | 5/6 | In Progress|  |
| 2. Admin Core | 0/? | Not started | - |
| 3. Project Content | 0/? | Not started | - |
| 4. Client Portal | 0/? | Not started | - |
| 5. Reports & Settings | 0/? | Not started | - |
