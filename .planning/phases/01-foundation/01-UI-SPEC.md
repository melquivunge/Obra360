---
phase: 1
slug: foundation
status: approved
shadcn_initialized: false
preset: none
created: 2026-06-27
---

# Phase 1 — UI Design Contract

> Visual and interaction contract for Phase 1: Foundation.
> Covers the login screen, admin layout shell, client layout shell, and user management screens.
> All UI text is in Portuguese (PT). No SPA — server-side Blade templates with Tailwind CSS v4.

---

## Design System

| Property | Value |
|----------|-------|
| Tool | Tailwind CSS v4 (utility-first, no component library) |
| Preset | none |
| Component library | Custom Blade components only |
| Icon library | Heroicons v2 (SVG inline or via blade-heroicons) |
| Font | Instrument Sans (already loaded via Bunny Fonts CDN in vite.config.js) |
| CSS framework | Tailwind CSS v4 via `@tailwindcss/vite` plugin |

---

## Color Palette

| Role | Hex | Tailwind class | Usage |
|------|-----|----------------|-------|
| Dominant (60%) | `#ffffff` | `white` | Page content areas, card backgrounds, form fields |
| Surface (30%) | `#f3f4f6` | `gray-100` | Page background, table row stripes, input wrappers |
| Navy (sidebar/login) | `#1e3a5f` | custom `navy-800` or `slate-800` | Sidebar background, login page background |
| Navy dark | `#152d4a` | custom `navy-900` | Sidebar hover states, active item highlight |
| Accent green | `#16a34a` | `green-600` | Progress bars, active badges, success states, "Ativo" status |
| Accent blue link | `#2563eb` | `blue-600` | Hyperlinks, secondary buttons |
| Destructive | `#dc2626` | `red-600` | Delete buttons, error messages, inactive/blocked state text |
| Border | `#e5e7eb` | `gray-200` | Table borders, card borders, form field borders |
| Text primary | `#111827` | `gray-900` | Body copy, headings, table cell text |
| Text secondary | `#6b7280` | `gray-500` | Labels, metadata, placeholder text |
| Text on navy | `#ffffff` | `white` | Sidebar labels, login page text |
| Text sidebar muted | `#94a3b8` | `slate-400` | Sidebar inactive icon/label |

**Accent reserved for:** Progress bars, status badges ("Ativo", "Concluída"), submit buttons ("Entrar"), and success flash messages only. Never used for decorative elements.

**Navy implementation:** Use `bg-slate-800` / `bg-slate-900` from Tailwind's default palette (closest to `#1e3a5f`). Adjust in `resources/css/app.css` with CSS custom properties if exact hex needed:
```css
:root {
  --color-navy: #1e3a5f;
  --color-navy-dark: #152d4a;
}
```

---

## Spacing Scale

All values are multiples of 4px. Tailwind CSS v4 uses the default 4px base grid.

| Token | Value | Tailwind | Usage |
|-------|-------|----------|-------|
| xs | 4px | `p-1` / `gap-1` | Icon gaps, badge padding |
| sm | 8px | `p-2` / `gap-2` | Compact element padding, icon-to-label gap |
| md | 16px | `p-4` / `gap-4` | Default form field padding, card padding |
| lg | 24px | `p-6` / `gap-6` | Card inner padding, section spacing |
| xl | 32px | `p-8` / `gap-8` | Page content area padding |
| 2xl | 48px | `p-12` / `gap-12` | Major section breaks |
| 3xl | 64px | `p-16` / `gap-16` | Login card vertical centering offset |

Sidebar width: `w-64` (256px). Sidebar item height: `h-11` (44px) — touch-friendly.

---

## Typography

Font stack: `'Instrument Sans', system-ui, sans-serif`

