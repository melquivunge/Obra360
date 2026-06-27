<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;

class DashboardController extends Controller
{
    public function index()
    {
        $clientId = auth()->user()->client_id;

        $projects = Project::with('client')
            ->where('client_id', $clientId)
            ->latest('updated_at')
            ->get()
            ->map(function ($project) {
                $cover = $project->photos()->whereIn('media_type', ['image'])->latest()->first();
                $project->cover_url = $cover
                    ? asset('storage/' . $cover->path)
                    : ($project->cover_image_path ? asset('storage/' . $project->cover_image_path) : null);
                return $project;
            });

        return view('client.dashboard', compact('projects'));
    }
}
