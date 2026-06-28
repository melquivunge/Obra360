<?php

namespace App\Http\View\Composers;

use App\Models\Message;
use App\Models\Project;
use App\Models\ProjectUpdate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ClientLayoutComposer
{
    public function compose(View $view): void
    {
        if (! auth()->check()) {
            return;
        }

        $userId = auth()->id();
        $clientId = auth()->user()->client_id;

        $firstProject = $clientId
            ? Cache::remember("client_first_project_{$clientId}", 120, fn () => Project::where('client_id', $clientId)->select(['id', 'client_id', 'name'])->first()
            )
            : null;

        $lastViewed = session('messages_last_viewed_at')
            ? Carbon::createFromTimestamp(session('messages_last_viewed_at'))
            : now()->subDays(30);

        // Separate cache keys so display messages (take 6) and the badge count stay independent.
        // Both keyed to a 30-second bucket so stale data is bounded to 30 s.
        $bucket = (int) (now()->timestamp / 30);

        $recentUnreadMsgs = $clientId
            ? Cache::remember("client_unread_msgs_{$userId}_{$bucket}", 30, function () use ($clientId, $userId, $lastViewed) {
                return Message::whereHas('project', fn ($q) => $q->where('client_id', $clientId))
                    ->where('user_id', '!=', $userId)
                    ->where('created_at', '>', $lastViewed)
                    ->with(['project', 'user'])
                    ->latest()
                    ->take(6)
                    ->get();
            })
            : collect();

        // COUNT query is separate so the badge correctly shows the real unread total (not capped at 6).
        $unreadMessages = $clientId
            ? Cache::remember("client_unread_count_{$userId}_{$bucket}", 30, function () use ($clientId, $userId, $lastViewed) {
                return Message::whereHas('project', fn ($q) => $q->where('client_id', $clientId))
                    ->where('user_id', '!=', $userId)
                    ->where('created_at', '>', $lastViewed)
                    ->count();
            })
            : 0;

        $recentUpdates = $clientId
            ? Cache::remember("client_updates_{$clientId}", 60, fn () => ProjectUpdate::whereHas('project', fn ($q) => $q->where('client_id', $clientId))
                ->latest()
                ->take(5)
                ->get()
            )
            : collect();

        $msgHref = $firstProject ? route('portal.projects.messages', $firstProject) : route('portal.dashboard');
        $onDash = request()->routeIs('portal.dashboard');
        $onMessages = request()->routeIs('portal.projects.messages');
        $onProfile = request()->routeIs('profile.edit');

        $view->with(compact(
            'firstProject', 'recentUnreadMsgs', 'unreadMessages',
            'recentUpdates', 'msgHref', 'onDash', 'onMessages', 'onProfile'
        ));
    }
}