| Role | Size | Weight | Line Height | Tailwind |
|------|------|--------|-------------|---------|
| Body | 14px | 400 | 1.5 | `text-sm` |
| Label / small | 12px | 500 | 1.25 | `text-xs font-medium` |
| Form input | 14px | 400 | 1.5 | `text-sm` |
| Table cell | 14px | 400 | 1.5 | `text-sm` |
| Table header | 12px | 600 | 1.25 | `text-xs font-semibold uppercase tracking-wide` |
| Section heading | 18px | 600 | 1.25 | `text-lg font-semibold` |
| Page heading | 24px | 700 | 1.2 | `text-2xl font-bold` |
| App name (login) | 28px | 700 | 1.2 | `text-3xl font-bold` |
| Sidebar item label | 14px | 500 | 1 | `text-sm font-medium` |

---

## Screens and Interaction Contracts

### Screen 1: Login (`/login`)

**Layout:** Full-page dark navy background (`bg-slate-800`). Vertically and horizontally centered white card.

**Card:**
- Width: `w-full max-w-sm` (384px max)
- Padding: `p-8` (32px)
- Border radius: `rounded-2xl` (16px)
- Shadow: `shadow-2xl`
- Background: `bg-white`

**Card contents (top to bottom):**
1. **Logo block:** Construction building icon (Heroicons `BuildingOffice2Outline`, 40×40, `text-slate-800`) + "Obra360" in `text-3xl font-bold text-slate-800` — centered
2. **Subtitle:** "Acesse sua conta" — `text-sm text-gray-500 text-center mt-1`
3. **Form (mt-8):**
   - Email field — label "E-mail", `type="email"`, `autocomplete="email"`
   - Password field — label "Senha", `type="password"`, `autocomplete="current-password"`
   - Row with "Esqueci a senha" link (right-aligned, `text-sm text-blue-600`)
   - Submit button — full width, "Entrar", `bg-green-600 hover:bg-green-700 text-white`

**Error states:**
- Invalid credentials: red border on fields + message below form: "E-mail ou senha incorretos."
- Inactive account: message "Sua conta está inativa. Entre em contato com o administrador." (`text-red-600 text-sm`)
- Validation error: per-field `text-red-500 text-xs mt-1`

**Password Reset (`/forgot-password`):**
- Same card layout, title "Redefinir senha", subtitle "Insira seu e-mail para receber o link de redefinição."
- Single email field + button "Enviar link de redefinição"
- Success state: green message "Link enviado! Verifique seu e-mail."

---

### Screen 2: Admin Layout Shell

