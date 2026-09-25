<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string|max:2000',
            'p256dh' => 'required|string|max:500',
            'auth' => 'required|string|max:200',
        ]);

        // Enforce max 10 push subscriptions per user to prevent queue amplification
        if (PushSubscription::where('user_id', auth()->id())->count() >= 10) {
            return response()->json(['error' => __('Limite de inscrições atingido.')], 429);
        }

        PushSubscription::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'endpoint' => $data['endpoint'],
            ],
            [
                'public_key' => $data['p256dh'],
                'auth_token' => $data['auth'],
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|string|max:2000']);

        PushSubscription::where('user_id', auth()->id())
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
