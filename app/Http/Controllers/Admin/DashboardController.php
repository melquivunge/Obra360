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
        $totalProjects = Project::count();
        $inProgress = Project::where('status', 'em_andamento')->count();
        $completed = Project::where('status', 'concluida')->count();
        $paused = Project::where('status', 'pausada')->count();
        $totalClients = Client::count();
        $totalBudget = Project::sum('budget');
        $totalSpent = Expense::sum('amount');

        $recentProjects = Project::with('client')
            ->latest()
            ->limit(5)
            ->get();

        $recentUpdates = ProjectUpdate::with('project')
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalProjects', 'inProgress', 'completed', 'paused',
            'totalClients', 'totalBudget', 'totalSpent',
            'recentProjects', 'recentUpdates'
        ));
    }
}
