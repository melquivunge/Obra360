---
phase: 01-foundation
plan: 3
subsystem: ui-shell
tags: [layouts, blade-components, tailwind-v4, navy-sidebar, mobile-responsive, portuguese]
dependency_graph:
  requires: [01-01]
  provides: [admin-layout, client-layout, guest-layout, badge-component, flash-message-component, data-table-component, nav-item-component]
  affects: [01-04, 01-05, 01-06, 02-all, 03-all, 04-all, 05-all]
tech_stack:
  added: []
  patterns:
    - Blade anonymous components with @props for typed slots
    - Inline Heroicons v2 SVG (no npm package — copy-paste 24x24 outline paths)
    - CSS custom properties (--color-navy, --color-navy-dark) in :root via app.css
    - Vanilla JS sidebar toggle (data-* attributes) appended to app.js alongside Alpine.js
    - POST form logout with @csrf inside layout shells (CSRF protection per T-03-01)
key_files:
  created:
    - resources/css/app.css (modified — navy tokens added)
    - resources/views/components/badge.blade.php
    - resources/views/components/flash-message.blade.php
    - resources/views/components/data-table.blade.php
    - resources/views/components/nav-item.blade.php
    - resources/views/components/layouts/admin.blade.php
    - resources/views/components/layouts/client.blade.php
    - resources/views/components/layouts/guest.blade.php
    - resources/js/app.js (modified — mobile sidebar toggle appended)
  modified: []
decisions:
  - D-06: Admin layout uses fixed bg-slate-800 sidebar (w-64) with lg:ml-64 main offset; mobile uses overlay drawer with backdrop
  - D-07: Logout in both shells is a POST form with @csrf — never a GET link (CSRF safety per T-03-01)
  - D-08: Inline SVG heroicons (no npm blade-heroicons package) — static, no injection surface (T-03-03 accepted)
  - D-09: nav-item uses plain path hrefs (/dashboard, /usuarios etc.) not route() helpers — those names are undefined until Phase 2+
  - D-10: Client layout uses separate shell from admin to prevent admin nav leaking to client portal (T-03-02 mitigation)
metrics:
  duration_minutes: 20
  completed_date: "2026-06-27"
  tasks_completed: 3
  files_created: 7
  files_modified: 2
  files_deleted: 0
---

# Phase 1 Plan 3: Layout Shells and Reusable UI Components Summary

**One-liner:** Navy admin sidebar shell (11 PT nav items, mobile drawer, CSRF logout), minimal client top-nav shell (5 PT items, mobile dropdown), navy guest shell, and four reusable Blade components (badge, flash-message, data-table, nav-item) matching the UI Design Contract exactly.

## What Was Built

### CSS Tokens (Task 1)

`resources/css/app.css` extended with:
- `--color-navy: #1e3a5f` and `--color-navy-dark: #152d4a` inside `:root { }` below the existing `@import "tailwindcss"` line

### Reusable Blade Components (Task 1)

| Component | File | Key Classes |
|-----------|------|-------------|
| `<x-badge>` | `badge.blade.php` | `inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full` + color map |
| `<x-flash-message>` | `flash-message.blade.php` | Reads `session('success'/'error')`, auto-dismiss after 4000ms via inline script |
| `<x-data-table>` | `data-table.blade.php` | `rounded-xl shadow-sm overflow-hidden border border-gray-200`, `min-w-full divide-y divide-gray-200`, named `head` slot |
| `<x-nav-item>` | `nav-item.blade.php` | Active: `bg-slate-900 text-white border-l-2 border-green-500`; inactive: `text-slate-400 hover:bg-slate-700 hover:text-white`; icons with `aria-hidden="true"` |

**Badge color map:**
- `purple` → `bg-purple-100 text-purple-700` (Administrador)
- `blue` → `bg-blue-100 text-blue-700` (Gestor)
- `yellow` → `bg-yellow-100 text-yellow-700` (Financeiro)
- `gray` → `bg-gray-100 text-gray-600` (Cliente)
- `green` → `bg-green-100 text-green-700` (Ativo)
- `red` → `bg-red-100 text-red-700` (Inativo/Erro)

### Admin Layout Shell (Task 2)

`resources/views/components/layouts/admin.blade.php` (`<x-admin-layout>`):

- Full HTML document, `lang="pt-BR"`, `@vite(['resources/css/app.css','resources/js/app.js'])`, `body class="font-sans"`
- **Sidebar** (`w-64 fixed left-0 top-0 h-full bg-slate-800`): BuildingOffice2 logo + "Obra360", 10 nav items via `<x-nav-item>`, bottom Sair POST form
- **Nav items** (all in Portuguese, plain-path hrefs, active via `request()->is()`):
  Dashboard `/dashboard`, Clientes `/clientes`, Obras `/obras`, Fases `/fases`, Fotos `/fotos`, Despesas `/despesas`, Documentos `/documentos`, Relatórios `/relatorios`, Usuários `/usuarios`, Configurações `/configuracoes`
