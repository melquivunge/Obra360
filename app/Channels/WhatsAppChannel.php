<?php

namespace App\Channels;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $phoneId = config('services.whatsapp.phone_id');
        $token = config('services.whatsapp.token');

        if (! $phoneId || ! $token) {
            return;
        }

        $phone = $this->normalizePhone(
            $notifiable->phone ?? $notifiable->client?->phone ?? null
        );

        if (! $phone) {
            return;
        }

        $body = $notification->toWhatsApp($notifiable);

        // Empty body means the notification chose to skip delivery (e.g. project was deleted)
        if ($body === '') {
            return;
        }

        try {
            $response = Http::withToken($token)
                ->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => ['body' => $body, 'preview_url' => false],
                ]);

            if ($response->failed()) {
                Log::warning('WhatsApp delivery failed', [
                    'to_hash' => hash('sha256', $phone),
                    'status' => $response->status(),
                    'error' => $response->json('error.message', 'Unknown error'),
                ]);
                // Throw so the queued job can retry
                throw new \RuntimeException(
                    "WhatsApp API returned HTTP {$response->status()}"
                );
            }
        } catch (\RuntimeException|ConnectionException $e) {
            // Re-throw so the queue worker retries (HTTP errors + network failures)
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp notification failed', [
                'to_hash' => hash('sha256', $phone),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        // Must have at least 10 digits (country code + number)
        return strlen($digits) >= 10 ? $digits : null;
    }
}
