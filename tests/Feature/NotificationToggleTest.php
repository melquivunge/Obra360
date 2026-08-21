<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ProjectUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationToggleTest extends TestCase
{
    use RefreshDatabase;

    private function projectWithClientUser(): array
    {
        $client = Client::factory()->create();
        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
        ]);
        $project = Project::factory()->create(['client_id' => $client->id]);

        return [$project, $clientUser];
    }

    public function test_client_is_notified_when_the_event_type_is_enabled(): void
    {
        Notification::fake();
        [$project, $clientUser] = $this->projectWithClientUser();

        ProjectUpdate::create([
            'project_id' => $project->id,
            'type' => 'foto',
            'title' => 'Nova foto',
            'happened_at' => now(),
        ]);

        Notification::assertSentTo($clientUser, ProjectUpdateNotification::class);
    }

    public function test_disabling_an_event_type_silences_it(): void
    {
        Notification::fake();
        Setting::set('notify_foto', '0');
        [$project, $clientUser] = $this->projectWithClientUser();

        ProjectUpdate::create([
            'project_id' => $project->id,
            'type' => 'foto',
            'title' => 'Nova foto',
            'happened_at' => now(),
        ]);

        Notification::assertNotSentTo($clientUser, ProjectUpdateNotification::class);
    }

    public function test_disabling_one_type_does_not_silence_the_others(): void
    {
        Notification::fake();
        Setting::set('notify_foto', '0');
        [$project, $clientUser] = $this->projectWithClientUser();

        ProjectUpdate::create([
            'project_id' => $project->id,
            'type' => 'despesa',
            'title' => 'Nova despesa',
            'happened_at' => now(),
        ]);

        Notification::assertSentTo($clientUser, ProjectUpdateNotification::class);
    }

    public function test_admin_can_save_the_toggles(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update'), [
                'company_name' => 'Obra360',
                'currency' => 'BRL',
                'notify_foto' => '1',
                'notify_despesa' => '0',
            ])
            ->assertRedirect();

        $this->assertSame('1', Setting::get('notify_foto'));
        $this->assertSame('0', Setting::get('notify_despesa'));
    }

    public function test_toggles_appear_on_the_settings_screen(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('name="notify_foto"', false)
            ->assertSee('Notificações');
    }
}
