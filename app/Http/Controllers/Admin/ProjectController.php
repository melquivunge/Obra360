<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Photo;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Traits\AuthorizesProjectAccess;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    use AuthorizesProjectAccess;

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

        return view('admin.projects.create', compact('clients', 'managers'));
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
            'status' => 'required|in:'.implode(',', array_keys(Project::STATUSES)),
            'type' => 'required|in:'.implode(',', array_keys(Project::TYPES)),
            'manager_id' => 'nullable|exists:users,id',
            'show_financials' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
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
        ProjectUpdate::log($project->id, $project->isSiteWork() ? 'Obra criada' : 'Projecto criado', 'status');

        return redirect()->route('admin.projects.show', $project)
            ->with('success', $project->isSiteWork() ? 'Obra criada com sucesso.' : 'Projecto criado com sucesso.');
    }

    public function show(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $project->load([
            'client', 'manager', 'phases', 'expenses', 'documents', 'updates.user',
            'messages.user', 'payments',
            // As versões vêm juntas: o separador das plantas mostra o histórico
            // inteiro, e sem isto seriam duas consultas por planta.
            'plans.versions.uploader',
        ]);
        $phases = $project->phases;
        $totalSpent = $project->totalSpent();
        $payments = $project->payments;
        $photos = $this->filteredPhotos($request, $project);

        return view('admin.projects.show', compact('project', 'phases', 'totalSpent', 'payments', 'photos'));
    }

    public function edit(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $clients = Client::orderBy('name')->get();
        $managers = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_MANAGER])->orderBy('name')->get();

        return view('admin.projects.edit', compact('project', 'clients', 'managers'));
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
            'status' => 'required|in:'.implode(',', array_keys(Project::STATUSES)),
            'type' => 'required|in:'.implode(',', array_keys(Project::TYPES)),
            'manager_id' => 'nullable|exists:users,id',
            'show_financials' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
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
        }

        $oldStatus = $project->status;

        if ($request->hasFile('cover_image')) {
            if ($project->cover_image_path) {
                Storage::disk('local')->delete($project->cover_image_path);
                Storage::disk('public')->delete($project->cover_image_path);
            }
            $data['cover_image_path'] = $request->file('cover_image')->store('covers', 'local');
        }

        $project->update($data);

        if ($data['status'] !== $oldStatus) {
            ProjectUpdate::log($project->id, 'Status alterado para: '.Project::STATUSES[$data['status']], 'status');
        }

        return redirect()->route('admin.projects.show', $project)
            ->with('success', $project->isSiteWork() ? 'Obra atualizada.' : 'Projecto atualizado.');
    }

    public function destroy(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $wasSiteWork = $project->isSiteWork();

        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', $wasSiteWork ? 'Obra removida.' : 'Projecto removido.');
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

        return back()->with('success', 'Atualização registrada.');
    }

    /**
     * Gallery filtering by phase, date range and order (FOTO-04).
     *
     * @return Collection<int, Photo>
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
            ->get();
    }
}
