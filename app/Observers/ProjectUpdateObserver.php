<?php

namespace App\Observers;

use App\Models\ProjectUpdate;
use App\Notifications\ProjectUpdateNotification;

class ProjectUpdateObserver
{
    public function created(ProjectUpdate $update): void
    {
        $project = $update->project()->with('client.users')->first();

        if (! $project?->client) {
            return;
        }

        foreach ($project->client->users as $user) {
            // Skip the user who triggered the action (they don't need a push)
            if ($user->id === $update->user_id) {
                continue;
            }

            // Create a new instance per user so Queueable state is not shared
            $user->notify(new ProjectUpdateNotification($update));
        }
    }
}
