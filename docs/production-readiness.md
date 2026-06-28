# Production Readiness Notes

## Docker/Vite build

The production `Dockerfile` builds frontend assets in a dedicated Node stage:

```bash
docker build .
```

The final PHP image receives `public/build` from that build stage and does not include `node_modules`.

## Legacy public media migration

New project covers and photos are stored on the private `local` disk and served through authenticated routes.

Existing files that were previously stored in `storage/app/public/covers` or `storage/app/public/projects/*/photos` should be migrated before removing the temporary public-disk fallback.

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

Keep the fallback from `public` to `local` until the migration has been run and verified in the target environment.

## Access and deletion decisions

The `financial` role currently remains allowed into the internal admin area because the existing roadmap and gates only prove that it needs financial capabilities, not that it must be excluded from project context. Tighten this after a product decision defines the exact financial-only surface.

Project deletion remains a soft delete. Media files are not removed during soft delete so future restore remains possible. Permanent media cleanup should be implemented with an explicit force-delete lifecycle or queued cleanup job.
