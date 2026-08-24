<?php

namespace App\Http\Controllers\Client;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Message;
use App\Models\Photo;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    /** Mensagens carregadas na página do projecto — tecto contra OOM em obras antigas. */
    public const SHOW_MESSAGE_LIMIT = 100;

    /** Fotos carregadas na página do projecto. */
    public const SHOW_PHOTO_LIMIT = 200;

    /** Mensagens carregadas de uma vez no ecrã da conversa. */
    public const CONVERSATION_MESSAGE_LIMIT = 200;

    public function show(Project $project)
    {
        // Gate: client can only see their own projects
        $clientId = auth()->user()->client_id;
        abort_if($project->client_id !== $clientId, 403);

        $project->load(['client', 'phases', 'updates.user']);
        // Load messages with a hard limit to prevent OOM on large projects
        $messages = $project->messages()->reorder()->with('user')->latest()
            ->limit(self::SHOW_MESSAGE_LIMIT)->get()->reverse()->values();
        $project->setRelation('messages', $messages);
        $photos = $project->photos()->limit(self::SHOW_PHOTO_LIMIT)->get();
        $canViewFinancials = $this->canViewFinancials($project);
        $allowComments = Setting::get('allow_comments', '1') === '1';
        $expenses = $canViewFinancials ? $project->expenses()->get() : collect();
        $documents = $project->documents()->where('visibility', 'cliente')->get();
        $payments = $canViewFinancials ? $project->payments()->get() : collect();
        $totalSpent = $expenses->sum('amount');
        $plans = $this->plansSentToClient($project);

        // OBRA-04: a continuidade entre o projecto e a obra, nos dois sentidos.
        //
        // O filtro pelo cliente autenticado está aqui de propósito, e não só na
        // validação da área interna que já impede ligações entre clientes
        // diferentes: se alguma vez existir uma ligação assim — importada,
        // corrigida à mão na base de dados, herdada de uma versão anterior — o
        // portal não é o sítio onde ela se descobre. Um projecto que não é dele
        // não aparece, nem sequer pelo nome.
        //
        // Uma origem na reciclagem devolve nulo pelo SoftDeletes e a secção
        // simplesmente não aparece: a página do cliente não rebenta nem lhe
        // mostra um nome que já não existe.
        $sourceProject = $project->sourceProject()->where('client_id', $clientId)->first();
        $derivedProjects = $project->derivedProjects()->where('client_id', $clientId)->get();

        return view('client.projects.show', compact('project', 'photos', 'expenses', 'documents', 'payments', 'totalSpent', 'canViewFinancials', 'allowComments', 'plans', 'sourceProject', 'derivedProjects'));
    }

    public function messages(Request $request, Project $project)
    {
        abort_if($project->client_id !== auth()->user()->client_id, 403);
        $allowComments = Setting::get('allow_comments', '1') === '1';

        $messages = $project->messages()
            ->with('user')
            ->reorder()
            ->oldest()
            ->limit(self::CONVERSATION_MESSAGE_LIMIT)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'user_name' => $m->user?->name ?? 'Equipe',
                'user_id' => $m->user_id,
                'is_mine' => $m->user_id === auth()->id(),
            ])->values()->toArray();

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($messages);
        }

        return view('client.messages.index', compact('project', 'messages', 'allowComments'));
    }

    public function storeMessage(Request $request, Project $project)
    {
        abort_if($project->client_id !== auth()->user()->client_id, 403);
        abort_unless(Setting::get('allow_comments', '1') === '1', 403);

        $request->validate(['body' => 'required|string|max:1000']);
        $msg = Message::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'body' => $request->body,
        ]);
        broadcast(new MessageSent($msg))->toOthers();

        return response()->json(['ok' => true, 'id' => $msg->id]);
    }

    public function downloadDocument(Project $project, Document $document)
    {
        abort_if($project->client_id !== auth()->user()->client_id, 403);
        abort_if((int) $document->project_id !== (int) $project->id, 404);
        abort_unless($document->isClientVisible(), 403);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->name);
    }

    public function cover(Project $project)
    {
        $this->authorizeClientProject($project);
        abort_unless($project->cover_image_path, 404);

        return $this->mediaResponse($project->cover_image_path);
    }

    public function photo(Request $request, Project $project, Photo $photo)
    {
        $this->authorizeClientProject($project);
        abort_unless((int) $photo->project_id === (int) $project->id, 404);

        $path = $photo->thumbnail_path
            && $request->boolean('thumb')
            && Storage::disk('local')->exists($photo->thumbnail_path)
                ? $photo->thumbnail_path
                : $photo->path;

        return $this->mediaResponse($path);
    }

    /**
     * PORT-01: as plantas como o cliente as vê — e uma versão que não lhe foi
     * enviada não existe.
     *
     * O filtro está aqui, na consulta, e não na vista: uma condição esquecida
     * num `@if` do Blade é um esboço de trabalho entregue por engano, e nenhuma
     * versão sem `sent_to_client_at` chega sequer a ser carregada. Uma planta
     * cujas versões estejam todas por enviar não aparece de todo — é por isso
     * que o `whereHas` acompanha o `with`, com a mesma condição nos dois.
     *
     * @return Collection<int, Plan>
     */
    private function plansSentToClient(Project $project): Collection
    {
        $sent = fn ($query) => $query->whereNotNull('sent_to_client_at');

        return $project->plans()
            ->whereHas('versions', $sent)
            ->with(['versions' => $sent, 'versions.approver'])
            ->get();
    }

    private function canViewFinancials(Project $project): bool
    {
        return $project->show_financials
            && Setting::get('show_client_financials', '1') === '1';
    }

    private function authorizeClientProject(Project $project): void
    {
        abort_if($project->client_id !== auth()->user()->client_id, 403);
    }

    /**
     * Media vive só no disco privado. Um registo sem ficheiro é um 404 — sem
     * isto o `response()` sobre um caminho inexistente devolve 500.
     */
    private function mediaResponse(string $path): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
