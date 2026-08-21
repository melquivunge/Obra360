<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Gallery thumbnails (FOTO-03).
 *
 * ponytail: plain GD, no image library. The job is "shrink to fit a box and
 * re-encode as JPEG" — Intervention Image would be a dependency for ~40 lines.
 * Swap it in if cropping, filters or format negotiation ever show up.
 */
class Thumbnail
{
    public const MAX_EDGE = 480;

    private const QUALITY = 78;

    /**
     * Write a thumbnail next to the original and return its path,
     * or null when the source is not a supported still image.
     */
    public static function generate(string $sourcePath, string $disk = 'local'): ?string
    {
        if (! Storage::disk($disk)->exists($sourcePath)) {
            return null;
        }

        try {
            $bytes = Storage::disk($disk)->get($sourcePath);
            $image = @imagecreatefromstring($bytes);

            if ($image === false) {
                return null;   // video, PDF, or a format GD cannot read
            }

            [$width, $height] = [imagesx($image), imagesy($image)];
            $scale = min(1, self::MAX_EDGE / max($width, $height));

            $target = $scale < 1
                ? imagescale($image, (int) round($width * $scale), (int) round($height * $scale))
                : $image;

            if ($target === false) {
                imagedestroy($image);

                return null;
            }

            // Flatten transparency onto white so PNG/WebP alpha does not go black.
            $canvas = imagecreatetruecolor(imagesx($target), imagesy($target));
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopy($canvas, $target, 0, 0, 0, 0, imagesx($target), imagesy($target));

            ob_start();
            imagejpeg($canvas, null, self::QUALITY);
            $thumbBytes = ob_get_clean();

            imagedestroy($canvas);
            if ($target !== $image) {
                imagedestroy($target);
            }
            imagedestroy($image);

            $thumbPath = self::pathFor($sourcePath);
            Storage::disk($disk)->put($thumbPath, $thumbBytes);

            return $thumbPath;
        } catch (\Throwable $e) {
            // A failed thumbnail must never fail the upload — the original is what matters.
            Log::warning('Thumbnail generation failed', ['path' => $sourcePath, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public static function pathFor(string $sourcePath): string
    {
        $directory = trim(dirname($sourcePath), '.');
        $name = pathinfo($sourcePath, PATHINFO_FILENAME);

        return ($directory ? $directory.'/' : '').'thumbs/'.$name.'.jpg';
    }
}
