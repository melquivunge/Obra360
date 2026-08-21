# Codebase Concerns

**Analysis Date:** 2026-08-20

## Missing Features

**Expense Editing:**
- Issue: No update/edit route for expenses after creation; users can only store or destroy
- Files: `routes/web.php` (missing `PUT /obras/{project}/despesas/{expense}` route), `app/Http/Controllers/Admin/ProjectController.php` (missing `updateExpense()` method)
- Impact: Users cannot correct expense data once recorded. Must delete and re-enter, losing audit trail
- Fix approach: Add `Route::put()` for expense update; implement `updateExpense()` controller method with same validation as store; create blade form for editing

**PDF Report Generation:**
- Issue: `barryvdh/laravel-dompdf` dependency not installed; no PDF export capability exists
- Files: `composer.json` (missing from require), no controller methods for PDF generation
- Impact: Cannot generate project reports or financial summaries in PDF format; blocks compliance/archival use cases
- Fix approach: Add `composer require barryvdh/laravel-dompdf:^3.0`, create report controller, implement PDF views for projects and expenses

**Dedicated Audit Logging:**
- Issue: No system-level audit log; only project-specific `ProjectUpdate` table exists which is user-facing, not audit-focused
- Files: No audit-related tables or models; `app/Models/ProjectUpdate.php` serves dual purpose
- Impact: Cannot track who changed critical settings, deleted records, or accessed sensitive data; compliance/security investigation limited
- Fix approach: Create `audit_logs` table with action, user, model, changes, IP; implement `AuditEvent` and `AuditObserver` for sensitive model operations

## Tech Debt

**Unused/Dead Code:**
- Issue: `photos.thumbnail_path` column exists but is never populated or used anywhere
- Files: `database/migrations/2026_06_27_100004_create_photos_table.php` (column defined), `app/Models/Photo.php` (in fillable), no usage anywhere
- Impact: Database bloat, confusion for future developers, potential performance drag if populated later without indexing
- Fix approach: Either implement thumbnail generation pipeline (resize images on upload, write path) or remove column via migration

**Unused Photo Model Properties:**
- Issue: `photo.external_url` and `photo.media_type='video'` are fillable/stored but never rendered or used in templates
- Files: `app/Models/Photo.php` (fillable), `app/Http/Controllers/Admin/ProjectController.php` (sets `media_type` on upload, line 233)
- Impact: Dead code, unclear intent, potential for confusion when extending photo functionality
- Fix approach: Either fully implement video gallery feature (update views, add video player) or remove these columns

**Currency Setting Never Applied:**
- Issue: `currency` setting stored in database but hardcoded to "R$" in all Blade templates using `number_format()`
- Files: `app/Http/Controllers/Admin/SettingController.php` (stores currency), `resources/views/admin/dashboard.blade.php`, `resources/views/admin/projects/show.blade.php`, client views (all hardcoded "R$")
- Impact: Currency changes at application level have no effect; misleading UX; blocks multi-currency support
- Fix approach: Create `CurrencyFormatter` helper class; pass `$currency` to views; update all money display to use `moneyFormat($amount, $currency)` helper

## Known Security/Access Control Issues

**Financial Role Admin Area Access:**
- Issue: `financial` role is allowed into admin area (`role:admin,manager,financial` middleware) but authorization logic (`AuthorizesProjectAccess` trait) restricts them to projects where they're the manager; unclear if intentional
- Files: `routes/web.php` (line 31 includes financial), `app/Traits/AuthorizesProjectAccess.php` (line 14-18 financial check), `docs/production-readiness.md` (open decision documented)
- Impact: Potential privilege escalation if financial users can access admin dashboard; inconsistent access control model
- Fix approach: Product decision needed: either (a) restrict financial to `role:financial` only routes separate from admin, or (b) add `financial_id` column to projects and update authorization check; document decision in code

**Session Encryption Overhead:**
- Issue: `SESSION_ENCRYPT=true` in `.env.example` adds CPU cost to every session read; may impact performance under load
- Files: `.env.example` (line 36), `config/session.php` (line 50)
- Impact: Slower session operations; potential bottleneck in high-concurrency scenarios
- Fix approach: Monitor session performance; consider disabling encryption if sessions are database-backed and already protected; add caching layer

