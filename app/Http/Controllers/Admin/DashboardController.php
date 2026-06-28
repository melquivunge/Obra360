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
        $isManager = auth()->user()->isManager();

        $statsQuery = Project::query()
            ->when($isManager, fn ($q) => $q->where('manager_id', auth()->id()));

        // Single aggregation replaces 6 sequential COUNT/SUM queries
        $stats = (clone $statsQuery)->selectRaw("
            COUNT(*) AS total,
            SUM(status = 'em_andamento') AS ongoing,
            SUM(status = 'concluida') AS completed,
            SUM(status = 'pausada') AS paused,
            COUNT(DISTINCT client_id) AS client_count,
            COALESCE(SUM(budget), 0) AS total_budget
        ")->first();

        $totalProjects = (int) $stats->total;
        $inProgress = (int) $stats->ongoing;
        $completed = (int) $stats->completed;
        $paused = (int) $stats->paused;
        $totalBudget = (float) $stats->total_budget;
        $totalClients = $isManager
            ? (int) $stats->client_count
            : Client::count();
        $totalSpent = Expense::whereIn('project_id', (clone $statsQuery)->select('id'))->sum('amount');

        $recentProjects = (clone $statsQuery)->with('client')
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
