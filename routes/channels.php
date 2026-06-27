<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    if ($user->isAdmin() || $user->isFinancial()) {
        return \App\Models\Project::whereKey($projectId)->exists();
    }

    if ($user->isManager()) {
        return \App\Models\Project::whereKey($projectId)
            ->where('manager_id', $user->id)
            ->exists();
    }

    if ($user->isClient() && $user->client_id) {
        return \App\Models\Project::where('id', $projectId)
            ->where('client_id', $user->client_id)
            ->exists();
    }
    return false;
});