## Performance Bottlenecks

**Message Listing N+1 Query:**
- Issue: `MessageController::index()` loads latest 1 message per project with eager load, but then sorts in-memory; queries are optimized but sorting large collections is inefficient
- Files: `app/Http/Controllers/Admin/MessageController.php` (line 22-25)
- Impact: Slow page load if many projects exist; memory usage grows with project count
- Fix approach: Load message timestamps in initial query; order by `projects.updated_at` based on last message timestamp instead; use database sorting

**Media Disk Redundancy:**
- Issue: Deletion code attempts to delete from both 'local' and 'public' disks regardless of where file actually exists (no check like retrieve does)
- Files: `app/Http/Controllers/Admin/ProjectController.php` (line 250-251 photos, line 312-313 expenses), `app/Http/Controllers/Admin/ClientController.php`
- Impact: Wasted disk I/O operations; potential for silent failures if one disk fails; inefficient cleanup
- Fix approach: Use the same `mediaDisk()` check before deletion; delete only from the disk that has the file

## Fragile Areas

**Media Migration and Dual-Disk Fallback:**
- Issue: Temporary fallback from `local` to `public` disk for legacy media remains in place; migration command exists but hasn't been fully executed
- Files: `app/Console/Commands/MigratePublicProjectMedia.php`, `app/Http/Controllers/Admin/ProjectController.php` (line 396-399 mediaDisk check), `docs/production-readiness.md` (explicitly documented as open)
- Impact: Code complexity; storage path unpredictable; if public disk is ever removed/moved, legacy media breaks
- Fix approach: Run migration in production; enforce local-disk-only after confirmed success; remove fallback logic and `mediaDisk()` method

**Soft Delete Media Cleanup Gap:**
- Issue: Models use `SoftDeletes` (Project, Photo, Document, Expense, etc.) but media files are never cleaned up during soft delete; permanent cleanup requires explicit force-delete or manual cleanup job
- Files: `app/Models/Project.php` (line 20 uses SoftDeletes), `app/Models/Photo.php` (line 9 uses SoftDeletes), `database/migrations/2026_06_27_100004_create_photos_table.php` (uses cascadeOnDelete which soft-deletes related rows)
- Impact: Orphaned files accumulate on disk; storage bloat; restore of soft-deleted projects doesn't restore files if cleaned separately
- Fix approach: Implement `ForceDeleting` observer to clean media before force-delete; or create scheduled `CleanupSoftDeletedMedia` job; document policy in README

**Large ProjectController (400 lines):**
- Issue: `ProjectController` combines create, read, update, delete for projects + phases, photos, documents, updates, expenses in one class
- Files: `app/Http/Controllers/Admin/ProjectController.php` (400 lines)
- Impact: High cognitive load; difficult to test; violates single responsibility; bugs in one feature affect entire controller
- Fix approach: Refactor into separate controllers: `ProjectController` (CRUD only), `PhotoController`, `DocumentController`, `PhaseController`, `ExpenseController`

## Test Coverage Gaps

**Critical Business Logic Untested:**
- Issue: No test files for Project, Expense, Payment, or Photo operations; only auth, user management, and schema tests exist
- Files: `tests/Feature/` has tests only for SecurityHardeningTest, UserManagementTest, and Auth classes; missing ProjectTest.php, ExpenseTest.php, PaymentTest.php
- Impact: Regressions in core functionality go undetected; financial calculations (expense sums, payment tracking) can break silently
- Fix approach: Create feature tests for: (a) expense CRUD, (b) payment creation/status changes, (c) project visibility by role, (d) photo upload, (e) document access control

**No Integration Tests for Workflows:**
- Issue: No end-to-end test for complete project lifecycle (create → add phases → upload photos → record expenses → client views)
- Files: `tests/` directory; only unit/security/auth tests
- Impact: Cannot catch bugs in feature interactions; deployment risk high
- Fix approach: Add feature tests covering: complete project workflow, financial workflow (budget → expenses → payments), client portal access scenarios

