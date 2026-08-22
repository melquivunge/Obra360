# Production Readiness Notes

## Docker/Vite build

The production `Dockerfile` builds frontend assets in a dedicated Node stage:

```bash
docker build .
```

The final PHP image receives `public/build` from that build stage and does not include `node_modules`.

## Legacy public media migration

New project covers and photos are stored on the private `local` disk and served through authenticated routes.

Files that were previously stored under `storage/app/public` — covers, photos, thumbnails and documents — are migrated by the command below. The public-disk fallback was removed on 2026-08-22: media is now read only from the private `local` disk, and a record whose file is missing answers 404 instead of 500. Run the migration in any environment that still has media on the public disk **before** deploying that change there.

Preview the migration:

```bash
php artisan obra360:media:migrate-public
```

Copy legacy media to private storage:

```bash
php artisan obra360:media:migrate-public --commit
```

After validating that media works through the application, remove the public source files:

```bash
php artisan obra360:media:migrate-public --commit --delete-source
```

Production was migrated on 2026-08-22. The dry-run found eight media records whose files were on neither disk — the production database had been copied from a local environment and the files never followed. They were uploaded by hand into `storage/app/private`; the dry-run now reports `already_private: 8`, `missing_public: 0`.

## Access and deletion decisions

The `financial` role currently remains allowed into the internal admin area because the existing roadmap and gates only prove that it needs financial capabilities, not that it must be excluded from project context. Tighten this after a product decision defines the exact financial-only surface.

Project deletion remains a soft delete. Media files are not removed during soft delete so future restore remains possible. Permanent media cleanup should be implemented with an explicit force-delete lifecycle or queued cleanup job.
