<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_project_is_recorded(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $project = Project::factory()->create(['name' => 'Obra do Porto']);

        $log = AuditLog::where('auditable_type', Project::class)
            ->where('auditable_id', $project->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Obra do Porto', $log->description);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($admin->name, $log->user_name);
    }

    public function test_updating_records_before_and_after(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $project = Project::factory()->create(['name' => 'Antes']);

        $project->update(['name' => 'Depois']);

        $log = AuditLog::where('action', 'updated')->latest('id')->first();

        $this->assertSame('Antes', $log->changes['de']['name']);
        $this->assertSame('Depois', $log->changes['para']['name']);
    }

    public function test_an_update_that_changes_nothing_is_not_recorded(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $project = Project::factory()->create(['name' => 'Igual']);

        $before = AuditLog::where('action', 'updated')->count();
        $project->update(['name' => 'Igual']);

        $this->assertSame($before, AuditLog::where('action', 'updated')->count());
    }

    public function test_deleting_is_recorded(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $project = Project::factory()->create();

        $project->delete();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Project::class,
            'auditable_id' => $project->id,
            'action' => 'deleted',
        ]);
    }

    public function test_password_and_token_never_reach_the_log(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create();

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->first();

        $this->assertArrayNotHasKey('password', $log->changes);
        $this->assertArrayNotHasKey('remember_token', $log->changes);
        $this->assertStringNotContainsString('password', json_encode($log->changes));
    }

    public function test_only_admin_can_open_the_audit_screen(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.audit.index'))->assertOk();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('admin.audit.index'))->assertForbidden();

        $this->actingAs(User::factory()->financial()->create())
            ->get(route('admin.audit.index'))->assertForbidden();

        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => Client::factory()->create()->id,
        ]);
        $this->actingAs($clientUser)
            ->get(route('admin.audit.index'))->assertForbidden();
    }

    public function test_audit_screen_can_be_filtered_by_action(): void
    {
        $this->actingAs($admin = User::factory()->admin()->create());
        $project = Project::factory()->create(['name' => 'Obra Filtrada']);
        $project->delete();

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['action' => 'deleted']))
            ->assertOk()
            ->assertSee('Obra Filtrada');
    }
}
