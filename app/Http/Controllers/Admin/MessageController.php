<?php

namespace App\Http\Controllers\Admin;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /** Obras por página na lista de conversas. */
    public const PROJECTS_PER_PAGE = 20;

    /** Mensagens carregadas de uma vez numa conversa. */
    public const CONVERSATION_MESSAGE_LIMIT = 200;

    public function index()
    {
        $query = Project::query();

        if (auth()->user()->isManager()) {
            $query->where('manager_id', auth()->id());
        }

        // A data da última mensagem passa a vir da própria consulta, numa
        // subconsulta correlacionada. Antes carregavam-se todas as obras
        // visíveis para as ordenar em PHP com `sortByDesc()`: o custo crescia
        // com o número de obras, em memória e em tempo, e não havia tecto.
        //
        // O `reorder()` é obrigatório: `messages()` traz um ORDER BY que não
        // pertence a uma agregação e o MySQL em ONLY_FULL_GROUP_BY recusa-o —
        // a mesma armadilha documentada em `Plan::nextVersionNumber()`.
        $lastMessageAt = Message::query()
            ->reorder()
            ->selectRaw('MAX(messages.created_at)')
            ->whereColumn('messages.project_id', 'projects.id');

        $projects = $query
            ->select('projects.*')
            ->addSelect(['last_message_at' => $lastMessageAt])
            // O mesmo `reorder()`, por outro motivo: `messages()` ordena da mais
            // antiga para a mais recente, e sem o limpar o `latest()` daqui era
            // ignorado — a lista mostrava e ordenava pela PRIMEIRA mensagem da
            // obra, não pela última.
            ->with([
                'messages' => fn ($q) => $q->reorder()->latest()->limit(1),
                'messages.user',
            ])
            // Uma obra sem mensagens tem `last_message_at` nulo e cai para o
            // fim, tal como caía na ordenação em PHP. O desempate por data de
            // criação preserva a ordem que o `latest()` anterior já dava.
            ->orderByDesc('last_message_at')
            ->orderByDesc('projects.created_at')
            ->orderByDesc('projects.id')
            ->paginate(self::PROJECTS_PER_PAGE);

        return view('admin.messages', compact('projects'));
    }

    public function messages(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $messages = $project->messages()
            ->with('user')
            ->latest()
            ->limit(self::CONVERSATION_MESSAGE_LIMIT)
            ->get()
            ->sortBy('created_at')
            ->values();

        return response()->json($messages->map(fn ($m) => [
            'id' => $m->id,
            'body' => $m->body,
            'created_at' => $m->created_at->format(app()->getLocale() === 'en' ? 'm/d/Y H:i' : 'd/m/Y H:i'),
            'user_name' => $m->user?->name ?? 'Usuário',
            'user_id' => $m->user_id,
            'is_mine' => $m->user_id === auth()->id(),
        ]));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);
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

    private function authorizeProjectAccess(Project $project): void
    {
        if (auth()->user()->isManager()) {
            abort_unless((int) $project->manager_id === auth()->id(), 403);
        }
    }
}
