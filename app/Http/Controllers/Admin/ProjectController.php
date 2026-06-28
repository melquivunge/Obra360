<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Photo;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Traits\AuthorizesProjectAccess;
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

        ProjectUpdate::log($project->id, 'Obra criada', 'status');

        return redirect()->route('admin.projects.show', $project)->with('success', 'Obra criada com sucesso.');
    }

    public function show(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $project->load(['client', 'manager', 'phases', 'photos.phase', 'expenses', 'documents', 'updates.user', 'messages.user', 'payments']);
        $phases = $project->phases;
        $totalSpent = $project->totalSpent();
        $payments = $project->payments;

        return view('admin.projects.show', compact('project', 'phases', 'totalSpent', 'payments'));
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
            'manager_id' => 'nullable|exists:users,id',
            'show_financials' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
        $data['show_financials'] = $request->boolean('show_financials');
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

        return redirect()->route('admin.projects.show', $project)->with('success', 'Obra atualizada.');
    }

    public function destroy(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Obra removida.');
    }

    // ── Phases ───────────────────────────────────────────────

    public function storePhase(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:'.implode(',', array_keys(ProjectPhase::STATUSES)),
            'percentage' => 'required|integer|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date',
        ]);
        $data['project_id'] = $project->id;
        $data['sort_order'] = $data['sort_order'] ?? $project->phases()->count();
        ProjectPhase::create($data);
        ProjectUpdate::log($project->id, 'Nova fase: '.$data['name'], 'fase');

        return back()->with('success', 'Fase adicionada.');
    }

    public function updatePhase(Request $request, Project $project, ProjectPhase $phase)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $phase);

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:'.implode(',', array_keys(ProjectPhase::STATUSES)),
            'percentage' => 'required|integer|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date',
        ]);
        $phase->update($data);
        ProjectUpdate::log($project->id, 'Fase atualizada: '.$phase->name.' — '.$data['percentage'].'%', 'fase');

        return back()->with('success', 'Fase atualizada.');
    }

    public function destroyPhase(Project $project, ProjectPhase $phase)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $phase);

        $phase->delete();

        return back()->with('success', 'Fase removida.');
    }

    // ── Photos ───────────────────────────────────────────────

    public function storePhoto(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $request->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'required|file|mimes:jpeg,jpg,png,webp,gif,mp4,mov,avi|max:51200',
            'phase_id' => 'nullable|exists:project_phases,id,project_id,'.$project->id,
            'caption' => 'nullable|string|max:500',
            'taken_at' => 'nullable|date',
        ]);

        foreach ($request->file('photos') as $file) {
            $mime = $file->getMimeType();
            $isVideo = str_starts_with($mime, 'video/');
            $path = $file->store("projects/{$project->id}/photos", 'local');
            Photo::create([
                'project_id' => $project->id,
                'phase_id' => $request->phase_id,
                'caption' => $request->caption,
                'path' => $path,
                'media_type' => $isVideo ? 'video' : 'image',
                'taken_at' => $request->taken_at ?: now()->toDateString(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        ProjectUpdate::log($project->id, 'Fotos adicionadas', 'foto');

        return back()->with('success', 'Fotos enviadas com sucesso.');
    }

    public function destroyPhoto(Project $project, Photo $photo)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $photo);

        if ($photo->path) {
            Storage::disk('local')->delete($photo->path);
            Storage::disk('public')->delete($photo->path);
        }
        $photo->delete();

        return back()->with('success', 'Foto removida.');
    }

    public function cover(Project $project)
    {
        $this->authorizeProjectAccess($project);
        abort_unless($project->cover_image_path, 404);

        $disk = $this->mediaDisk($project->cover_image_path);

        return Storage::disk($disk)->response($project->cover_image_path);
    }

    public function photo(Project $project, Photo $photo)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $photo);

        $disk = $this->mediaDisk($photo->path);

        return Storage::disk($disk)->response($photo->path);
    }

    // ── Expenses ─────────────────────────────────────────────

    public function storeExpense(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'category' => 'required|in:'.implode(',', array_keys(Expense::CATEGORIES)),
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
            'spent_at' => 'required|date',
            'supplier' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:1000',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);
        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store("projects/{$project->id}/receipts", 'local');
        }

        Expense::create($data);
        ProjectUpdate::log($project->id, 'Despesa: '.$data['description'].' — R$ '.number_format($data['amount'], 2, ',', '.'), 'despesa');

        return back()->with('success', 'Despesa registrada.');
    }

    public function destroyExpense(Project $project, Expense $expense)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $expense);

        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();

        return back()->with('success', 'Despesa removida.');
    }

    // ── Documents ────────────────────────────────────────────

    public function storeDocument(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $request->validate([
            'file' => 'required|file|mimes:pdf,docx,xlsx,xls,doc,jpg,jpeg,png,webp|max:51200',
            'name' => 'required|string|max:200',
            'type' => 'required|in:'.implode(',', array_keys(Document::TYPES)),
            'visibility' => 'required|in:interno,cliente',
            'notes' => 'nullable|string|max:500',
        ]);

        $path = $request->file('file')->store("projects/{$project->id}/documents", 'local');

        $doc = Document::create([
            'project_id' => $project->id,
            'name' => $request->name,
            'type' => $request->type,
            'visibility' => $request->visibility,
            'path' => $path,
            'notes' => $request->notes,
            'uploaded_by' => auth()->id(),
        ]);

        if ($doc->isClientVisible()) {
            ProjectUpdate::log($project->id, 'Documento liberado: '.$doc->name, 'documento');
        }

        return back()->with('success', 'Documento enviado.');
    }

    public function destroyDocument(Project $project, Document $document)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $document);

        Storage::disk('local')->delete($document->path);
        Storage::disk('public')->delete($document->path);
        $document->delete();

        return back()->with('success', 'Documento removido.');
    }

    public function downloadDocument(Project $project, Document $document)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $document);

        $disk = Storage::disk('local')->exists($document->path) ? 'local' : 'public';

        return Storage::disk($disk)->download($document->path, $document->name);
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

    private function assertBelongsToProject(Project $project, object $child): void
    {
        abort_unless((int) $child->project_id === (int) $project->id, 404);
    }

    private function mediaDisk(string $path): string
    {
        return Storage::disk('local')->exists($path) ? 'local' : 'public';
    }
}
