<?php

namespace App\Observers;

use App\Models\ProjectUpdate;
use App\Models\Setting;
use App\Notifications\ProjectUpdateNotification;

class ProjectUpdateObserver
{
    /**
     * Settings key that enables notifications for one update type (CONF-04).
     */
    public static function settingKey(string $type): string
    {
        return 'notify_'.$type;
    }

    public function created(ProjectUpdate $update): void
    {
        // Admin can silence each event type independently. Default is on, so
        // existing installations keep behaving as before.
        if (Setting::get(self::settingKey($update->type), '1') !== '1') {
            return;
        }

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
