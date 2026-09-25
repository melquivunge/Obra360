<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Capas e fotos servidas a partir do disco privado, com o tipo declarado.
 *
 * Sem `Content-Type` explícito o Symfony adivinha-o do ficheiro, e um ficheiro
 * guardado antes das regras actuais de upload — ou chegado por outra via — pode
 * ser lido como `text/html` ou `image/svg+xml` e correr script na origem do
 * portal. O tipo sai daqui, de um mapa fechado, indexado pela extensão do
 * caminho que o próprio servidor gerou no `store()`; o que não estiver no mapa
 * não é servido com um tipo adivinhado, é 404.
 *
 * Mesmo padrão do `Client\PlanController::PREVIEW_MIME`, que já o fazia para as
 * plantas.
 */
trait ServesProjectMedia
{
    /** @var array<string, string> */
    private const MEDIA_MIME = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
    ];

    /** As capas: `cover_image` valida `image|mimes:jpg,jpeg,png,webp`. */
    private const COVER_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * As fotos da galeria, incluindo os vídeos: `photos.*` valida
     * `mimes:jpeg,jpg,png,webp,gif,mp4,mov,avi`. A lista é a mesma nos dois
     * lados — o cliente só envia imagens, mas vê os vídeos que a equipa enviou.
     * As miniaturas saem sempre em `.jpg` (`Thumbnail::pathFor`).
     */
    private const PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'mov', 'avi'];

    private function coverResponse(string $path): StreamedResponse
    {
        return $this->allowlistedMediaResponse($path, self::COVER_EXTENSIONS);
    }

    private function photoResponse(string $path): StreamedResponse
    {
        return $this->allowlistedMediaResponse($path, self::PHOTO_EXTENSIONS);
    }

    /**
     * Media vive só no disco privado. Um registo sem ficheiro é um 404 — sem
     * isto o `response()` sobre um caminho inexistente devolve 500.
     *
     * @param  list<string>  $allowed
     */
    private function allowlistedMediaResponse(string $path, array $allowed): StreamedResponse
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        abort_unless(in_array($extension, $allowed, true), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => self::MEDIA_MIME[$extension],
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
