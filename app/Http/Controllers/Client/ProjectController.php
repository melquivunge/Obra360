<?php

namespace App\Http\Controllers\Client;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Message;
use App\Models\Photo;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function show(Project $project)
    {
        // Gate: client can only see their own projects
        $clientId = auth()->user()->client_id;
        abort_if($project->client_id !== $clientId, 403);

        $project->load(['client', 'phases', 'updates.user']);
        // Load messages with a hard limit to prevent OOM on large projects
        $messages = $project->messages()->with('user')->latest()->limit(100)->get()->reverse()->values();
        $project->setRelation('messages', $messages);
        $photos = $project->photos()->latest()->limit(200)->get();
        $canViewFinancials = $this->canViewFinancials($project);
        $allowComments = Setting::get('allow_comments', '1') === '1';
        $expenses = $canViewFinancials ? $project->expenses()->get() : collect();
        $documents = $project->documents()->where('visibility', 'cliente')->get();
        $payments = $canViewFinancials ? $project->payments()->get() : collect();
        $totalSpent = $expenses->sum('amount');

        return view('client.projects.show', compact('project', 'photos', 'expenses', 'documents', 'payments', 'totalSpent', 'canViewFinancials', 'allowComments'));
    }

    public function messages(Request $request, Project $project)
    {
        abort_if($project->client_id !== auth()->user()->client_id, 403);
        $allowComments = Setting::get('allow_comments', '1') === '1';

        $messages = $project->messages()
            ->with('user')
            ->oldest()
            ->limit(200)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'user_name' => $m->user?->name ?? 'Equipa',
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

        $disk = Storage::disk('local')->exists($document->path) ? 'local' : 'public';

        return Storage::disk($disk)->download($document->path, $document->name);
    }

    public function cover(Project $project)
    {
        $this->authorizeClientProject($project);
        abort_unless($project->cover_image_path, 404);

        $disk = $this->mediaDisk($project->cover_image_path);

        return Storage::disk($disk)->response($project->cover_image_path);
    }

    public function photo(Request $request, Project $project, Photo $photo)
    {
        $this->authorizeClientProject($project);
        abort_unless((int) $photo->project_id === (int) $project->id, 404);

        $path = $photo->thumbnail_path
            && $request->boolean('thumb')
            && Storage::disk($this->mediaDisk($photo->thumbnail_path))->exists($photo->thumbnail_path)
                ? $photo->thumbnail_path
                : $photo->path;

        $disk = $this->mediaDisk($path);

        return Storage::disk($disk)->response($path);
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

    private function mediaDisk(string $path): string
    {
        return Storage::disk('local')->exists($path) ? 'local' : 'public';
    }
}
