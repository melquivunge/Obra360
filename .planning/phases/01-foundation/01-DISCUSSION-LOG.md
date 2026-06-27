# Phase 1: Foundation - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-06-27
**Phase:** 01-foundation
**Areas discussed:** DB Schema Scope, Admin Sidebar Behavior, User Management UI, Login Screen Design

---

## DB Schema Scope

| Option | Description | Selected |
|--------|-------------|----------|
| Yes — all tables now | Create all ~13 tables in Phase 1. Later phases add features without touching schema. | ✓ |
| No — phased migrations | Each phase adds its own tables. Simpler Phase 1 but schema scattered. | |
| Hybrid — core tables only | Phase 1 creates 4–5 foundation tables; complex tables wait for their phase. | |

**User's choice:** All tables created in Phase 1 (standard set)
**Notes:** No extra tables beyond the standard set were requested. Standard set confirmed: users, clients, projects, project_phases, photos, expenses, documents, project_updates, messages, settings.

---

## Admin Sidebar Behavior

| Option | Description | Selected |
|--------|-------------|----------|
| Always visible, fixed width | Sidebar always open on desktop. Simple, best for non-technical staff. Mobile: hamburger overlay. | ✓ |
| Collapsible — icons-only when collapsed | Toggle button shrinks to icon strip. More screen space but more JS complexity. | |
| Top nav bar instead of sidebar | Horizontal bar. Too many items (10) for horizontal layout. | |

**User's choice:** Always visible, fixed width on desktop; hamburger overlay on mobile.

| Option | Description | Selected |
|--------|-------------|----------|
| Icon + label text | Small icon on left, Portuguese label to the right. | ✓ |
| Label text only | Clean but plain. | |
| Icons only | Compact but not accessible for non-technical users. | |

**User's choice:** Icon + label text for each sidebar item.

---

## User Management UI

| Option | Description | Selected |
|--------|-------------|----------|
| Dedicated full pages | /usuarios (list) → /usuarios/criar → /usuarios/{id}/editar. Standard Laravel resource pattern. | ✓ |
| List page + modal dialogs | Single page, modal opens for create/edit. More JS required. | |
| Inline expand (accordion row) | Form expands inline in table row. Complex UX. | |

**User's choice:** Dedicated full CRUD pages (resource pattern).
**Notes:** This sets the pattern for all Phase 2 admin CRUD screens (clients, projects).

| Option | Description | Selected |
|--------|-------------|----------|
| Action buttons in the table row | Edit, Activate/Deactivate toggle, Delete with confirmation — inline per row. | ✓ |
| Actions in edit page only | Status change and delete only accessible from edit page. | |

**User's choice:** Inline row actions for activate/deactivate and delete.

---

## Login Screen Design

| Option | Description | Selected |
|--------|-------------|----------|
| Centered card on dark blue background | White card centered on navy/dark blue page. Professional, matches palette. | ✓ |
| Full-page split layout | Left: branding image. Right: form. Modern SaaS look, more complex to build. | |
| Clean white, minimal | Simple centered form on white. Fast to build but generic. | |

**User's choice:** Centered white card on dark blue/navy background.

| Option | Description | Selected |
|--------|-------------|----------|
| App name 'Obra360' with icon/logo placeholder | Shows 'Obra360' + building icon. Phase 5 settings replace with real company logo. | ✓ |
| Generic greeting only | 'Bem-vindo' or 'Acesse sua conta'. No brand identity. | |
| Configurable from start | Reads from settings table immediately. Adds Phase 1 complexity. | |

**User's choice:** "Obra360" app name with construction icon placeholder; real company logo deferred to Phase 5 settings.

---

## Claude's Discretion

- Icon set selection (Heroicons recommended)
- Tailwind CSS exact color values within the dark blue/white/green palette
- Migration file ordering and column naming conventions
- Pagination page size (15 items default)
- Database seeder for initial admin user

## Deferred Ideas

None — discussion stayed within Phase 1 scope.
