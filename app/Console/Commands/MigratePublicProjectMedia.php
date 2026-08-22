<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Photo;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigratePublicProjectMedia extends Command
{
    protected $signature = 'obra360:media:migrate-public
        {--commit : Copy legacy public media into the private local disk}
        {--delete-source : Delete public files after a verified copy; requires --commit}';

    protected $description = 'Safely migrate legacy project covers, photos, thumbnails and documents from public storage to private local storage.';

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');
        $deleteSource = (bool) $this->option('delete-source');

        if ($deleteSource && ! $commit) {
            $this->error('--delete-source requires --commit.');

            return self::FAILURE;
        }

        $paths = $this->mediaPaths();
        $stats = [
            'found' => $paths->count(),
            'already_private' => 0,
            'would_migrate' => 0,
            'migrated' => 0,
            'deleted_source' => 0,
            'missing_public' => 0,
            'errors' => 0,
        ];

        foreach ($paths as $path) {
            try {
                if (Storage::disk('local')->exists($path)) {
                    $stats['already_private']++;

                    continue;
                }

                if (! Storage::disk('public')->exists($path)) {
                    $stats['missing_public']++;
                    $this->warn("Missing public media: {$path}");

                    continue;
                }

                $stats['would_migrate']++;

                if (! $commit) {
                    $this->line("Would migrate: {$path}");

                    continue;
                }

                Storage::disk('local')->put($path, Storage::disk('public')->get($path));

                if (! Storage::disk('local')->exists($path)) {
                    $stats['errors']++;
                    $this->error("Copy verification failed: {$path}");

                    continue;
                }

                $stats['migrated']++;
                $this->info("Migrated: {$path}");

                if ($deleteSource) {
                    Storage::disk('public')->delete($path);
                    $stats['deleted_source']++;
                    $this->line("Deleted public source: {$path}");
                }
            } catch (Throwable $exception) {
                $stats['errors']++;
                $this->error("Failed {$path}: {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($value, $key) => [$key, $value])->values());

        if (! $commit) {
            $this->comment('Dry-run only. Re-run with --commit to copy files. Add --delete-source after validating migrated files.');
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function mediaPaths()
    {
        return Project::query()
            ->whereNotNull('cover_image_path')
            ->pluck('cover_image_path')
            ->merge(Photo::query()->whereNotNull('path')->pluck('path'))
            ->merge(Photo::query()->whereNotNull('thumbnail_path')->pluck('thumbnail_path'))
            ->merge(Document::withTrashed()->whereNotNull('path')->pluck('path'))
            ->filter()
            ->unique()
            ->values();
    }
}
