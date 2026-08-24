<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectUpdate;
use App\Support\PhaseTemplates;
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

    /**
     * Aplica o modelo de etapas do tipo do projeto.
     *
     * É uma acção explícita e não algo que acontece ao criar o projeto: ver
     * cinco fases aparecerem sem as pedir é intrusivo, e o estúdio pode ter o
     * seu próprio faseamento. Só se oferece a um projeto ainda sem fases.
     *
     * As etapas nascem em 0%, portanto o progresso derivado continua em zero —
     * e o modo manual, se estiver ligado, continua a mandar (FASE-04).
     */
    public function applyTemplate(Project $project)
    {
        $this->authorizeProjectAccess($project);

        abort_unless(PhaseTemplates::availableFor($project), 404);

        $ordem = 0;

        foreach (PhaseTemplates::for($project->type) as $etapa) {
            ProjectPhase::create([
                'project_id' => $project->id,
                'name' => $etapa['name'],
                'description' => $etapa['description'],
                'status' => 'pendente',
                'percentage' => 0,
                'sort_order' => $ordem++,
            ]);
        }

        // Uma entrada e não cinco: o histórico é visível ao cliente, e cinco
        // linhas «Nova fase» no mesmo minuto são ruído, não informação.
        ProjectUpdate::log($project->id, 'Etapas de entrega definidas', 'fase');

        return back()->with('success', 'Etapas de entrega criadas. Ajuste as que precisar.');
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
