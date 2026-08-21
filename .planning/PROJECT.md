# Portal de Acompanhamento de Obras (Obra360)

## What This Is

A web portal for construction, renovation, and inspection companies to share project progress with their clients in a transparent, visual, and professional way. Companies manage clients, projects, phases, photos, expenses, documents, and updates; clients log in and see only their own projects with real-time progress, financials, and photo galleries. The goal is to replace repetitive WhatsApp messages with a structured, client-facing portal that builds trust and saves the company administrative time.

## Core Value

A client logs in, immediately sees the current state of their construction project (phase, progress %, recent photos, money spent), and feels informed — without needing to contact the company.

## Requirements

*Reconciled against the deployed codebase on 2026-08-20. See `.planning/REQUIREMENTS.md` for per-requirement status.*

### Validated

- ✓ Laravel 13 skeleton, Docker (PHP-FPM 8.4 + Nginx), Tailwind v4 + Vite, MySQL — existing
- ✓ Authentication: login/logout, role-based redirect, password recovery, inactive-user blocking — Phase 1
- ✓ User management: admin CRUD over 4 roles (admin, manager, financial, client) — Phase 1
- ✓ Client management: CRUD + linking client accounts to user logins — Phase 2
- ✓ Project (obra) management: full CRUD with status, progress, budget, manager, dates — Phase 2
- ✓ Admin dashboard: KPI cards + recent activity — Phase 2
- ✓ Project phases: CRUD with status, percentage, ordering, dates — Phase 2
- ✓ Photo uploads with per-project/phase gallery — Phase 3
- ✓ Expense tracking with categories, supplier, receipt upload — Phase 3
- ✓ Payments module (registo, edição, marcar pago) — built beyond the original spec
- ✓ Automatic financial summary: total spent, remaining balance, % of budget — Phase 3
- ✓ Project update history: auto-logged via `ProjectUpdateObserver` + manual entries — Phase 3
- ✓ Document management with Interno/Cliente visibility — Phase 3
- ✓ Client dashboard and project detail page — Phase 4
- ✓ Per-project messaging, delivered live over Laravel Reverb — Phase 4
- ✓ System settings: company name/logo/contacts, currency, comment and financial-visibility toggles — Phase 5
- ✓ Role-based access control via middleware + `AuthorizesProjectAccess` — Phase 1/4
- ✓ Soft deletes on users, clients, projects, phases' parents, expenses, documents, photos, payments — Phase 1
- ✓ Mobile-first responsive UI in Portuguese across all screens — Phase 1-4
- ✓ PWA with Web Push notifications (VAPID) — beyond original scope
- ✓ WhatsApp notification channel — beyond original scope
- ✓ CI/CD: GitHub Actions running tests + Pint + audits, deploying to HostGator — beyond original scope

### Active

- [ ] Reports: per-project general, financial, photos, phases — screen + PDF export (REL-01..04, DomPDF not installed)
- [ ] Audit/activity log with an admin screen (SEC-05 — only `project_updates` exists today)
- [ ] Email notifications (NOTF-01/03 — currently Web Push + WhatsApp only)
- [ ] Per-event notification toggles (NOTF-02 / CONF-04)
- [ ] Financial breakdown by category and by month (FIN-02, FIN-03)
- [ ] Apply the configured currency when formatting values (FIN-06)
- [ ] Photo thumbnail generation (FOTO-03) and gallery filters by date/phase (FOTO-04)
- [ ] Expense editing (DESP-03)
- [ ] Dashboard KPIs for delayed and near-completion projects (DASH-01)
- [ ] Write requirements retroactively for the payments module
- [ ] Decide the exact surface the `financial` role should reach (see `docs/production-readiness.md`)

### Out of Scope

- Mobile app (iOS/Android) — future milestone
- Cloud storage (S3/DigitalOcean/R2) — private local disk in use, Flysystem keeps the swap cheap
- Multi-tenant SaaS billing — single company installation in v1

*Moved out of Out of Scope on 2026-08-20 because they shipped: WhatsApp integration, real-time notifications (Reverb + Web Push).*

## Context

- **Codebase state (2026-08-20)**: In production at obra360app.com. 12 Eloquent models, 20 controllers, 17 migrations, 13 feature tests. Phases 1-4 of the roadmap are delivered; Phase 5 is partial.
- **Planning artifacts**: Only Phase 1 was executed through the GSD workflow. Phases 2-5 were built directly, so `.planning/phases/` has no plans or summaries for them.
- **Stale docs**: `.planning/codebase/*.md` still describe the June 2026 skeleton and are inlined into `AGENTS.md` and `CLAUDE.md`. Regenerate with `/gsd-map-codebase`.
- **Target users**: Construction company admin staff (non-technical), field managers, financial staff, and end clients
- **Language**: Portuguese (PT) — all UI text, routes, labels, and notifications
- **Document source**: `requisitos_portal_acompanhamento_obras.docx` (v1, 26/06/2026)
- **Design palette**: dark base (#1a1a16) with gold accent (#c9a227) — evolved from the original white/blue/green proposal

## Constraints

- **Tech stack**: Laravel 13 + Blade + TailwindCSS v4 + MySQL — no SPA framework, server-side rendering
- **Auth**: Laravel Breeze (Blade) — installed
- **PDF**: DomPDF (barryvdh/laravel-dompdf) for report exports — **not installed yet**
- **Storage**: Private local disk via Flysystem; media served through authenticated routes; S3-ready
- **PHP**: 8.5 in production (cPanel ea-php85), 8.4 in Docker, 8.3+ minimum per composer.json
- **Security**: CSRF, XSS, SQL injection protection built-in via Laravel; upload validation required; policies on all sensitive routes

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Laravel Breeze for auth | Provides login/logout/password reset out of the box; less boilerplate than Fortify | ✅ Done — Phase 1 |
| Blade templates (no SPA) | Matches spec; simpler for non-JS-heavy team; server-side rendering is sufficient | ✅ Done — Blade + Alpine throughout |
| Local storage + Flysystem | Gets v1 done fast; swap to S3 later with zero code change | ✅ Done — moved to the **private** disk; media served by authenticated routes |
| Soft deletes on entities | RB08 requires confirmation before delete; logical delete prevents accidents | ✅ Done — 7 models |
| Portuguese UI | End users are Portuguese-speaking; reduces confusion and support burden | ✅ Done — UI and routes in PT |
| Separate admin/client view layouts | Clear separation of concerns; prevents accidental data leakage | ✅ Done — `components/layouts/admin` + `client` |
| Web Push + WhatsApp instead of email notifications | Clients read push and WhatsApp; email was being ignored | ⚠️ Deviates from NOTF-01 — email still owed |
| Laravel Reverb for live messaging | Pulled ADV-01 forward; chat felt broken without it | ✅ Done — `MessageSent` broadcast |
| Payments as a separate module | Budget vs. expense was not enough to track client billing | ✅ Done — no requirement was ever written |
| Deploy to HostGator via GitHub Actions | Cheapest path to a live URL for client demos | ✅ Done — PHP 8.5 forced via cPanel uapi |
| Phases 2-5 built outside GSD | Speed; the workflow felt heavy mid-build | ⚠️ Cost: no plan/summary artifacts, planning docs drifted for ~2 months |

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
*Last updated: 2026-08-20 — reconciled with the deployed codebase*