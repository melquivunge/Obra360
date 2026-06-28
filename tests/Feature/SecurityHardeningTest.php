<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Photo;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_cannot_access_admin_only_settings_or_clients(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('admin.settings.index'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('admin.clients.index'))
            ->assertForbidden();
    }

    public function test_manager_only_sees_assigned_projects(): void
    {
        $manager = User::factory()->manager()->create();
        $ownProject = Project::factory()->create([
            'name' => 'Assigned project',
            'manager_id' => $manager->id,
        ]);
        Project::factory()->create(['name' => 'Other manager project']);

        $this->actingAs($manager)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee($ownProject->name)
            ->assertDontSee('Other manager project');
    }

    public function test_manager_dashboard_is_scoped_to_assigned_projects(): void
    {
        $manager = User::factory()->manager()->create();
        $ownProject = Project::factory()->create([
            'name' => 'Manager visible dashboard project',
            'manager_id' => $manager->id,
        ]);
        Project::factory()->create(['name' => 'Hidden dashboard project']);

        $this->actingAs($manager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($ownProject->name)
            ->assertDontSee('Hidden dashboard project');
    }

    public function test_nested_phase_cannot_be_updated_through_another_project(): void
    {
        $admin = User::factory()->admin()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        $phase = ProjectPhase::factory()->create(['project_id' => $projectB->id]);

        $this->actingAs($admin)
            ->put(route('admin.projects.phases.update', [$projectA, $phase]), [
                'name' => 'Tampered',
                'status' => 'pendente',
                'percentage' => 10,
            ])
            ->assertNotFound();

        $this->assertNotSame('Tampered', $phase->fresh()->name);
    }

    public function test_nested_payment_cannot_be_marked_paid_through_another_project(): void
    {
        $admin = User::factory()->admin()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        $payment = Payment::factory()->create([
            'project_id' => $projectB->id,
            'client_id' => $projectB->client_id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.projects.payments.mark-paid', [$projectA, $payment]))
            ->assertNotFound();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_receipt_upload_rejects_unapproved_file_types(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.expenses.store', $project), [
                'category' => 'material',
                'description' => 'Unsafe receipt',
                'amount' => 100,
                'spent_at' => now()->toDateString(),
                'receipt' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
            ])
            ->assertSessionHasErrors('receipt');
    }

    public function test_documents_are_stored_on_private_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.documents.store', $project), [
                'file' => UploadedFile::fake()->create('contract.pdf', 10, 'application/pdf'),
                'name' => 'Contrato',
                'type' => 'contrato',
                'visibility' => 'cliente',
            ])
            ->assertRedirect();

        $document = Document::firstOrFail();
        Storage::disk('local')->assertExists($document->path);
        Storage::disk('public')->assertMissing($document->path);
    }

    public function test_photos_are_stored_on_private_disk_and_served_through_authorized_route(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.projects.photos.store', $project), [
                'photos' => [
                    UploadedFile::fake()->image('progress.jpg'),
                ],
            ])
            ->assertRedirect();

        $photo = Photo::firstOrFail();
        Storage::disk('local')->assertExists($photo->path);
        Storage::disk('public')->assertMissing($photo->path);

        $this->actingAs($admin)
            ->get(route('admin.projects.photos.show', [$project, $photo]))
            ->assertOk();
    }

    public function test_client_cannot_download_internal_document(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $project = Project::factory()->create(['client_id' => $client->id]);
        $document = Document::factory()->create([
            'project_id' => $project->id,
            'visibility' => 'interno',
        ]);

        $this->actingAs($user)
            ->get(route('portal.projects.documents.download', [$project, $document]))
            ->assertForbidden();
    }

    public function test_client_financials_are_hidden_when_global_setting_disables_them(): void
    {
        Setting::set('show_client_financials', '0');

        $client = Client::factory()->create();
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'show_financials' => true,
        ]);
        Payment::factory()->create([
            'project_id' => $project->id,
            'client_id' => $client->id,
        ]);

        $this->actingAs($user)
            ->get(route('portal.projects.show', $project))
            ->assertOk()
            ->assertDontSee('Financeiro')
            ->assertDontSee('Cobranças');
    }

    public function test_client_dashboard_hides_budget_when_global_financials_are_disabled(): void
    {
        Setting::set('show_client_financials', '0');

        $client = Client::factory()->create();
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        Project::factory()->create([
            'client_id' => $client->id,
            'show_financials' => true,
            'budget' => 12345,
        ]);

        $this->actingAs($user)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertDontSee('Orçamento')
            ->assertDontSee('12.345');
    }

    public function test_allow_comments_setting_blocks_client_and_admin_message_posts(): void
    {
        Setting::set('allow_comments', '0');

        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();
        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $project = Project::factory()->create(['client_id' => $client->id]);

        $this->actingAs($clientUser)
            ->postJson(route('portal.projects.messages.store', $project), ['body' => 'Blocked'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson(route('admin.projects.messages.store', $project), ['body' => 'Blocked'])
            ->assertForbidden();
    }

    public function test_public_media_migration_dry_run_does_not_copy_or_delete_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $project = Project::factory()->create(['cover_image_path' => 'covers/legacy.jpg']);
        Photo::factory()->create([
            'project_id' => $project->id,
            'path' => 'projects/'.$project->id.'/photos/legacy.jpg',
        ]);

        Storage::disk('public')->put('covers/legacy.jpg', 'cover');
        Storage::disk('public')->put('projects/'.$project->id.'/photos/legacy.jpg', 'photo');

        $exitCode = Artisan::call('obra360:media:migrate-public');

        $this->assertSame(0, $exitCode);
        Storage::disk('local')->assertMissing('covers/legacy.jpg');
        Storage::disk('public')->assertExists('covers/legacy.jpg');
    }

    public function test_public_media_migration_commit_copies_files_without_deleting_sources(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $project = Project::factory()->create(['cover_image_path' => 'covers/legacy.jpg']);
        $photoPath = 'projects/'.$project->id.'/photos/legacy.jpg';
        Photo::factory()->create([
            'project_id' => $project->id,
            'path' => $photoPath,
        ]);

        Storage::disk('public')->put('covers/legacy.jpg', 'cover');
        Storage::disk('public')->put($photoPath, 'photo');

        $exitCode = Artisan::call('obra360:media:migrate-public', ['--commit' => true]);

        $this->assertSame(0, $exitCode);
        Storage::disk('local')->assertExists('covers/legacy.jpg');
        Storage::disk('local')->assertExists($photoPath);
        Storage::disk('public')->assertExists('covers/legacy.jpg');
        Storage::disk('public')->assertExists($photoPath);
    }
}
