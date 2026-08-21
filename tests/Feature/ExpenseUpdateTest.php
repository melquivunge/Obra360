<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function expense(Project $project): Expense
    {
        return Expense::factory()->create([
            'project_id' => $project->id,
            'category' => 'material',
            'description' => 'Cimento',
            'amount' => 300,
            'spent_at' => '2026-04-01',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category' => 'transporte',
            'description' => 'Frete corrigido',
            'amount' => 450.75,
            'spent_at' => '2026-04-02',
            'supplier' => 'Transportes X',
        ], $overrides);
    }

    public function test_admin_can_correct_an_expense(): void
    {
        $project = Project::factory()->create();
        $expense = $this->expense($project);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.expenses.update', [$project, $expense]), $this->payload())
            ->assertRedirect();

        $expense->refresh();
        $this->assertSame('transporte', $expense->category);
        $this->assertSame('Frete corrigido', $expense->description);
        $this->assertSame('450.75', $expense->amount);
    }

    public function test_correcting_the_amount_changes_the_project_total(): void
    {
        $project = Project::factory()->create();
        $expense = $this->expense($project);
        $this->assertSame(300.0, $project->totalSpent());

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.expenses.update', [$project, $expense]), $this->payload(['amount' => 1000]));

        $this->assertSame(1000.0, $project->fresh()->totalSpent());
    }

    public function test_replacing_the_receipt_deletes_the_previous_file(): void
    {
        Storage::fake('local');
        $project = Project::factory()->create();
        $expense = $this->expense($project);
        $expense->update(['receipt_path' => 'projects/1/receipts/antigo.pdf']);
        Storage::disk('local')->put('projects/1/receipts/antigo.pdf', 'conteudo');

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.expenses.update', [$project, $expense]), $this->payload([
                'receipt' => UploadedFile::fake()->create('novo.pdf', 100, 'application/pdf'),
            ]));

        Storage::disk('local')->assertMissing('projects/1/receipts/antigo.pdf');
        $this->assertNotSame('projects/1/receipts/antigo.pdf', $expense->fresh()->receipt_path);
    }

    public function test_invalid_payload_is_rejected(): void
    {
        $project = Project::factory()->create();
        $expense = $this->expense($project);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.expenses.update', [$project, $expense]), $this->payload([
                'category' => 'inexistente', 'amount' => -5,
            ]))
            ->assertSessionHasErrors(['category', 'amount']);
    }

    public function test_expense_from_another_project_is_rejected(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $expense = $this->expense($other);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.projects.expenses.update', [$project, $expense]), $this->payload())
            ->assertNotFound();
    }

    public function test_client_cannot_edit_an_expense(): void
    {
        $project = Project::factory()->create();
        $expense = $this->expense($project);
        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => Client::factory()->create()->id,
        ]);

        $this->actingAs($clientUser)
            ->put(route('admin.projects.expenses.update', [$project, $expense]), $this->payload())
            ->assertForbidden();
    }
}
