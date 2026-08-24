<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanVersion;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Traits\AuthorizesProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * PLAN-02 a PLAN-08: as revisões de uma planta. Cada upload é uma versão nova;
 * a anterior fica marcada como substituída e continua descarregável.
 */
class ProjectPlanVersionController extends Controller
{
    use AuthorizesProjectAccess;

    public function store(Request $request, Project $project, Plan $plan)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $plan);

        // LIM-02: um ficheiro maior do que `upload_max_filesize` chega com erro
        // e sem conteúdo. A regra `file` recusa-o com «deve ser um ficheiro»,
        // que não diz a quem enviou 90 MB o que se passou. (O caso irmão — o
        // pedido inteiro acima de `post_max_size`, que nem chega aqui — é
        // tratado por App\Http\Middleware\ExplainTruncatedUpload.)
        $upload = $request->file('file');
        if ($upload instanceof UploadedFile && $upload->getError() === UPLOAD_ERR_INI_SIZE) {
            return back()->withErrors(['file' => PlanVersion::overLimitMessage()]);
        }

        $data = $request->validate($this->rules(), $this->messages(), [
            'file' => 'ficheiro',
            'external_url' => 'endereço externo',
            'change_note' => 'nota de alterações',
        ]);

        $version = DB::transaction(function () use ($request, $project, $plan, $data): PlanVersion {
            // PLAN-02: o número sai do máximo já gravado, sob bloqueio, para
            // que dois uploads simultâneos não reclamem ambos a v2 — o índice
            // único (plan_id, version) recusaria o segundo.
            $number = (int) $plan->versions()->reorder()->lockForUpdate()->max('version') + 1;

            // PLAN-04: a corrente deixa de o ser assim que esta entra.
            $plan->versions()->whereNull('superseded_at')->update(['superseded_at' => now()]);

            $attributes = [
                'plan_id' => $plan->id,
                'version' => $number,
                'change_note' => $data['change_note'] ?? null,
                'uploaded_by' => auth()->id(),
            ];

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $attributes += [
                    // PLAN-06: disco privado, como o resto da media.
                    'path' => $file->store("projects/{$project->id}/plans/{$plan->id}", 'local'),
                    'original_name' => $file->getClientOriginalName(),
                    'format' => strtolower($file->getClientOriginalExtension()),
                    'size' => $file->getSize(),
                ];
            } else {
                // PLAN-08: o desenho vive fora do servidor.
                $attributes['external_url'] = $data['external_url'];
            }

            $plan->update(['status' => $this->statusAfterNewVersion($plan)]);

            return PlanVersion::create($attributes);
        });

        return back()->with('success', "Versão {$version->label()} carregada.");
    }

    /**
     * PLAN-05: a versão corrente passa a estar disponível ao cliente. A entrada
     * no histórico do projecto é o que o cliente vê — e é o mesmo mecanismo que
     * já anuncia um documento libertado.
     */
    public function send(Project $project, Plan $plan, PlanVersion $version)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $plan);
        $this->assertBelongsToPlan($plan, $version);

        if ($version->isSuperseded()) {
            return back()->withErrors([
                'version' => 'Só a versão corrente pode ser enviada ao cliente.',
            ]);
        }

        if (! $version->wasSentToClient()) {
            $version->update(['sent_to_client_at' => now()]);
        }

        $plan->update(['status' => 'enviada']);

        ProjectUpdate::log(
            $project->id,
            "Planta enviada: {$plan->name} ({$version->label()})",
            'planta',
        );

        return back()->with('success', "Versão {$version->label()} enviada ao cliente.");
    }

    /**
     * PLAN-04: qualquer versão é descarregável, substituída ou não.
     *
     * Uma versão que só tem endereço externo não passa por aqui: a interface
     * liga directamente ao endereço. Reencaminhar para ele a partir de uma rota
     * autenticada faria da aplicação um redireccionador aberto.
     */
    public function download(Project $project, Plan $plan, PlanVersion $version)
    {
        // Leitura, e não escrita: o gestor de uma obra descarrega também as
        // plantas do projecto de origem dela (OBRA-02).
        $this->authorizePlanReadAccess($project);
        $this->assertBelongsToProject($project, $plan);
        $this->assertBelongsToPlan($plan, $version);

        abort_if($version->path === null, 404);
        abort_unless(Storage::disk('local')->exists($version->path), 404);

        return Storage::disk('local')->download(
            $version->path,
            $version->original_name ?: $plan->name.' '.$version->label(),
        );
    }

    /**
     * As versões resolvem-se pelo id, sem ligação à planta do URL — sem isto
     * chegava-se às versões de outra planta.
     */
    private function assertBelongsToPlan(Plan $plan, PlanVersion $version): void
    {
        abort_unless((int) $version->plan_id === (int) $plan->id, 404);
    }

    /**
     * PLAN-05: uma planta que o cliente já viu — ou aprovou — não volta a «em
     * elaboração» quando chega uma revisão: fica «substituída», que é o que de
     * facto aconteceu ao desenho que ele tinha em mãos.
     */
    private function statusAfterNewVersion(Plan $plan): string
    {
        return in_array($plan->status, ['enviada', 'aprovada'], true)
            ? 'substituida'
            : $plan->status;
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return [
            // PLAN-07. `extensions:` e não `mimes:` — ver PlanVersion::FORMATS.
            'file' => [
                'nullable',
                'required_without:external_url',
                'file',
                'extensions:'.implode(',', PlanVersion::FORMATS),
                'max:'.PlanVersion::maxUploadKilobytes(),
            ],
            // PLAN-08: a alternativa ao ficheiro, nunca o acompanhamento dele.
            'external_url' => [
                'nullable',
                'required_without:file',
                'prohibits:file',
                'url:http,https',
                'max:2048',
            ],
            'change_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        $formats = strtoupper(implode(', ', PlanVersion::FORMATS));

        return [
            'file.required_without' => 'Escolha um ficheiro ou indique um endereço externo para esta versão.',
            'external_url.required_without' => 'Escolha um ficheiro ou indique um endereço externo para esta versão.',
            'external_url.prohibits' => 'Uma versão guarda o ficheiro ou o endereço externo, não os dois.',
            'file.extensions' => "Formato não aceite. São aceites: {$formats}.",
            'file.max' => PlanVersion::overLimitMessage(),
        ];
    }
}
