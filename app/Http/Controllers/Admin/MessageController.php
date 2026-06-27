<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        $projects = Project::with(['messages' => function ($q) {
            $q->latest()->limit(1);
        }, 'messages.user'])->latest()->get()
            ->sortByDesc(fn ($p) => $p->messages->first()?->created_at);

        return view('admin.messages', compact('projects'));
    }

    public function messages(Project $project)
    {
        $messages = $project->messages()->with('user')->get();
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
        $request->validate(['body' => 'required|string|max:1000']);
        $msg = Message::create([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
            'body' => $request->body,
        ]);
        broadcast(new \App\Events\MessageSent($msg))->toOthers();
        return response()->json(['ok' => true, 'id' => $msg->id]);
    }
}
