<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;

class DashboardController extends Controller
{
    public function index()
    {
        $clientId = auth()->user()->client_id;

        $projects = Project::with(['client', 'latestImage'])
            ->where('client_id', $clientId)
            ->latest('updated_at')
            ->get()
            ->map(function ($project) {
                $project->cover_url = $project->latestImage
                    ? asset('storage/' . $project->latestImage->path)
                    : ($project->cover_image_path ? asset('storage/' . $project->cover_image_path) : null);
                return $project;
            });

        return view('client.dashboard', compact('projects'));
    }
}
