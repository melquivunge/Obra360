# Portal de Acompanhamento de Obras (Obra360)

## What This Is

A web portal for construction, renovation, and inspection companies to share project progress with their clients in a transparent, visual, and professional way. Companies manage clients, projects, phases, photos, expenses, documents, and updates; clients log in and see only their own projects with real-time progress, financials, and photo galleries. The goal is to replace repetitive WhatsApp messages with a structured, client-facing portal that builds trust and saves the company administrative time.

## Core Value

A client logs in, immediately sees the current state of their construction project (phase, progress %, recent photos, money spent), and feels informed — without needing to contact the company.

## Requirements

*Reconciled against the codebase on 2026-08-21. See `.planning/REQUIREMENTS.md` for per-requirement status.*

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
- ✓ System settings: company name/logo/contacts, currency, comment, financial-visibility and per-event notification toggles — Phase 5
- ✓ Four per-project PDF reports: general, financial, photos, phases — Phase 5
- ✓ Audit log over 9 models with an admin-only screen — Phase 5
- ✓ Expense breakdown by category and by month; the currency setting now actually formats values — Phase 5
- ✓ Photo thumbnails and a gallery filterable by phase, date range and order — Phase 5
- ✓ Expense editing — Phase 5
- ✓ Dashboard KPIs for late and near-completion projects — Phase 5
- ✓ Notifications on three channels — e-mail, Web Push and WhatsApp — Phase 5
- ✓ Role-based access control via middleware + `AuthorizesProjectAccess` — Phase 1/4
- ✓ Soft deletes on users, clients, projects, phases' parents, expenses, documents, photos, payments — Phase 1
- ✓ Mobile-first responsive UI in Portuguese across all screens — Phase 1-4
- ✓ PWA with working Web Push delivery (VAPID) — beyond original scope
- ✓ WhatsApp notification channel — beyond original scope
- ✓ CI/CD: GitHub Actions running tests + Pint + audits, deploying to HostGator — beyond original scope

### Active

- [ ] **Regression tests for the older code paths** — 131 tests exist, but project CRUD, payments, document access control and upload validation on the pre-existing routes are still uncovered (Phase 6). Top debt.
- [ ] FASE-04 — progress is derived from the phases, but there is no setting to switch between derived and manual
- [ ] Run `obra360:media:migrate-public` in production, then remove the public-disk fallback
- [ ] Decide the exact surface the `financial` role should reach (see `docs/production-readiness.md`)
- [ ] Split `ProjectController` — over 400 lines covering six sub-resources
- [ ] Write requirements retroactively for the payments module
- [ ] Media cleanup policy on force-delete — soft-deleted projects leave files on disk

### Out of Scope

- Mobile app (iOS/Android) — future milestone
- Cloud storage (S3/DigitalOcean/R2) — private local disk in use, Flysystem keeps the swap cheap
- Multi-tenant SaaS billing — single company installation in v1

*Moved out of Out of Scope on 2026-08-20 because they shipped: WhatsApp integration, real-time notifications (Reverb + Web Push).*

## Context

- **Codebase state (2026-08-21)**: In production at obra360app.com. 13 Eloquent models, 22 controllers, 18 migrations, 23 test files with 131 passing tests. Phases 1-5 delivered; 82 of 83 v1 requirements done.
- **Planning artifacts**: Only Phase 1 ran through the GSD workflow. Phases 2-5 and the 2026-08-21 gap-closure work were built directly at the user's direction, so `.planning/phases/` has no plans or summaries for them.
- **Codebase docs**: `.planning/codebase/*.md` were regenerated 2026-08-20 and now predate `AuditLog`, `ReportController`, `Thumbnail` and the money helper. Mildly stale.
- **Target users**: Construction company admin staff (non-technical), field managers, financial staff, and end clients
- **Language**: Portuguese (PT) — all UI text, routes, labels, and notifications
- **Document source**: `requisitos_portal_acompanhamento_obras.docx` (v1, 26/06/2026)
- **Design palette**: dark base (#1a1a16) with gold accent (#c9a227) — evolved from the original white/blue/green proposal

## Constraints

- **Tech stack**: Laravel 13 + Blade + TailwindCSS v4 + MySQL — no SPA framework, server-side rendering
- **Auth**: Laravel Breeze (Blade) — installed
- **PDF**: DomPDF (barryvdh/laravel-dompdf) — installed and in use by the four reports
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
| Web Push + WhatsApp instead of email notifications | Clients read push and WhatsApp; email was being ignored | ✅ Resolved 2026-08-21 — e-mail added as a third channel, all three toggleable per event |
| Laravel Reverb for live messaging | Pulled ADV-01 forward; chat felt broken without it | ✅ Done — `MessageSent` broadcast |
| Payments as a separate module | Budget vs. expense was not enough to track client billing | ✅ Done — no requirement was ever written |
| Deploy to HostGator via GitHub Actions | Cheapest path to a live URL for client demos | ✅ Done — PHP 8.5 forced via cPanel uapi |
| Phases 2-5 built outside GSD | Speed; the workflow felt heavy mid-build | ⚠️ Cost: no plan/summary artifacts, planning docs drifted for ~2 months |
| Audit trail as one generic observer, not calls in controllers | Less code, and impossible to forget on a new write path | ✅ Done — `AuditObserver` on 9 models |
| Plain GD for thumbnails instead of Intervention Image | The job is ~40 lines; a dependency was not worth it | ✅ Done — `App\Support\Thumbnail` |
| Features before the test net | User's call after the risk was flagged | ⚠️ A `ParseError` shipped undetected in `7b3faa6`, caught one commit later |

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
*Last updated: 2026-08-21 — Phase 5 complete, all v1 feature gaps closed*