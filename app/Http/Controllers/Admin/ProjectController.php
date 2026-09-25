<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Message;
use App\Models\Photo;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Traits\AuthorizesProjectAccess;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class ProjectController extends Controller
{
    use AuthorizesProjectAccess;

    /**
     * Tectos da página da obra (um separador, uma página).
     *
     * A área interna alinha-se pelo critério que o portal do cliente já
     * seguia: nada é carregado sem limite. O que fica de fora não desaparece —
     * cada separador pagina no próprio pedido, com o seu parâmetro de página,
     * e o gestor chega ao histórico antigo sem sair da página.
     */
    public const MESSAGES_PER_PAGE = 100;

    /** Fotos por página na galeria — a grelha é o que mais pesa a renderizar. */
    public const PHOTOS_PER_PAGE = 60;

    /** Registos por página nos separadores em tabela ou em lista. */
    public const RECORDS_PER_PAGE = 50;

    public function index(Request $request)
    {
        $query = Project::with('client')->latest();
        if (auth()->user()->isManager()) {
            $query->where('manager_id', auth()->id());
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // TIPO-05: filtrar a lista por tipo, ao lado do cliente e do estado.
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        $projects = $query->paginate(20)->withQueryString();
        $clients = Client::orderBy('name')->get();
        $managers = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_MANAGER])->orderBy('name')->get();

        return view('admin.projects.index', compact('projects', 'clients', 'managers'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $managers = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_MANAGER])->orderBy('name')->get();
        $sourceProjects = $this->sourceProjectOptions();

        return view('admin.projects.create', compact('clients', 'managers', 'sourceProjects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:200',
            'code' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:5000',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'revisions_included' => 'nullable|integer|min:0|max:999',
            'status' => 'required|in:'.implode(',', array_keys(Project::STATUSES)),
            'type' => 'required|in:'.implode(',', array_keys(Project::TYPES)),
            // OBRA-01: a origem é opcional, mas o servidor é quem decide se ela
            // é aceitável — a interface só mostra o que faz sentido, e o que a
            // interface mostra nunca é uma garantia.
            'source_project_id' => ['nullable', 'integer', $this->sourceExistsRule(), $this->sourceProjectRule($request)],
            'manager_id' => 'nullable|exists:users,id',
            'show_financials' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], $this->messages());
        $data['show_financials'] = $request->boolean('show_financials', true);
        $data['progress'] = 0;
        if (auth()->user()->isManager()) {
            $data['manager_id'] = auth()->id();
        }

        if ($request->hasFile('cover_image')) {
            $data['cover_image_path'] = $request->file('cover_image')->store('covers', 'local');
        }

        $project = Project::create($data);

        // O histórico é visível ao cliente: quem contratou só o projecto de
        // arquitectura não pode ler «Obra criada».
        ProjectUpdate::log($project->id, $project->isSiteWork() ? 'Obra criada' : 'Projeto criado', 'status');

        $this->logSourceLink($project);

        return redirect()->route('admin.projects.show', $project)
            ->with('success', $project->isSiteWork() ? __('Obra criada com sucesso.') : __('Projeto criado com sucesso.'));
    }

    public function show(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        // As relações que ficam aqui são as que não crescem com o tempo: o
        // cliente, o gestor, as fases (o plano da obra, não o seu histórico) e
        // a ligação de origem. Tudo o que se acumula — despesas, cobranças,
        // documentos, histórico, mensagens, fotos — passou a ser paginado
        // abaixo, uma consulta por separador.
        $project->load([
            'client', 'manager', 'phases',
            // As versões vêm juntas: o separador das plantas mostra o histórico
            // inteiro, e sem isto seriam duas consultas por planta.
            'plans.versions.uploader',
            'plans.versions.comments.author',
            'budgetItems',
            // OBRA-01/OBRA-03: a continuidade nos dois sentidos. A origem
            // respeita o SoftDeletes e vem nula se estiver na reciclagem.
            'sourceProject', 'derivedProjects',
        ]);
        $phases = $project->phases;
        $totalSpent = $project->totalSpent();
        // Os totais das cobranças são somados em SQL: com a lista paginada, um
        // total tirado da página visível estaria errado a partir da segunda.
        $totalPaid = $project->totalPaid();
        $totalPending = $project->totalPending();
        $expenses = $this->paginateTab($project->expenses(), 'despesas');
        $payments = $this->paginateTab($project->payments(), 'pagamentos');
        $documents = $this->paginateTab($project->documents(), 'documentos');
        $updates = $this->paginateTab($project->updates()->with('user'), 'historico');
        $messages = $this->conversationPage($project);
        $photos = $this->filteredPhotos($request, $project);
        // OBRA-02: as plantas aprovadas da origem, lidas onde vivem. Nada é
        // copiado nem guardado em cache: uma correcção no projecto aparece aqui
        // no pedido seguinte.
        $inheritedPlans = $project->inheritedApprovedPlans();

        return view('admin.projects.show', compact(
            'project', 'phases', 'totalSpent', 'totalPaid', 'totalPending',
            'expenses', 'payments', 'documents', 'updates', 'messages',
            'photos', 'inheritedPlans',
        ));
    }

    /**
     * Pagina um separador da página da obra.
     *
     * Cada separador tem o seu parâmetro de página para que dois separadores
     * abertos no mesmo URL não se atropelem, e o `tab` viaja com os links —
     * sem ele o Alpine reabria a página no separador «geral» e a segunda
     * página caía num sítio onde ninguém a pediu.
     *
     * @param  HasMany<covariant Model, Project>  $relation
     * @return LengthAwarePaginator<int, Model>
     */
    private function paginateTab(HasMany $relation, string $tab): LengthAwarePaginator
    {
        return $relation
            ->paginate(self::RECORDS_PER_PAGE, ['*'], $tab.'_pagina')
            ->withQueryString()
            ->appends(['tab' => $tab]);
    }

    /**
     * A conversa da obra — as mensagens mais recentes primeiro, do lado do
     * servidor, mas apresentadas por ordem de leitura.
     *
     * O tecto é o mesmo que o portal do cliente já tinha. A página seguinte
     * deste paginador é a das mensagens ANTERIORES: pagina-se da mais recente
     * para a mais antiga (senão a primeira página seria a conversa de há um
     * ano) e inverte-se apenas o que ficou nesta página.
     *
     * @return LengthAwarePaginator<int, Message>
     */
    private function conversationPage(Project $project): LengthAwarePaginator
    {
        // reorder() porque `messages()` ordena da mais antiga para a mais
        // recente e sem o limpar o `latest()` daqui não teria efeito nenhum.
        $messages = $project->messages()
            ->reorder()
            ->with('user')
            ->latest()
            ->paginate(self::MESSAGES_PER_PAGE, ['*'], 'mensagens_pagina')
            ->withQueryString()
            ->appends(['tab' => 'mensagens']);

        return $messages->setCollection($messages->getCollection()->reverse()->values());
    }

    public function edit(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $clients = Client::orderBy('name')->get();
        $managers = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_MANAGER])->orderBy('name')->get();
        // O próprio projecto fora da lista: a origem de si próprio não existe.
        $sourceProjects = $this->sourceProjectOptions($project);

        return view('admin.projects.edit', compact('project', 'clients', 'managers', 'sourceProjects'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:200',
            'code' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:5000',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'revisions_included' => 'nullable|integer|min:0|max:999',
            'status' => 'required|in:'.implode(',', array_keys(Project::STATUSES)),
            'type' => 'required|in:'.implode(',', array_keys(Project::TYPES)),
            // OBRA-01: a mesma regra da criação, mais a que só existe aqui —
            // uma obra não pode ligar-se a si própria nem fechar um ciclo.
            'source_project_id' => ['nullable', 'integer', $this->sourceExistsRule(), $this->sourceProjectRule($request, $project)],
            'manager_id' => 'nullable|exists:users,id',
            'show_financials' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], $this->messages());
        $data['show_financials'] = $request->boolean('show_financials');

        // FASE-04: o progresso só é editável quando as definições o põem em manual;
        // em modo derivado é a média das fases que manda.
        if (Project::progressIsManual()) {
            $data['progress'] = (int) $request->validate([
                'progress' => 'required|integer|min:0|max:100',
            ])['progress'];
        }

        if (auth()->user()->isManager()) {
            $data['manager_id'] = auth()->id();
            // PROJ-05: o gestor edita a obra que é dele, mas não a transfere
            // para outro cliente — isso mudaria quem a vê no portal, e o âmbito
            // do gestor é a obra, não a carteira. O valor pedido é descartado em
            // silêncio, como já acontecia com `manager_id`; a vista deixou de
            // oferecer o campo para não prometer o que o servidor não faz.
            $data['client_id'] = $project->client_id;
        }

        $oldStatus = $project->status;
        $oldSourceId = $project->source_project_id;

        if ($request->hasFile('cover_image')) {
            if ($project->cover_image_path) {
                Storage::disk('local')->delete($project->cover_image_path);
            }
            $data['cover_image_path'] = $request->file('cover_image')->store('covers', 'local');
        }

        $project->update($data);

        if ($data['status'] !== $oldStatus) {
            ProjectUpdate::log($project->id, 'Status alterado para: '.Project::STATUSES[$data['status']], 'status');
        }

        if ((int) $project->source_project_id !== (int) $oldSourceId) {
            $this->logSourceLink($project);
        }

        return redirect()->route('admin.projects.show', $project)
            ->with('success', $project->isSiteWork() ? __('Obra atualizada.') : __('Projeto atualizado.'));
    }

    public function destroy(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $wasSiteWork = $project->isSiteWork();

        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', $wasSiteWork ? __('Obra removida.') : __('Projeto removido.'));
    }

    // ── Manual Updates ────────────────────────────────────────

    public function storeUpdate(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $request->validate([
            'title' => 'required|string|max:300',
            'description' => 'nullable|string|max:2000',
        ]);
        ProjectUpdate::log($project->id, $request->title, 'geral', $request->description);

        return back()->with('success', __('Atualização registrada.'));
    }

    /**
     * OBRA-01: os projectos que podem ser origem de uma obra, agrupados pelo
     * cliente a que pertencem.
     *
     * É assim que se resolve a dependência entre os dois campos do formulário:
     * o cliente escolhe-se primeiro e a lista de origens tem de o seguir. Uma
     * rota nova que devolvesse JSON a cada mudança de cliente seria mais uma
     * rota, mais um pedido e mais uma autorização a acertar; aqui o mapa inteiro
     * viaja com o formulário e o Alpine mostra só o ramo do cliente escolhido.
     * São os projectos de arquitectura e de design da casa — algumas dezenas no
     * pior dos casos — e nenhum deles leva dados sensíveis, só nome e tipo.
     *
     * A lista não é reduzida às obras do gestor de propósito: o caso que motivou
     * tudo isto é precisamente o desenho ser de um e a construção de outro.
     *
     * @return array<int, array<int, array{id: int, label: string}>>
     */
    private function sourceProjectOptions(?Project $exclude = null): array
    {
        return Project::query()
            ->whereIn('type', Project::SOURCE_TYPES)
            ->when($exclude?->exists, fn ($query) => $query->whereKeyNot($exclude->getKey()))
            ->orderBy('name')
            ->get(['id', 'client_id', 'name', 'type'])
            ->groupBy('client_id')
            ->map(fn ($projects) => $projects->map(fn (Project $source): array => [
                'id' => (int) $source->id,
                'label' => $source->name.' — '.$source->typeLabel(),
            ])->values()->all())
            ->all();
    }

    /**
     * O projecto de origem tem de existir e de estar vivo. `exists` sozinho não
     * conhece o SoftDeletes: aceitaria um projecto na reciclagem e a obra
     * nasceria já com uma ligação que não mostra nada a ninguém.
     */
    private function sourceExistsRule(): Exists
    {
        return Rule::exists('projects', 'id')->whereNull('deleted_at');
    }

    /**
     * OBRA-01, do lado do servidor. A interface só oferece origens do cliente
     * escolhido, mas um pedido não passa pela interface: as quatro recusas são
     * verificadas aqui, e a ordem importa — a primeira que se aplica é a que dá
     * a mensagem, e «é a própria obra» explica melhor do que «não é um projecto
     * de arquitectura».
     */
    private function sourceProjectRule(Request $request, ?Project $project = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($request, $project): void {
            $candidate = Project::find($value);

            if ($candidate === null) {
                // A regra `exists` já recusou; não há nada a acrescentar.
                return;
            }

            if ($project !== null && (int) $candidate->id === (int) $project->id) {
                $fail(__('Uma obra não pode ser a origem de si mesma.'));

                return;
            }

            if (! $candidate->canBeSource()) {
                $fail(__('A origem deve ser um projeto de arquitetura ou de design de interiores.'));

                return;
            }

            if ((int) $candidate->client_id !== $this->effectiveClientId($request, $project)) {
                $fail(__('A origem deve ser um projeto do mesmo cliente.'));

                return;
            }

            if ($project !== null && $project->wouldCycleThroughSource((int) $candidate->id)) {
                $fail(__('Este vínculo criaria um ciclo entre projetos.'));
            }
        };
    }

    /**
     * O cliente com que o pedido vai mesmo ficar.
     *
     * Num `update` feito por um gestor o `client_id` que vier no pedido é
     * descartado e a obra fica no cliente que já tinha. A regra da origem tem de
     * comparar com esse cliente, e não com o que o formulário enviou — senão um
     * pedido forjado com o `client_id` de outro cliente passava a validação e
     * deixava a obra ligada a um projecto que não é do dono dela.
     */
    private function effectiveClientId(Request $request, ?Project $project): int
    {
        if ($project !== null && auth()->user()->isManager()) {
            return (int) $project->client_id;
        }

        return (int) $request->input('client_id');
    }

    /**
     * A ligação é um facto da obra e entra no histórico como os outros. O
     * histórico é visível ao cliente — é ele, aliás, quem mais ganha em ler que
     * a obra continua o projecto que já conhece (OBRA-04).
     */
    private function logSourceLink(Project $project): void
    {
        $source = $project->sourceProject()->first();

        if ($source === null) {
            return;
        }

        ProjectUpdate::log(
            $project->id,
            "Vinculada ao projeto de origem: {$source->name}",
            'origem',
        );
    }

    /**
     * As mensagens de validação que não vale a pena deixar em inglês.
     *
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'source_project_id.exists' => __('O projeto de origem escolhido não existe mais.'),
            'source_project_id.integer' => __('Escolha um projeto de origem válido.'),
        ];
    }

    /**
     * Gallery filtering by phase, date range and order (FOTO-04).
     *
     * @return LengthAwarePaginator<int, Photo>
     */
    private function filteredPhotos(Request $request, Project $project)
    {
        $query = $project->photos()->with('phase')->reorder();

        if ($request->filled('photo_phase')) {
            $query->where('phase_id', $request->photo_phase);
        }

        if ($request->filled('photo_from')) {
            $query->whereDate('taken_at', '>=', $request->photo_from);
        }

        if ($request->filled('photo_to')) {
            $query->whereDate('taken_at', '<=', $request->photo_to);
        }

        return $query
            ->orderBy('taken_at', $request->input('photo_order') === 'antigas' ? 'asc' : 'desc')
            ->orderBy('id', $request->input('photo_order') === 'antigas' ? 'asc' : 'desc')
            // Os filtros viajam com os links da paginação: mudar de página não
            // pode desfazer o filtro que trouxe o gestor até aqui.
            ->paginate(self::PHOTOS_PER_PAGE, ['*'], 'fotos_pagina')
            ->withQueryString()
            ->appends(['tab' => 'fotos']);
    }
}