## Scaling Limits

**Database Query Performance at Scale:**
- Issue: Dashboard aggregation query (line 21-28 DashboardController) uses `selectRaw()` efficiently but other views load full relations; no pagination on `ProjectUpdate` feed (line 45-49)
- Files: `app/Http/Controllers/Admin/DashboardController.php`, views that don't paginate large datasets
- Impact: Page load time grows with data volume; memory usage on large projects (many photos, messages, updates)
- Fix approach: Implement pagination or cursor-based pagination for updates/messages; add query caching for aggregations; monitor slow queries

**Session Table Growth:**
- Issue: `QUEUE_CONNECTION=database` and `SESSION_DRIVER=database` means all sessions and queued jobs stored in database; no automatic cleanup configured
- Files: `.env.example` (line 44, 35), `config/session.php`, `config/queue.php`
- Impact: Sessions and job records accumulate indefinitely; database bloat; slower queries over time
- Fix approach: Configure Laravel's session garbage collection; implement queue job pruning via `queue:prune-failed`; consider moving to Redis for scale

**File Storage on Shared Disk:**
- Issue: All media stored on `local` disk (which may be attached storage or shared network drive); no S3 migration path actively used
- Files: `routes/web.php` (storePhoto uses `local` disk), `config/filesystems.php` (should have S3 configured as option)
- Impact: Limits horizontal scaling; shared disk becomes bottleneck; backup complexity
- Fix approach: Migrate to S3; use Flysystem `s3` driver configured; implement signed URLs for private media access

## Dependencies at Risk

**No Security Update Strategy:**
- Issue: No documented process for monitoring or applying security updates to dependencies
- Files: `composer.json`, `composer.lock` exist but no CI/CD pipeline shown, no dependabot/renovate config
- Impact: Vulnerable dependencies can remain unpatched indefinitely
- Fix approach: Add GitHub Actions workflow for `composer outdated` checks; enable Dependabot; document security release process

**WhatsApp Integration Dependency:**
- Issue: WhatsApp notification channel depends on Meta Graph API credentials (`WHATSAPP_PHONE_ID`, `WHATSAPP_TOKEN`); no fallback if API is unavailable
- Files: `app/Channels/WhatsAppChannel.php` (line 40-62 throws on failure, relies on queue retry)
- Impact: Notifications fail silently if API down; queue backs up with retried jobs; no alerting for missing credentials
- Fix approach: Add alert/log when credentials missing; implement circuit breaker; add fallback notification channel (email); add monitoring for API errors

## Miscellaneous Issues

**Hardcoded Language/Locale:**
- Issue: Portuguese text hardcoded throughout; no i18n/translation system in place despite `APP_LOCALE` config
- Files: All Blade views, controller flash messages, notifications; `.env.example` shows locale but not used
- Impact: Cannot easily add English or other language support; maintenance burden for text changes
- Fix approach: Extract strings to `resources/lang/pt/messages.php`; use `__()` helper throughout; add locale switcher

**Message Limit Hardcoding:**
- Issue: Message queries hardcode limits (100, 200) in code to prevent OOM; limits not configurable
- Files: `app/Http/Controllers/Client/ProjectController.php` (line 25), `app/Http/Controllers/Admin/MessageController.php` (line 37)
- Impact: Limits become magic numbers; future changes require code edits; inconsistency across endpoints
- Fix approach: Move to config: `config/app.php` → `'query_limits' => ['messages' => 200, ...]`; use throughout

**No Rate Limiting on Resource Operations:**
- Issue: Only push subscription endpoints throttled (`throttle:10,1` and `throttle:30,1`); no rate limiting on message posting, photo uploads, expense creation
- Files: `routes/web.php` (line 126-130 have throttle, others don't)
- Impact: Potential abuse vector; DoS risk; spam by malicious client users
- Fix approach: Add `->middleware('throttle:10,1')` to POST/DELETE routes for content creation; document rate limits for clients

---

*Concerns audit: 2026-08-20*
