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
    public function index()
    {
        $query = Project::query();

        if (auth()->user()->isManager()) {
            $query->where('manager_id', auth()->id());
        }

        $projects = $query->with(['messages' => function ($q) {
            $q->latest()->limit(1);
        }, 'messages.user'])->latest()->get()
            ->sortByDesc(fn ($p) => $p->messages->first()?->created_at);

        return view('admin.messages', compact('projects'));
    }

    public function messages(Project $project)
    {
        $this->authorizeProjectAccess($project);

        $messages = $project->messages()
            ->with('user')
            ->latest()
            ->limit(200)
            ->get()
            ->sortBy('created_at')
            ->values();

        return response()->json($messages->map(fn ($m) => [
            'id' => $m->id,
            'body' => $m->body,
            'created_at' => $m->created_at->format('d/m/Y H:i'),
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
