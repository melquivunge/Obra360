<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectUpdate;
use App\Traits\AuthorizesProjectAccess;
use Illuminate\Http\Request;

class ProjectPhaseController extends Controller
{
    use AuthorizesProjectAccess;

    public function store(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:'.implode(',', array_keys(ProjectPhase::STATUSES)),
            'percentage' => 'required|integer|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date',
        ]);
        $data['project_id'] = $project->id;
        $data['sort_order'] = $data['sort_order'] ?? $project->phases()->count();
        ProjectPhase::create($data);
        ProjectUpdate::log($project->id, 'Nova fase: '.$data['name'], 'fase');

        return back()->with('success', 'Fase adicionada.');
    }

    public function update(Request $request, Project $project, ProjectPhase $phase)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $phase);

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'status' => 'required|in:'.implode(',', array_keys(ProjectPhase::STATUSES)),
            'percentage' => 'required|integer|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date',
        ]);
        $phase->update($data);
        ProjectUpdate::log($project->id, 'Fase atualizada: '.$phase->name.' — '.$data['percentage'].'%', 'fase');

        return back()->with('success', 'Fase atualizada.');
    }

    public function destroy(Project $project, ProjectPhase $phase)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $phase);

        $phase->delete();

        return back()->with('success', 'Fase removida.');
    }
}
