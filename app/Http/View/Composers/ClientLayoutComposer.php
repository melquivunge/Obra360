<?php

namespace App\Http\View\Composers;

use App\Models\Message;
use App\Models\Project;
use App\Models\ProjectUpdate;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

/**
 * Prepara os dados da navegação do portal do cliente.
 *
 * Regra crítica: **nunca** guardar modelos Eloquent (ou coleções deles) em
 * cache. O driver de cache em produção é `database`, portanto o valor é
 * serializado; se as classes mudarem entre deploys a desserialização devolve
 * `__PHP_Incomplete_Class` e qualquer uso do valor rebenta com um 500 para
 * todos os clientes. Só guardamos escalares e arrays de escalares, e validamos
 * a forma do que vem da cache antes de a usar.
 */
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
            ? ($this->cachedArray("client_first_project_{$clientId}", 120, function () use ($clientId) {
                $project = Project::where('client_id', $clientId)
                    ->select(['id', 'client_id', 'name'])
                    ->first();

                return $project ? ['id' => $project->id, 'name' => $project->name] : [];
            }) ?: null)
            : null;

        $lastViewed = session('messages_last_viewed_at')
            ? Carbon::createFromTimestamp(session('messages_last_viewed_at'))
            : now()->subDays(30);

        // Separate cache keys so display messages (take 6) and the badge count stay independent.
        // Both keyed to a 30-second bucket so stale data is bounded to 30 s.
        $bucket = (int) (now()->timestamp / 30);

        $recentUnreadMsgs = collect($clientId
            ? $this->cachedArray("client_unread_msgs_{$userId}_{$bucket}", 30, function () use ($clientId, $userId, $lastViewed) {
                return Message::whereHas('project', fn ($q) => $q->where('client_id', $clientId))
                    ->where('user_id', '!=', $userId)
                    ->where('created_at', '>', $lastViewed)
                    ->with(['project:id,name', 'user:id,name'])
                    ->latest()
                    ->take(6)
                    ->get()
                    ->map(fn (Message $msg) => [
                        'id' => $msg->id,
                        'body' => (string) $msg->body,
                        'author' => $msg->user?->name ?? 'Equipa',
                        'project_id' => $msg->project?->id,
                        'project_name' => $msg->project?->name ?? '—',
                        'created_at' => $msg->created_at?->timestamp,
                    ])
                    ->all();
            })
            : [])->map(function (array $msg) {
                $msg['href'] = $msg['project_id']
                    ? route('portal.projects.messages', $msg['project_id'])
                    : route('portal.dashboard');
                $msg['time_ago'] = $msg['created_at']
                    ? Carbon::createFromTimestamp($msg['created_at'])->diffForHumans()
                    : '';

                return $msg;
            });

        // COUNT query is separate so the badge correctly shows the real unread total (not capped at 6).
        $unreadMessages = $clientId
            ? $this->cachedInt("client_unread_count_{$userId}_{$bucket}", 30, function () use ($clientId, $userId, $lastViewed) {
                return Message::whereHas('project', fn ($q) => $q->where('client_id', $clientId))
                    ->where('user_id', '!=', $userId)
                    ->where('created_at', '>', $lastViewed)
                    ->count();
            })
            : 0;

        $recentUpdates = collect($clientId
            ? $this->cachedArray("client_updates_{$clientId}", 60, function () use ($clientId) {
                return ProjectUpdate::whereHas('project', fn ($q) => $q->where('client_id', $clientId))
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(fn (ProjectUpdate $update) => [
                        'id' => $update->id,
                        'title' => (string) $update->title,
                        'type_label' => ProjectUpdate::TYPES[$update->type] ?? (string) $update->type,
                        'created_at' => $update->created_at?->timestamp,
                    ])
                    ->all();
            })
            : [])->map(function (array $update) {
                $update['time_ago'] = $update['created_at']
                    ? Carbon::createFromTimestamp($update['created_at'])->diffForHumans()
                    : '';

                return $update;
            });

        $msgHref = $firstProject ? route('portal.projects.messages', $firstProject['id']) : route('portal.dashboard');
        $onDash = request()->routeIs('portal.dashboard');
        $onMessages = request()->routeIs('portal.projects.messages');
        $onProfile = request()->routeIs('profile.edit');

        $view->with(compact(
            'firstProject', 'recentUnreadMsgs', 'unreadMessages',
            'recentUpdates', 'msgHref', 'onDash', 'onMessages', 'onProfile'
        ));
    }

    /**
     * Lê um array da cache, recalculando sempre que o valor guardado não tiver
     * a forma esperada (por exemplo, dados envenenados de um deploy anterior).
     *
     * @return array<mixed>
     */
    private function cachedArray(string $key, int $ttl, Closure $producer): array
    {
        try {
            $cached = Cache::get($key);

            if (is_array($cached) && $this->isPlainData($cached)) {
                return $cached;
            }
        } catch (Throwable) {
            // Valor ilegível na cache — segue para recálculo.
        }

        return $this->refresh($key, $ttl, $producer, []);
    }

    /**
     * Lê um inteiro da cache, recalculando se o valor guardado não for numérico.
     */
    private function cachedInt(string $key, int $ttl, Closure $producer): int
    {
        try {
            $cached = Cache::get($key);

            if (is_int($cached) || (is_string($cached) && ctype_digit($cached))) {
                return (int) $cached;
            }
        } catch (Throwable) {
            // Valor ilegível na cache — segue para recálculo.
        }

        return (int) $this->refresh($key, $ttl, $producer, 0);
    }

    /**
     * Recalcula o valor e volta a guardá-lo. Se a escrita/leitura falhar, o
     * layout continua a renderizar com o valor produzido (ou o de recurso).
     */
    private function refresh(string $key, int $ttl, Closure $producer, mixed $fallback): mixed
    {
        try {
            Cache::forget($key);
        } catch (Throwable) {
            // Ignorado de propósito: a cache não pode partir o layout.
        }

        try {
            $fresh = $producer();
        } catch (Throwable) {
            return $fallback;
        }

        try {
            Cache::put($key, $fresh, $ttl);
        } catch (Throwable) {
            // Ignorado de propósito: o valor fresco já é suficiente para esta resposta.
        }

        return $fresh;
    }

    /**
     * Garante que o valor só contém escalares, null e arrays destes — ou seja,
     * nada que dependa de uma classe existir para ser desserializado.
     */
    private function isPlainData(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (! $this->isPlainData($item)) {
                    return false;
                }
            }

            return true;
        }

        return $value === null || is_scalar($value);
    }
}
