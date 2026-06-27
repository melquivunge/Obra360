<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectUpdate;

class DashboardController extends Controller
{
    public function index()
    {
        $projectsScope = Project::query();

        if (auth()->user()->isManager()) {
            $projectsScope->where('manager_id', auth()->id());
        }

        $totalProjects = (clone $projectsScope)->count();
        $inProgress = (clone $projectsScope)->where('status', 'em_andamento')->count();
        $completed = (clone $projectsScope)->where('status', 'concluida')->count();
        $paused = (clone $projectsScope)->where('status', 'pausada')->count();
        $totalClients = auth()->user()->isManager()
            ? (clone $projectsScope)->distinct('client_id')->count('client_id')
            : Client::count();
        $totalBudget = (clone $projectsScope)->sum('budget');
        $totalSpent = Expense::whereIn('project_id', (clone $projectsScope)->select('id'))->sum('amount');

        $recentProjects = (clone $projectsScope)->with('client')
            ->latest()
            ->limit(5)
            ->get();

        $recentUpdates = ProjectUpdate::with('project')
            ->whereHas('project', fn ($query) => $this->scopeProjectsForDashboard($query))
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalProjects', 'inProgress', 'completed', 'paused',
            'totalClients', 'totalBudget', 'totalSpent',
            'recentProjects', 'recentUpdates'
        ));
    }

    private function scopeProjectsForDashboard($query)
    {
        if (auth()->user()->isManager()) {
            $query->where('manager_id', auth()->id());
        }

        return $query;
    }
}
