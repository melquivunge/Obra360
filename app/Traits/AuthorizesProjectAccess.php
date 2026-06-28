<?php

namespace App\Traits;

use App\Models\Project;

trait AuthorizesProjectAccess
{
    protected function authorizeProjectAccess(Project $project): void
    {
        if (auth()->user()->isManager()) {
            abort_unless((int) $project->manager_id === auth()->id(), 403);
        }
        if (auth()->user()->isFinancial()) {
            // Financial role: restrict write access to projects where this user is the manager.
            // If financial users need unrestricted project access, add a financial_id column
            // to projects and update this check accordingly.
            abort_unless((int) $project->manager_id === auth()->id(), 403);
        }
    }
}
