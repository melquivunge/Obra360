<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create/update/delete on the models listed in AppServiceProvider (SEC-05).
 *
 * This is deliberately generic: one observer beats an audit call sprinkled through
 * every controller, and it cannot be forgotten when a new write path is added.
 */
class AuditObserver
{
    /**
     * Never written to the log — secrets and noise.
     */
    private const REDACTED = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        'updated_at', 'created_at',
    ];

    public function created(Model $model): void
    {
        $this->record('created', $model, $this->clean($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $this->clean($model->getChanges());

        if ($changes === []) {
            return;
        }

        $before = array_intersect_key($this->clean($model->getOriginal()), $changes);

        $this->record('updated', $model, ['de' => $before, 'para' => $changes]);
    }

    public function deleted(Model $model): void
    {
        $this->record('deleted', $model, null);
    }

    public function restored(Model $model): void
    {
        $this->record('restored', $model, null);
    }

    private function record(string $action, Model $model, ?array $changes): void
    {
        $user = auth()->user();

        AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'description' => $this->describe($model),
            'changes' => $changes,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * A human handle for the row, so the log reads without joining back to it
     * (and still reads after the row is force-deleted).
     */
    private function describe(Model $model): string
    {
        foreach (['name', 'title', 'description', 'key'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return mb_substr($value, 0, 120);
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function clean(array $attributes): array
    {
        return array_diff_key($attributes, array_flip(self::REDACTED));
    }
}
