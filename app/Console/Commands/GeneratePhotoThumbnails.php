<?php

namespace App\Console\Commands;

use App\Models\Photo;
use App\Support\Thumbnail;
use Illuminate\Console\Command;

class GeneratePhotoThumbnails extends Command
{
    protected $signature = 'obra360:photos:thumbnails {--force : Regenerate thumbnails that already exist}';

    protected $description = 'Gera miniaturas para fotos já existentes (FOTO-03)';

    public function handle(): int
    {
        $query = Photo::query()->where('media_type', '!=', 'video');

        if (! $this->option('force')) {
            $query->whereNull('thumbnail_path');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('Nada a fazer — todas as fotos já têm miniatura.');

            return self::SUCCESS;
        }

        $this->info("A processar {$total} foto(s)...");
        $bar = $this->output->createProgressBar($total);
        $done = $skipped = 0;

        $query->chunkById(100, function ($photos) use ($bar, &$done, &$skipped) {
            foreach ($photos as $photo) {
                $path = Thumbnail::generate($photo->path);

                if ($path) {
                    $photo->update(['thumbnail_path' => $path]);
                    $done++;
                } else {
                    $skipped++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Miniaturas geradas: {$done}. Ignoradas (vídeo ou arquivo ilegível): {$skipped}.");

        return self::SUCCESS;
    }
}
