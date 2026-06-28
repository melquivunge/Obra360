<?php

namespace App\Channels;

use App\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        if (! class_exists(WebPush::class)) {
            Log::info('WebPush skipped: minishlink/web-push not installed. Run: composer require minishlink/web-push');

            return;
        }

        $vapidPublic = config('services.vapid.public_key');
        $vapidPrivate = config('services.vapid.private_key');
        $vapidSubject = config('services.vapid.subject', 'mailto:admin@obra360.com');

        if (! $vapidPublic || ! $vapidPrivate) {
            return;
        }

        $subscriptions = PushSubscription::where('user_id', $notifiable->id)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = $notification->toWebPush($notifiable);

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $vapidSubject,
                'publicKey' => $vapidPublic,
                'privateKey' => $vapidPrivate,
            ],
        ]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
            ]);

            $webPush->queueNotification($subscription, json_encode($payload));
        }

        foreach ($webPush->flush() as $report) {
            if (! $report->isSuccess()) {
                // Subscription expired — remove it
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                } else {
                    Log::warning('WebPush delivery failed', ['reason' => $report->getReason()]);
                }
            }
        }
    }
}
