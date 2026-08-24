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
    /** Mensagens por ler mostradas no menu (o contador conta todas, à parte). */
    private const UNREAD_PREVIEW_LIMIT = 6;

    /** Atualizações recentes mostradas no menu. */
    private const RECENT_UPDATES_LIMIT = 5;

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
                    ->select(['id', 'client_id', 'name', 'type'])
                    ->first();

                // Guardamos a palavra já formada — uma string —, não o modelo
                // nem o tipo em bruto: o menu só precisa dela e a regra de
                // concordância continua a viver no modelo.
                return $project
                    ? [
                        'id' => $project->id,
                        'name' => $project->name,
                        'noun_with_article' => $project->nounWithArticle(),
                    ]
                    : [];
            }) ?: null)
            : null;

        // TIPO-04: o menu não pode chamar «obras» ao que o cliente contratou
        // quando ele só tem projectos de arquitectura ou de design de
        // interiores. Guardamos dois booleanos — nunca modelos.
        $projectMix = $clientId
            ? $this->cachedArray("client_project_mix_{$clientId}", 120, function () use ($clientId) {
                $types = Project::where('client_id', $clientId)
                    ->distinct()
                    ->pluck('type')
                    ->all();

                return [
                    'has_site_work' => array_intersect($types, Project::SITE_TYPES) !== [],
                    'has_design_work' => array_diff($types, Project::SITE_TYPES) !== [],
                ];
            })
            : [];

        $projectsNavLabel = Project::collectionLabel(
            (bool) ($projectMix['has_site_work'] ?? false),
            (bool) ($projectMix['has_design_work'] ?? false),
        );

        $lastViewed = session('messages_last_viewed_at')
            ? Carbon::createFromTimestamp(session('messages_last_viewed_at'))
            : now()->subDays(30);

        // Separate cache keys so display messages (the preview cap) and the badge count stay independent.
        // Both keyed to a 30-second bucket so stale data is bounded to 30 s.
        $bucket = (int) (now()->timestamp / 30);

        $recentUnreadMsgs = collect($clientId
            ? $this->cachedArray("client_unread_msgs_{$userId}_{$bucket}", 30, function () use ($clientId, $userId, $lastViewed) {
                return Message::whereHas('project', fn ($q) => $q->where('client_id', $clientId))
                    ->where('user_id', '!=', $userId)
                    ->where('created_at', '>', $lastViewed)
                    ->with(['project:id,name', 'user:id,name'])
                    ->latest()
                    ->take(self::UNREAD_PREVIEW_LIMIT)
                    ->get()
                    ->map(fn (Message $msg) => [
                        'id' => $msg->id,
                        'body' => (string) $msg->body,
                        'author' => $msg->user?->name ?? 'Equipe',
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

        // O mesmo cuidado da página da obra, e aqui é por obra: o menu junta
        // várias. Uma entrada de despesa traz o valor no título, portanto só
        // passa quando a obra dela mostra o financeiro ao cliente — e o
        // interruptor global entra na chave da cache para não continuar a
        // vazar durante o minuto seguinte a ser desligado.
        $financeiroGlobal = Project::clientFinancialsEnabled();
        $chaveUpdates = "client_updates_{$clientId}_".($financeiroGlobal ? 'fin' : 'nofin');

        $recentUpdates = collect($clientId
            ? $this->cachedArray($chaveUpdates, 60, function () use ($clientId, $financeiroGlobal) {
                return ProjectUpdate::whereHas('project', fn ($q) => $q->where('client_id', $clientId))
                    ->where(function ($q) use ($financeiroGlobal) {
                        $q->whereNotIn('type', ProjectUpdate::FINANCIAL_TYPES);

                        if ($financeiroGlobal) {
                            $q->orWhereHas('project', fn ($p) => $p->where('show_financials', true));
                        }
                    })
                    ->latest()
                    ->take(self::RECENT_UPDATES_LIMIT)
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
        // Uma cache escrita antes desta versão não traz a palavra; «a obra» é o
        // valor de recurso, o mesmo que todos os projectos antigos têm.
        $msgNounWithArticle = $firstProject['noun_with_article'] ?? 'a obra';
        $onDash = request()->routeIs('portal.dashboard');
        $onMessages = request()->routeIs('portal.projects.messages');
        $onProfile = request()->routeIs('profile.edit');

        $view->with(compact(
            'firstProject', 'recentUnreadMsgs', 'unreadMessages',
            'recentUpdates', 'msgHref', 'msgNounWithArticle', 'projectsNavLabel',
            'onDash', 'onMessages', 'onProfile'
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
