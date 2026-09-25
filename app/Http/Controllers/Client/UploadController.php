<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Photo;
use App\Models\PlanVersion;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Setting;
use App\Support\Thumbnail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ADV-03: o cliente envia fotos e documentos pelo portal.
 *
 * Até aqui o portal era só de leitura para o cliente, e isso obrigava-o a
 * mandar por WhatsApp o que quisesse mostrar — que é exactamente o hábito que
 * este produto existe para substituir. Uma foto de uma infiltração ou o
 * contrato assinado devem ficar na obra, não numa conversa.
 *
 * Três decisões que valem por si:
 *
 * 1. O envio pode ser desligado nas definições. Nem toda a empresa quer
 *    receber ficheiros de clientes, e a que não quer não deve ter de explicar
 *    porque é que o botão não faz nada — deixa de existir.
 * 2. O que o cliente envia fica marcado como dele. A galeria da obra é
 *    trabalho da empresa e não pode passar a misturar-se com material do
 *    cliente sem que se veja quem enviou o quê.
 * 3. Os documentos do cliente nascem visíveis para ele — foi ele que os
 *    mandou. Marcá-los como internos esconderia do cliente o que ele próprio
 *    acabou de enviar.
 */
class UploadController extends Controller
{
    public function storePhotos(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeUpload($project);

        $request->validate([
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|file|mimes:jpeg,jpg,png,webp|max:'.$this->maxKilobytes(),
            'caption' => 'nullable|string|max:500',
        ]);

        foreach ($request->file('photos') as $file) {
            $path = $file->store("projects/{$project->id}/photos", 'local');

            Photo::create([
                'project_id' => $project->id,
                'caption' => $request->caption,
                'path' => $path,
                'thumbnail_path' => Thumbnail::generate($path),
                'media_type' => 'image',
                'taken_at' => now()->toDateString(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        $total = count($request->file('photos'));
        ProjectUpdate::log(
            $project->id,
            $total === 1 ? 'O cliente enviou uma foto' : "O cliente enviou {$total} fotos",
            'envio_cliente',
            $request->caption
        );

        return back()->with('success', __('Fotos enviadas. A equipe já consegue vê-las.'));
    }

    public function storeDocument(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeUpload($project);

        $data = $request->validate([
            'file' => 'required|file|mimes:pdf,docx,xlsx,xls,doc,jpg,jpeg,png,webp|max:'.$this->maxKilobytes(),
            'name' => 'required|string|max:200',
        ]);

        $document = Document::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'type' => 'outro',
            // Nasce visível: foi o cliente que o mandou, escondê-lo dele seria absurdo.
            'visibility' => 'cliente',
            'path' => $request->file('file')->store("projects/{$project->id}/documents", 'local'),
            'uploaded_by' => auth()->id(),
        ]);

        ProjectUpdate::log($project->id, 'O cliente enviou o documento: '.$document->name, 'envio_cliente');

        return back()->with('success', __('Documento enviado. A equipe já consegue vê-lo.'));
    }

    /**
     * A obra tem de ser do cliente autenticado, e o envio tem de estar ligado.
     * Um 403 nos dois casos: aqui não há nada a esconder — o cliente sabe que a
     * obra existe e sabe que tentou enviar.
     */
    private function authorizeUpload(Project $project): void
    {
        abort_if($project->client_id !== auth()->user()->client_id, 403);
        abort_unless(Setting::get('allow_client_uploads', '1') === '1', 403);
    }

    /** O mesmo tecto do servidor que a área interna usa, lido do php.ini. */
    private function maxKilobytes(): int
    {
        return PlanVersion::maxUploadKilobytes();
    }
}
