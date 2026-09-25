<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanComment;
use App\Models\PlanVersion;
use App\Models\Project;
use App\Models\ProjectUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PORT-01 a PORT-06: as plantas vistas do lado do cliente.
 *
 * Vive fora do Client\ProjectController pela mesma razão que a área interna
 * separou as plantas do projecto: são um recurso com regras próprias, e a
 * principal delas — o que o cliente pode sequer saber que existe — não se
 * parece com nada do resto do portal.
 */
class PlanController extends Controller
{
    /**
     * PORT-03: o que o browser mostra sem descarregar. O tipo é declarado a
     * partir desta lista fechada e nunca adivinhado do ficheiro: servir com um
     * `Content-Type` vindo do que foi carregado é como um SVG ou um HTML
     * disfarçado de planta se torna XSS na origem do portal.
     */
    private const PREVIEW_MIME = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    /**
     * PORT-02: a versão corrente e as anteriores, desde que enviadas.
     *
     * Uma versão só com endereço externo não passa por aqui — a interface liga
     * directamente ao endereço. Reencaminhar para ele a partir de uma rota
     * autenticada faria da aplicação um redireccionador aberto; foi decisão da
     * fatia anterior e mantém-se do lado do cliente.
     */
    public function download(Project $project, Plan $plan, PlanVersion $version)
    {
        $this->authorizeSentVersion($project, $plan, $version);

        abort_if($version->path === null, 404);
        abort_unless(Storage::disk('local')->exists($version->path), 404);

        return Storage::disk('local')->download(
            $version->path,
            $version->original_name ?: $plan->name.' '.$version->label(),
        );
    }

    /**
     * PORT-03: PDF e imagens abrem no browser. Os formatos pesados (dwg, skp,
     * rvt, ifc) e o GLB não têm visualizador e não fingem ter um: dão 404 aqui
     * e a interface só lhes oferece o descarregamento.
     */
    public function preview(Project $project, Plan $plan, PlanVersion $version): StreamedResponse
    {
        $this->authorizeSentVersion($project, $plan, $version);

        abort_if($version->path === null, 404);
        abort_unless(in_array($version->format, PlanVersion::PREVIEWABLE_FORMATS, true), 404);
        abort_unless(isset(self::PREVIEW_MIME[$version->format]), 404);
        abort_unless(Storage::disk('local')->exists($version->path), 404);

        return Storage::disk('local')->response(
            $version->path,
            $version->original_name ?: $plan->name.' '.$version->label(),
            [
                'Content-Type' => self::PREVIEW_MIME[$version->format],
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    /**
     * PORT-04 a PORT-06: o cliente aprova a versão corrente.
     *
     * A aprovação é idempotente por construção. Dois separadores abertos, um
     * duplo clique ou um botão carregado duas vezes chegam aqui a correr um
     * contra o outro; o que decide é a leitura sob bloqueio dentro da
     * transacção, e não o estado que veio no `$version` do route-model binding,
     * que já pode estar velho. Quem chega em segundo não sobrepõe a data nem o
     * autor originais, não escreve segunda linha no histórico e sai com uma
     * mensagem que diz a verdade.
     */
    public function approve(Project $project, Plan $plan, PlanVersion $version)
    {
        $this->authorizeSentVersion($project, $plan, $version);

        // PORT-04: uma versão substituída é legível e descarregável para
        // sempre, mas o que se aprova é o desenho que está em vigor.
        if ($version->isSuperseded()) {
            return $this->backToPlans()->withErrors([
                'plan_version' => __('Esta versão foi substituída por uma revisão mais recente. Só a versão atual pode ser aprovada.'),
            ]);
        }

        $approved = DB::transaction(function () use ($plan, $version): bool {
            // `lockForUpdate` serializa os pedidos concorrentes no MySQL, que é
            // o motor de produção. Em SQLite é um no-op — a transacção continua
            // a garantir a atomicidade da escrita.
            $fresh = PlanVersion::query()
                ->whereKey($version->getKey())
                ->lockForUpdate()
                ->first();

            if ($fresh === null || $fresh->isSuperseded() || $fresh->approved_at !== null) {
                return false;
            }

            // Guardado pelo modelo, e não por um update em massa, para que o
            // AuditObserver registado em AppServiceProvider veja a alteração:
            // é dele que sai o «quem aprovou» do registo de auditoria (PORT-05).
            $fresh->forceFill([
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ])->save();

            $plan->update(['status' => 'aprovada']);

            return true;
        });

        if (! $approved) {
            return $this->backToPlans()->with(
                'success',
                __('A versão :version da planta ":plan" já estava aprovada.', ['version' => $version->label(), 'plan' => $plan->name]),
            );
        }

        // PORT-05: a aprovação é um facto do projecto, não só uma coluna. Entra
        // no histórico pelo mesmo mecanismo que anuncia um documento libertado
        // ou uma planta enviada.
        ProjectUpdate::log(
            $project->id,
            "Planta aprovada pelo cliente: {$plan->name} ({$version->label()})",
            'planta_aprovada',
        );

        return $this->backToPlans()->with(
            'success',
            __('Versão :version da planta «:plan» aprovada.', ['version' => $version->label(), 'plan' => $plan->name]),
        );
    }

    /**
     * PORT-01, e o centro desta fatia.
     *
     * A ordem das recusas é deliberada. Um utilizador de outro cliente leva 403
     * — não há nada a esconder, o projecto não é dele e ele sabe-o. Tudo o
     * resto leva 404, incluindo a versão que existe mas ainda não lhe foi
     * enviada: um 403 confirmaria que o recurso existe, e num estúdio de
     * arquitectura saber que há uma revisão por mostrar já é informação a mais.
     * Para o cliente, um esboço de trabalho não existe.
     */
    /**
     * O cliente comenta uma versão que lhe foi enviada. A porta é a mesma do
     * download, da visualização e da aprovação — `authorizeSentVersion()`, com
     * 404 e não 403 para o que ainda não lhe foi mostrado: um 403 confirmaria
     * que o esboço existe, e isso já é informação a mais.
     */
    public function storeComment(Request $request, Project $project, Plan $plan, PlanVersion $version): RedirectResponse
    {
        $this->authorizeSentVersion($project, $plan, $version);

        $data = $request->validate(['body' => 'required|string|max:2000']);

        PlanComment::create([
            'plan_version_id' => $version->id,
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        return $this->backToPlans()->with('success', __('Comentário enviado.'));
    }

    private function authorizeSentVersion(Project $project, Plan $plan, PlanVersion $version): void
    {
        abort_if($project->client_id !== auth()->user()->client_id, 403);
        abort_unless((int) $plan->project_id === (int) $project->id, 404);
        abort_unless((int) $version->plan_id === (int) $plan->id, 404);
        abort_unless($version->wasSentToClient(), 404);
    }

    /** Volta ao portal já com o separador das plantas aberto. */
    private function backToPlans(): RedirectResponse
    {
        return back()->with('active_tab', 'plantas');
    }
}
