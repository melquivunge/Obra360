<?php

namespace Tests\Feature;

use App\Channels\WebPushChannel;
use App\Channels\WhatsAppChannel;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Notifications\ProjectUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

class ProjectNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function update(): ProjectUpdate
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id, 'name' => 'Obra Central']);

        return ProjectUpdate::create([
            'project_id' => $project->id,
            'type' => 'foto',
            'title' => 'Nova foto da fachada',
            'description' => 'Fachada concluída esta semana.',
            'happened_at' => now(),
        ]);
    }

    public function test_email_is_one_of_the_channels_for_a_user_with_an_address(): void
    {
        $user = User::factory()->create(['email' => 'cliente@example.com']);

        $channels = (new ProjectUpdateNotification($this->update()))->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains(WhatsAppChannel::class, $channels);
        $this->assertContains(WebPushChannel::class, $channels);
    }

    public function test_email_is_skipped_when_there_is_no_valid_address(): void
    {
        $notifiable = new class
        {
            public ?string $email = null;

            public string $name = 'Sem email';
        };

        $this->assertNotContains('mail', (new ProjectUpdateNotification($this->update()))->via($notifiable));
    }

    public function test_the_email_names_the_project_and_links_to_the_portal(): void
    {
        $user = User::factory()->create(['name' => 'Ana', 'email' => 'ana@example.com']);
        $update = $this->update();

        $mail = (new ProjectUpdateNotification($update))->toMail($user);

        $this->assertStringContainsString('Obra Central', $mail->subject);
        $this->assertStringContainsString('Ana', $mail->greeting);
        $this->assertSame('Ver no portal', $mail->actionText);
        $this->assertStringContainsString('/portal/obras/'.$update->project_id, $mail->actionUrl);
    }

    public function test_the_client_user_actually_receives_the_notification(): void
    {
        Notification::fake();

        $client = Client::factory()->create();
        $clientUser = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'client_id' => $client->id,
            'email' => 'dono@example.com',
        ]);
        $project = Project::factory()->create(['client_id' => $client->id]);

        ProjectUpdate::create([
            'project_id' => $project->id,
            'type' => 'fase',
            'title' => 'Fase concluída',
            'happened_at' => now(),
        ]);

        Notification::assertSentTo($clientUser, ProjectUpdateNotification::class);
    }

    public function test_web_push_payload_carries_title_body_and_url(): void
    {
        $update = $this->update();
        $payload = (new ProjectUpdateNotification($update))->toWebPush(User::factory()->create());

        $this->assertStringContainsString('Obra Central', $payload['title']);
        $this->assertSame('Nova foto da fachada', $payload['body']);
        $this->assertStringContainsString('/portal/obras/'.$update->project_id, $payload['url']);
    }

    public function test_web_push_library_is_installed(): void
    {
        // WebPushChannel silently no-ops when the package is missing, so assert it exists.
        $this->assertTrue(class_exists(WebPush::class));
    }
}
