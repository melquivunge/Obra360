<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Project;
use App\Traits\AuthorizesProjectAccess;
use Illuminate\Http\Request;

/**
 * PLAN-01/PLAN-09: a planta em si — o desenho identificado. Os ficheiros são
 * tratados por ProjectPlanVersionController.
 */
class ProjectPlanController extends Controller
{
    use AuthorizesProjectAccess;

    public function store(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'discipline' => 'required|in:'.implode(',', array_keys(Plan::DISCIPLINES)),
            'notes' => 'nullable|string|max:1000',
        ], [], [
            'name' => 'nome da planta',
            'discipline' => 'disciplina',
            'notes' => 'notas',
        ]);

        Plan::create($data + [
            'project_id' => $project->id,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Planta criada. Envie a primeira versão.');
    }

    public function update(Request $request, Project $project, Plan $plan)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $plan);

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'discipline' => 'required|in:'.implode(',', array_keys(Plan::DISCIPLINES)),
            'notes' => 'nullable|string|max:1000',
        ], [], [
            'name' => 'nome da planta',
            'discipline' => 'disciplina',
            'notes' => 'notas',
        ]);

        // O estado não vem do formulário: é consequência do que acontece às
        // versões (carregar, enviar, aprovar). Deixá-lo editável à mão abriria
        // a porta a uma planta «aprovada» que ninguém aprovou.
        $plan->update($data);

        return back()->with('success', 'Planta atualizada.');
    }

    /**
     * PLAN-09: reversível. As versões não são tocadas — o histórico sobrevive
     * ao apagar e volta inteiro se a planta for restaurada.
     */
    public function destroy(Project $project, Plan $plan)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $plan);

        $plan->delete();

        return back()->with('success', 'Planta removida. O histórico de versões foi mantido.');
    }
}
