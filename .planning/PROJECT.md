# Portal de Acompanhamento de Obras (Obra360)

## What This Is

A web portal for construction, renovation, and inspection companies to share project progress with their clients in a transparent, visual, and professional way. Companies manage clients, projects, phases, photos, expenses, documents, and updates; clients log in and see only their own projects with real-time progress, financials, and photo galleries. The goal is to replace repetitive WhatsApp messages with a structured, client-facing portal that builds trust and saves the company administrative time.

## Core Value

A client logs in, immediately sees the current state of their construction project (phase, progress %, recent photos, money spent), and feels informed — without needing to contact the company.

## Requirements

### Validated

- ✓ Laravel 13 skeleton installed — existing
- ✓ Docker (PHP-FPM 8.4 + Nginx) containerized environment — existing
- ✓ Tailwind CSS v4 + Vite 8 frontend pipeline — existing
- ✓ MySQL database driver (pdo_mysql) installed — existing
- ✓ User model with Authenticatable + sessions table — existing

### Active

- [ ] Authentication: login/logout with email+password, role-based redirect, password recovery
- [ ] User management: admin can create/edit/activate/deactivate users (4 roles: admin, manager, financial, client)
- [ ] Client management: CRUD for clients with document, address, status fields
- [ ] Project (obra) management: full CRUD with status, progress, budget, manager, dates
- [ ] Admin dashboard: KPI cards (active projects, clients, budget totals, alerts)
- [ ] Project phases: CRUD per project with status, percentage, ordering, dates
- [ ] Photo uploads: upload JPG/PNG/WebP per project/phase with captions, thumbnails, gallery view
- [ ] Expense tracking: register expenses per project with categories, supplier, receipt upload
- [ ] Automatic financial summary: total spent, remaining balance, % of budget used
- [ ] Project updates history: auto-logged events + manual entries per project
- [ ] Document management: upload PDF/DOCX/XLSX/images per project with internal/client visibility
- [ ] Client dashboard: cards showing client's projects with status, progress, last update
- [ ] Client project detail page: phase progress, photo gallery, financial summary, documents, update history
- [ ] Comments/messaging: client sends questions, company responds, per-project thread
- [ ] Email notifications: on new photo, new update, phase change, document released, project complete
- [ ] Reports: per-project general, financial, photos, phases — screen + PDF export
- [ ] System settings: company name/logo, currency, email config, notification toggles, financial visibility toggle
- [ ] Role-based access control: policies/gates enforcing each role's permissions
- [ ] Audit/activity log: record important actions (create/edit project, upload, expense, status change)
- [ ] Soft deletes on important entities
- [ ] Mobile-first responsive UI across all screens

### Out of Scope

- WhatsApp integration — planned for future (RNF06), not v1
- Mobile app (iOS/Android) — future milestone
- Cloud storage (S3/DigitalOcean/R2) — local storage first, S3-ready architecture
- Real-time notifications (WebSockets/Pusher) — email only in v1
- Multi-tenant SaaS billing — single company installation in v1

## Context

- **Codebase state**: Fresh Laravel 13 skeleton. No controllers, no models beyond User, no routes beyond `/` welcome. Docker compose ready. Auth not yet wired.
- **Target users**: Construction company admin staff (non-technical), field managers, financial staff, and end clients (homeowners/businesses commissioning construction work)
- **Language**: Portuguese (PT) — all UI text, labels, and notifications should be in Portuguese
- **Document source**: `requisitos_portal_acompanhamento_obras.docx` (v1, 26/06/2026) — full spec provided
- **Design palette**: White, light grey, dark blue, green for progress — clean, professional, mobile-first

## Constraints

- **Tech stack**: Laravel 13 + Blade + TailwindCSS v4 + MySQL — no SPA framework, server-side rendering
- **Auth**: Laravel Breeze or Fortify (built-in, not custom auth)
- **PDF**: DomPDF (barryvdh/laravel-dompdf) for report exports
- **Storage**: Local disk initially, prepared for S3 via Flysystem abstraction
- **PHP**: 8.4 (Docker), 8.3+ minimum per composer.json
- **Security**: CSRF, XSS, SQL injection protection built-in via Laravel; upload validation required; policies on all sensitive routes

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Laravel Breeze for auth | Provides login/logout/password reset out of the box; less boilerplate than Fortify | — Pending |
| Blade templates (no SPA) | Matches spec; simpler for non-JS-heavy team; server-side rendering is sufficient | — Pending |
| Local storage + Flysystem | Gets v1 done fast; swap to S3 later with zero code change | — Pending |
| Soft deletes on entities | RB08 requires confirmation before delete; logical delete prevents accidents | — Pending |
| Portuguese UI | End users are Portuguese-speaking; reduces confusion and support burden | — Pending |
| Separate admin/client view layouts | Clear separation of concerns; prevents accidental data leakage | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-06-27 after initialization*
