<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Traits\AuthorizesProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectExpenseController extends Controller
{
    use AuthorizesProjectAccess;

    public function store(Request $request, Project $project)
    {
        $this->authorizeProjectAccess($project);

        $data = $request->validate([
            'category' => 'required|in:'.implode(',', array_keys(Expense::CATEGORIES)),
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
            'spent_at' => 'required|date',
            'supplier' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:1000',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);
        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store("projects/{$project->id}/receipts", 'local');
        }

        Expense::create($data);
        ProjectUpdate::log($project->id, 'Despesa: '.$data['description'].' — '.money($data['amount']), 'despesa');

        return back()->with('success', 'Despesa registrada.');
    }

    public function update(Request $request, Project $project, Expense $expense)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $expense);

        $data = $request->validate([
            'category' => 'required|in:'.implode(',', array_keys(Expense::CATEGORIES)),
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
            'spent_at' => 'required|date',
            'supplier' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:1000',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) {
                Storage::disk('local')->delete($expense->receipt_path);
            }
            $data['receipt_path'] = $request->file('receipt')->store("projects/{$project->id}/receipts", 'local');
        }

        $expense->update($data);

        return back()->with('success', 'Despesa atualizada.');
    }

    public function destroy(Project $project, Expense $expense)
    {
        $this->authorizeProjectAccess($project);
        $this->assertBelongsToProject($project, $expense);

        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
        }

        $expense->delete();

        return back()->with('success', 'Despesa removida.');
    }
}