- **Top bar** (`h-16 bg-white border-b border-gray-200`): breadcrumb `$breadcrumb` slot (left), user name + role badge + initials avatar (right)
- **Main** (`lg:ml-64 bg-gray-100 min-h-screen`): `<x-flash-message>` + `{{ $slot }}`
- **Mobile**: `lg:hidden` hamburger (Bars3 icon) in top bar; `data-sidebar-backdrop` div with `bg-black/50`; `data-sidebar` aside toggled via JS; XMark close button in sidebar header

`resources/js/app.js` extended with vanilla-JS toggle listening on `[data-sidebar-toggle]`, showing/hiding `[data-sidebar]` and `[data-sidebar-backdrop]`.

### Client Layout Shell (Task 3)

`resources/views/components/layouts/client.blade.php` (`<x-client-layout>`):

- Full HTML document, `lang="pt-BR"`, `@vite`, `body class="font-sans"`
- **Top nav** (`h-16 bg-white border-b border-gray-200 shadow-sm`): BuildingOffice2 logo, 4 nav items (desktop), avatar + Sair POST form
- **Nav items** (all Portuguese): Minhas Obras `#`, Documentos `#`, Mensagens `#`, Meu Perfil `/profile`
- **Mobile**: `lg:hidden` hamburger toggles `[data-client-nav]` vertical dropdown (inline script)
- **Main** (`bg-gray-50 min-h-screen p-6`): `<x-flash-message>` + `{{ $slot }}`

### Guest Layout Shell (Task 3)

`resources/views/components/layouts/guest.blade.php` (`<x-guest-layout>`):

- Full HTML document, `lang="pt-BR"`, `@vite`, `body class="font-sans bg-slate-800 min-h-screen flex items-center justify-center p-4"`
- Renders `{{ $slot }}` centered — Plan 04 auth views supply the white card content

## Security Coverage (Threat Model)

| Threat | Status |
|--------|--------|
| T-03-01: CSRF logout | Mitigated — logout in both admin and client shells is a `<form method="POST">` with `@csrf`; never a GET link |
| T-03-02: Cross-role nav leakage | Mitigated — separate `x-admin-layout` and `x-client-layout` shells; admin routes never appear in client nav |
| T-03-03: Inline SVG injection | Accepted — icons are static inline SVG paths, `aria-hidden="true"`, no remote content |

## Deviations from Plan

None — plan executed exactly as written. All 11 admin labels, 5 client labels, POST+CSRF logout, mobile drawer, navy tokens, and four reusable components delivered matching the UI Design Contract.

## Known Stubs

The following nav item hrefs are intentionally `"#"` placeholders — routes will be defined in later plans/phases:

| Component | Item | Stub | Resolved In |
|-----------|------|------|-------------|
| admin.blade.php | Clientes, Obras, Fases, Fotos, Despesas, Documentos, Relatórios, Configurações | `href="#"` | Phase 2-5 plans |
| client.blade.php | Minhas Obras, Documentos, Mensagens | `href="#"` | Phase 4 plans |

These stubs do NOT prevent the plan's goal (layout shells) from being achieved. The shells render correctly with placeholder links.

## Threat Flags

None — no new network endpoints or trust boundaries beyond what the plan's threat model already covers.

## Self-Check: PASSED

- resources/css/app.css contains `--color-navy: #1e3a5f`: FOUND
- resources/views/components/badge.blade.php contains `rounded-full`: FOUND
- resources/views/components/flash-message.blade.php contains `session('success')` and `4000`: FOUND
- resources/views/components/data-table.blade.php contains `divide-y divide-gray-200`: FOUND
- resources/views/components/nav-item.blade.php contains `border-l-2 border-green-500` and `aria-hidden="true"`: FOUND
- resources/views/components/layouts/admin.blade.php contains all 11 labels + `bg-slate-800` + `action="/logout"` + `data-sidebar-toggle`: FOUND
- resources/views/components/layouts/client.blade.php contains all 5 labels + `action="/logout"` + `lg:hidden`: FOUND
- resources/views/components/layouts/guest.blade.php contains `bg-slate-800` and `items-center justify-center`: FOUND
- resources/js/app.js contains `data-sidebar`: FOUND
- npm run build exits 0: PASSED
- Commits 89e43a3 (Task 1), ebd7a97 (Task 2), 825ffb7 (Task 3): FOUND
