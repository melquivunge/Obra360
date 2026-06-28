<?php

namespace App\Notifications;

use App\Channels\WebPushChannel;
use App\Channels\WhatsAppChannel;
use App\Models\ProjectUpdate;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ProjectUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ProjectUpdate $update)
    {
        $this->update->loadMissing('project');
    }

    public function via(mixed $notifiable): array
    {
        return [WhatsAppChannel::class, WebPushChannel::class];
    }

    public function toWhatsApp(mixed $notifiable): string
    {
        $project = $this->update->project;

        // Return empty string so WhatsAppChannel can skip delivery gracefully
        if (! $project) {
            return '';
        }

        $company = Setting::get('company_name', 'Obra360');

        $description = $this->update->description
            ? "\n".Str::limit(strip_tags($this->update->description), 120)
            : '';

        return "🏗️ *{$company}* — Actualização na obra *{$project->name}*\n\n"
            ."*{$this->update->title}*{$description}\n\n"
            .'Aceda ao portal para ver todos os detalhes.';
    }

    public function toWebPush(mixed $notifiable): array
    {
        $project = $this->update->project;

        return [
            'title' => '🏗️ '.($project?->name ?? 'Obra360'),
            'body' => $this->update->title,
            'url' => $project
                ? route('portal.projects.show', $this->update->project_id)
                : route('portal.dashboard'),
        ];
    }
}