**Structure (desktop):**
```
┌─────────────────────────────────────────────────────┐
│  Sidebar (w-64, fixed left, full height, bg-slate-800) │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Logo: [Icon] Obra360                           │  │
│  │  ─────────────────────────────────────────────  │  │
│  │  [Icon] Dashboard                               │  │
│  │  [Icon] Clientes                                │  │
│  │  [Icon] Obras                          ACTIVE ◄ │  │
│  │  [Icon] Fases                                   │  │
│  │  [Icon] Fotos                                   │  │
│  │  [Icon] Despesas                                │  │
│  │  [Icon] Documentos                              │  │
│  │  [Icon] Relatórios                              │  │
│  │  [Icon] Usuários                                │  │
│  │  [Icon] Configurações                           │  │
│  │  ─────────────────────────────────────────────  │  │
│  │  [Icon] Sair                                    │  │
│  └──────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────┤
│  Main content area (ml-64, bg-gray-100, min-h-screen) │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Top bar: breadcrumb + user avatar/name         │  │
│  │  Page content (p-8)                             │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

**Sidebar items:**
- Container: `flex items-center gap-3 px-4 py-3 rounded-lg mx-2`
- Default state: `text-slate-400 hover:bg-slate-700 hover:text-white`
- Active state: `bg-slate-900 text-white` + left accent bar (2px green strip using `border-l-2 border-green-500`)
- Icon: 20×20 (`w-5 h-5`), Heroicons outline
- Label: `text-sm font-medium`

**Sidebar top logo:**
- `BuildingOffice2Outline` icon (24×24, white) + "Obra360" `text-lg font-bold text-white`
- Container: `px-4 py-5 border-b border-slate-700`

**Sidebar bottom "Sair":**
- Separated by `border-t border-slate-700 mt-auto`
- Same item style but icon is `ArrowRightOnRectangleOutline`

**Top bar:**
- Height: `h-16`, `bg-white border-b border-gray-200`
- Left: breadcrumb (`text-sm text-gray-500 > text-gray-900`)
- Right: user name + role badge + avatar initials circle (`w-8 h-8 rounded-full bg-slate-800 text-white text-xs flex items-center justify-center`)

**Mobile (< `lg` breakpoint):**
- Sidebar hidden by default; hamburger button (`Bars3Outline`, 24×24) in top-left of top bar
- Sidebar slides in as overlay (`fixed inset-0 z-50`), backdrop `bg-black/50`
- Close button (`XMarkOutline`) inside sidebar header

---

### Screen 3: Client Layout Shell

**Structure:**
```
┌─────────────────────────────────────────────────────┐
│  Top nav (h-16, bg-white, border-b, shadow-sm)     │
│  [Icon] Obra360  |  Minhas Obras  Documentos  ...  │
│                                    [Avatar] [Sair] │
└─────────────────────────────────────────────────────┘
│  Main content (bg-gray-50, min-h-screen, p-6)      │
```

**Nav items:** `text-sm font-medium text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md hover:bg-gray-100`
Active: `text-slate-800 font-semibold`

**Mobile:** Hamburger collapses nav items into vertical dropdown menu.

---

### Screen 4: User Management

**List page (`/usuarios`):**
- Page heading: "Usuários" (`text-2xl font-bold`) + "Novo Usuário" button (right, `bg-green-600 text-white`)
- Search/filter bar: text input for search + role filter dropdown
- Table: white background, `rounded-xl shadow-sm`, `divide-y divide-gray-200`
- Columns: Nome, E-mail, Telefone, Perfil (role badge), Status (active badge), Ações
- Role badges: `text-xs font-medium px-2 py-0.5 rounded-full`
  - Administrador: `bg-purple-100 text-purple-700`
  - Gestor da Obra: `bg-blue-100 text-blue-700`
  - Financeiro: `bg-yellow-100 text-yellow-700`
  - Cliente: `bg-gray-100 text-gray-600`
- Status badges:
  - Ativo: `bg-green-100 text-green-700`
  - Inativo: `bg-red-100 text-red-700`
- Row actions (right-aligned): "Editar" (link button, blue) | "Desativar"/"Ativar" (toggle, gray/green) | "Excluir" (red, with confirmation)

**Create/Edit form pages:**
- White card `max-w-2xl mx-auto rounded-xl shadow-sm p-8`
- Section title "Novo Usuário" or "Editar Usuário"
- Fields: Nome completo, E-mail, Telefone, Perfil (select), Senha (create only), Confirmar senha (create only)
- Form buttons: "Salvar" (green, primary) + "Cancelar" (gray, links back to list)
- Validation: inline red error text below each field (`text-sm text-red-600 mt-1`)

**Delete confirmation:**
- Inline `<form>` POST with a red "Excluir" button in row
- JavaScript `confirm()` dialog: "Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita."

**Flash messages (session-based):**
- Success: `bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg`
- Error: `bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg`
- Auto-dismiss after 4 seconds (vanilla JS)

---

## Copywriting Contract

All copy in **Portuguese (PT)**. Never use English text in the UI.

| Element | Copy (PT) |
|---------|-----------|
| Login CTA | "Entrar" |
| Forgot password link | "Esqueci a senha" |
| Password reset CTA | "Enviar link de redefinição" |
| Password reset success | "Link enviado! Verifique seu e-mail." |
| Login error — bad credentials | "E-mail ou senha incorretos." |
| Login error — inactive | "Sua conta está inativa. Entre em contato com o administrador." |
| Create user CTA | "Novo Usuário" |
| Save button | "Salvar" |
| Cancel link | "Cancelar" |
| Edit action | "Editar" |
| Deactivate action | "Desativar" |
| Activate action | "Ativar" |
| Delete action | "Excluir" |
| Delete confirmation | "Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita." |
| User list empty state | "Nenhum usuário cadastrado ainda. Crie o primeiro usuário." |
| Inactive badge | "Inativo" |
| Active badge | "Ativo" |
| Required field error | "Este campo é obrigatório." |
| Email invalid error | "Insira um e-mail válido." |
| Sidebar "Sair" | "Sair" |
| App subtitle (login) | "Acesse sua conta" |
| Breadcrumb separator | `›` |

---

## Component Inventory (Phase 1)

Custom Blade components to create in `resources/views/components/`:

| Component | File | Purpose |
|-----------|------|---------|
| `<x-admin-layout>` | `layouts/admin.blade.php` | Admin sidebar + top bar shell |
| `<x-client-layout>` | `layouts/client.blade.php` | Client top nav shell |
| `<x-guest-layout>` | `layouts/guest.blade.php` | Login/auth page shell |
| `<x-nav-item>` | `nav-item.blade.php` | Sidebar nav link with icon + label + active state |
| `<x-input-label>` | `input-label.blade.php` | Form label (already exists via Breeze) |
| `<x-text-input>` | `text-input.blade.php` | Form text input (already exists via Breeze) |
| `<x-input-error>` | `input-error.blade.php` | Per-field validation error (already exists via Breeze) |
| `<x-primary-button>` | `primary-button.blade.php` | Green submit button (already exists via Breeze) |
| `<x-flash-message>` | `flash-message.blade.php` | Success/error session flash |
| `<x-badge>` | `badge.blade.php` | Role and status colored badges |
| `<x-data-table>` | `data-table.blade.php` | Paginated table with header/row slots |

> Breeze-generated components (`x-input-label`, `x-text-input`, etc.) will be restyled to match this spec — do not replace them, just update their Tailwind classes.

---

## Heroicons Mapping

| Usage | Icon name | Variant |
|-------|-----------|---------|
| Sidebar: Dashboard | `HomeIcon` | Outline |
| Sidebar: Clientes | `UsersIcon` | Outline |
| Sidebar: Obras | `BuildingOffice2Icon` | Outline |
| Sidebar: Fases | `ClipboardDocumentListIcon` | Outline |
| Sidebar: Fotos | `PhotoIcon` | Outline |
| Sidebar: Despesas | `BanknotesIcon` | Outline |
| Sidebar: Documentos | `DocumentTextIcon` | Outline |
| Sidebar: Relatórios | `ChartBarIcon` | Outline |
| Sidebar: Usuários | `UserGroupIcon` | Outline |
| Sidebar: Configurações | `Cog6ToothIcon` | Outline |
| Sidebar: Sair | `ArrowRightOnRectangleIcon` | Outline |
| App logo | `BuildingOffice2Icon` | Outline |
| Mobile hamburger | `Bars3Icon` | Outline |
| Close sidebar | `XMarkIcon` | Outline |

---

## Registry Safety

| Registry | Items Used | Safety Gate |
|----------|-----------|-------------|
| Tailwind CSS v4 (npm) | Core utility classes | Not required |
| Heroicons v2 (npm or CDN) | SVG icons inline | Not required |
| Instrument Sans (Bunny Fonts CDN) | Font face | Not required — already in vite.config.js |

No third-party component registries (shadcn, Flowbite, etc.) — custom Blade components only.

---

## Accessibility Requirements

- All form inputs have associated `<label>` elements
- Color is never the only visual indicator (badges also have text)
- Sidebar active state has both background color change AND left border accent
- Flash messages are dismissible and auto-dismiss after 4s
- Delete confirmation uses `confirm()` dialog (accessible by default)
- Mobile nav keyboard-accessible (focus trap in open sidebar)
- All icons used decoratively have `aria-hidden="true"`

---

## Checker Sign-Off

- [x] Dimension 1 Copywriting: PASS — all copy specified in PT, no placeholders
- [x] Dimension 2 Visuals: PASS — screens 1–4 fully specified with layout details
- [x] Dimension 3 Color: PASS — full palette with hex, Tailwind class, and usage; accent reserved list explicit
- [x] Dimension 4 Typography: PASS — all roles specified with size, weight, line-height, Tailwind class
- [x] Dimension 5 Spacing: PASS — 4px grid declared, sidebar dimensions specified
- [x] Dimension 6 Registry Safety: PASS — no third-party registries; Tailwind + Heroicons + Instrument Sans only

**Approval:** approved 2026-06-27
