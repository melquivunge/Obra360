---
gsd_state_version: 1.0
milestone: v1.0
milestone_name: milestone
status: executing
stopped_at: All v1 feature gaps closed; Phase 6 (financial regression tests) outstanding
last_updated: "2026-08-21"
last_activity: 2026-08-21 -- Phase 5 completed and every remaining v1 gap built
progress:
  total_phases: 6
  completed_phases: 5
  total_plans: 8
  completed_plans: 8
  percent: 95
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-08-21)

**Core value:** A client logs in, immediately sees the current state of their construction project (phase, progress %, recent photos, money spent), and feels informed — without needing to contact the company.
**Current focus:** Phase 06 — financial regression test net (the last outstanding item)

## Current Position

Phase: 06 (rede de testes do fluxo financeiro) — NOT STARTED
Status: Phases 1-5 delivered. 82 of 83 v1 requirements done, 1 partial, 0 not built.
Last activity: 2026-08-21 -- all remaining feature gaps built and committed

Progress: [█████████▌] 95%

> **Working mode:** Phases 2-5 and the 2026-08-21 gap-closure work were built
> directly, outside the GSD workflow, at the user's direction. There are no
> PLAN.md/SUMMARY.md artifacts for them. The status below was reconstructed from
> the code and the commit history.

## What Was Built on 2026-08-21

Six commits closed every outstanding v1 gap:

| Commit | Delivered |
|--------|-----------|
| `2fd2f4a` | Currency helper `money()` replacing 21 hardcoded `R$`; `expensesByCategory()` / `expensesByMonth()` / `remainingBalance()` / `budgetUsedPercent()`; `ExpenseFactory`; DomPDF installed (FIN-02, FIN-03, FIN-06) |
| `126972d` | Four per-project reports — geral, financeiro, fotos, fases — on screen and as PDF; "Relatórios" sidebar entry; date casts added to four models (REL-01..04) |
| `3aac97f` | `audit_logs` table + `AuditObserver` on 9 models; admin-only `/admin/auditoria` with filters; per-event notification toggles wired into `ProjectUpdateObserver` (SEC-05, CONF-04) |
| `7b3faa6` | Expense editing via a shared create/edit modal; dashboard "Atrasadas" and "Perto do fim" KPIs with alert lists (DESP-03, DASH-01) |
| `becb9ba` | `App\Support\Thumbnail` (plain GD, no new dependency); thumbnails on upload, served with `?thumb=1`; `obra360:photos:thumbnails` backfill; gallery filters by phase, date range and order (FOTO-03, FOTO-04) |
| `991c2cd` | `minishlink/web-push` installed — Web Push had never delivered anything; `toMail()` added to `ProjectUpdateNotification` (NOTF-01, NOTF-03) |

**Tests: 73 → 131, all passing. Pint clean. `composer audit` clean.**

## Incidental Findings Fixed

- **CI was red before any of this work.** `composer audit` reported 12 advisories
  against `guzzlehttp/guzzle` and `league/commonmark`, which the CI runs as a
  blocking step — deploys were failing. Both packages updated.
- **Four models had no date casts** (`Expense`, `Project`, `ProjectPhase`, `Photo`).
  Views worked around it with `Carbon::parse` over raw strings. Fixed at the model.
- **A multi-line `@json` inside an `onclick` attribute** broke the project page with
  a `ParseError`. Introduced in `7b3faa6` and shipped undetected because the expense
  tests only asserted redirects; the gallery tests, which render the page, caught it.

## Remaining Work

1. **Phase 6 — financial regression tests.** 131 tests exist, but the older paths
   (project CRUD, payments, document access control, upload validation on the
   pre-existing routes) are still uncovered. This is the top debt.
2. **FASE-04** — progress is derived from phases but there is no setting to choose
   derived vs. manual. The only partial requirement left.
3. Run `obra360:media:migrate-public` in production, then drop the public-disk fallback.
4. Decide the exact surface the `financial` role should reach (`docs/production-readiness.md`).
5. Split `ProjectController` — over 400 lines covering projects, phases, photos,
   documents, expenses and updates.
6. S3 migration (Flysystem already abstracts it).

## Accumulated Context

### Decisions

- Notifications now ship on three channels — e-mail, Web Push and WhatsApp — each
  failing silently when unconfigured, with per-event-type toggles in Settings.
- Audit trail implemented as one generic observer over 9 models rather than audit
  calls in controllers: less code, and impossible to forget on a new write path.
  `ProjectUpdate` is deliberately excluded — it is client-facing content.
- Thumbnails use plain GD instead of Intervention Image — the job is ~40 lines.
- Reports share Blade partials between the on-screen view and the PDF; photos are
  embedded as base64 because the media disk is private and DomPDF cannot
  authenticate. `isRemoteEnabled(false)` since the HTML is built from user data.

### Roadmap Evolution

- Phase 04.1 inserted 2026-08-20, then removed and re-added as Phase 6 on the user's
  direction to build features before tests. That risk played out exactly as flagged:
  a `ParseError` shipped in `7b3faa6` and was only caught one commit later.

### Blockers/Concerns

- `.planning/codebase/*.md` were regenerated on 2026-08-20 and are now mildly stale:
  they predate `AuditLog`, `ReportController`, `Thumbnail`, the money helper and the
  audit_logs table. Re-run the codebase mapping before relying on them.

## Session Continuity

Last session: 2026-08-21
Stopped at: all v1 feature gaps closed and committed

Next step: build the Phase 6 regression net over the financial and access-control paths.
