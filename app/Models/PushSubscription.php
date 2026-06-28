<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'endpoint', 'public_key', 'auth_token'])]
class PushSubscription extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $sub): void {
            $sub->endpoint_hash = hash('sha256', $sub->endpoint ?? '');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
