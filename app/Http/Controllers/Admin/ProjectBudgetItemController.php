<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetItem;
use App\Models\Expense;
use App\Models\Project;
use App\Traits\AuthorizesProjectAccess;
use Illuminate\Http\Request;

/**
 * ADV-04: as linhas do orçamento de uma obra.
 *
 * Escrita no mesmo grupo das despesas (`role:admin,financial`): orçamentar é
 * trabalho financeiro, não estrutural — quem lança a despesa é quem planeia o
 * gasto.
 */
class ProjectBudgetItemController extends Controller
{
    use AuthorizesProjectAccess;

    public function store(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'category' => 'required|in:'.implode(',', array_keys(Expense::CATEGORIES)),
            'description' => 'nullable|string|max:200',
            'amount' => 'required|numeric|min:0',
        ]);

        $data['project_id'] = $project->id;
        $data['sort_order'] = $project->budgetItems()->reorder()->max('sort_order') + 1;

        BudgetItem::create($data);

        return back()->with('success', 'Linha do orçamento adicionada.');
    }

    public function update(Request $request, Project $project, BudgetItem $item)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $item);

        $data = $request->validate([
            'category' => 'required|in:'.implode(',', array_keys(Expense::CATEGORIES)),
            'description' => 'nullable|string|max:200',
            'amount' => 'required|numeric|min:0',
        ]);

        $item->update($data);

        return back()->with('success', 'Linha do orçamento atualizada.');
    }

    public function destroy(Project $project, BudgetItem $item)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $item);

        $item->delete();

        return back()->with('success', 'Linha do orçamento removida.');
    }
}
