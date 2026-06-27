<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Message;
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

        $project->load(['client', 'phases', 'updates.user', 'messages.user']);
        $photos = $project->photos()->get();
        $canViewFinancials = $this->canViewFinancials($project);
        $expenses = $canViewFinancials ? $project->expenses()->get() : collect();
        $documents = $project->documents()->where('visibility', 'cliente')->get();
        $payments = $canViewFinancials ? $project->payments()->get() : collect();
        $totalSpent = $expenses->sum('amount');

        return view('client.projects.show', compact('project', 'photos', 'expenses', 'documents', 'payments', 'totalSpent', 'canViewFinancials'));
    }

    public function messages(Request $request, Project $project)
    {
        abort_if($project->client_id !== auth()->user()->client_id, 403);

        $messages = $project->messages()
            ->with('user')
            ->latest()
            ->limit(200)
            ->get()
            ->sortBy('created_at')
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

        return view('client.messages.index', compact('project', 'messages'));
    }

    public function storeMessage(Request $request, Project $project)
    {
        abort_if($project->client_id !== auth()->user()->client_id, 403);
        $request->validate(['body' => 'required|string|max:1000']);
        $msg = Message::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'body' => $request->body,
        ]);
        broadcast(new \App\Events\MessageSent($msg))->toOthers();
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

    private function canViewFinancials(Project $project): bool
    {
        return $project->show_financials
            && Setting::get('show_client_financials', '1') === '1';
    }
}
