<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Setting;

class DashboardController extends Controller
{
    public function index()
    {
        $clientId = auth()->user()->client_id;
        $canViewFinancials = Setting::get('show_client_financials', '1') === '1';

        $projects = Project::with(['client', 'latestImage'])
            ->where('client_id', $clientId)
            ->latest('updated_at')
            ->get()
            ->map(function ($project) {
                $project->cover_url = $project->latestImage
                    ? route('portal.projects.photos.show', [$project, $project->latestImage])
                    : ($project->cover_image_path ? route('portal.projects.cover', $project) : null);

                return $project;
            });

        return view('client.dashboard', compact('projects', 'canViewFinancials'));
    }
}
