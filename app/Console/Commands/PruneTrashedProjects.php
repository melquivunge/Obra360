<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Política de retenção da reciclagem.
 *
 * Excluir uma obra ou uma planta é `SoftDeletes`: a linha some da interface e
 * pode voltar. Os arquivos ficam no disco de propósito — apagá-los na exclusão
 * do dia-a-dia tornaria o restauro impossível. Só que nada os apagava depois,
 * nunca: as fotos, os recibos, os documentos e as plantas de uma obra removida
 * há dois anos continuavam em `storage/app/private/projects/{id}/` a ocupar um
 * alojamento partilhado onde o disco é finito.
 *
 * É este comando que fecha o ciclo: passado o prazo de retenção a exclusão
 * passa a definitiva, o `forceDelete` leva as linhas filhas por chave
 * estrangeira e o MediaCleanupObserver leva os arquivos.
 *
 * À imagem do `obra360:audit:prune` e do `obra360:media:migrate-public`, o
 * modo por omissão é de ensaio: sem `--commit` conta e mostra, mas não apaga
 * nada. O agendador chama-o explicitamente com `--commit`.
 */
class PruneTrashedProjects extends Command
{
    /**
     * Prazo de retenção por omissão, em dias.
     *
     * Noventa dias é um trimestre inteiro: cobre a exclusão por engano — que
     * se descobre em dias — e ainda o gestor que esteve de fora dois meses e
     * só ao voltar deu pela obra que faltava. Mais do que isso já não é
     * segurança, é arquivo morto a ocupar o disco, e são os arquivos (fotos,
     * plantas, recibos) que enchem a conta, não as linhas. Por isso o prazo é
     * mais curto do que o do registo de auditoria, que só guarda texto.
     */
    public const DEFAULT_DAYS = 90;

    protected $signature = 'obra360:trash:prune
        {--days= : Dias na reciclagem antes da exclusão definitiva (por omissão 90)}
        {--commit : Excluir de verdade; sem esta opção é apenas uma simulação}';

    protected $description = 'Exclui definitivamente obras e plantas que estão na reciclagem há mais tempo do que o prazo de retenção, e remove os arquivos do disco.';

    public function handle(): int
    {
        $days = $this->retentionDays();

        if ($days === null) {
            $this->error('--days tem de ser um número inteiro maior do que zero.');

            return self::FAILURE;
        }

        // Corta no início do dia para o limite ser estável ao longo do dia e
        // ligeiramente conservador: o dia mais antigo é mantido por inteiro.
        $cutoff = now()->subDays($days)->startOfDay();
        $commit = (bool) $this->option('commit');

        $this->line("Prazo de retenção: {$days} dia(s) — alvo: itens na reciclagem desde antes de {$cutoff->toDateTimeString()}.");

        $expiredProjects = Project::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->orderBy('id')
            ->get();

        [$blocked, $removableProjects] = $expiredProjects->partition(
            fn (Project $project): bool => $this->isStillASource($project, $cutoff)
        );

        $expiredPlans = $this->expiredPlans($cutoff);

        $stats = [
            'obras_no_prazo' => $expiredProjects->count(),
            'obras_excluidas' => 0,
            'obras_adiadas' => $blocked->count(),
            'plantas_excluidas' => 0,
            'arquivos_removidos' => 0,
        ];

        foreach ($blocked as $project) {
            $this->warn("Adiada: {$this->label($project)} — ainda é origem de uma obra ativa.");
        }

        foreach ($removableProjects as $project) {
            $stats['arquivos_removidos'] += $this->purge($project, $commit);
            $stats['obras_excluidas']++;
        }

        foreach ($expiredPlans as $plan) {
            $stats['arquivos_removidos'] += $this->purge($plan, $commit);
            $stats['plantas_excluidas']++;
        }

        if ($expiredProjects->isEmpty() && $expiredPlans->isEmpty()) {
            $this->info('Nada a fazer — não há itens fora do prazo na reciclagem.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(['Métrica', 'Total'], collect($stats)->map(fn ($value, $key) => [$key, $value])->values());

        if (! $commit) {
            $this->comment('Simulação: nada foi excluído. Execute novamente com --commit para excluir.');
        }

        return self::SUCCESS;
    }

    /**
     * Exclui um item e devolve quantos arquivos seus existiam mesmo no disco.
     *
     * Os arquivos são contados antes, e só os que existem: uma foto removida
     * uma a uma já teve o arquivo apagado pelo controlador nessa altura, e
     * contá-la aqui daria um número que não corresponde a nada.
     *
     * Quem apaga o arquivo é o MediaCleanupObserver, no `forceDeleting` — daí
     * o `forceDelete()` ser chamado modelo a modelo e não em massa sobre o
     * query builder, que não dispara eventos nenhuns.
     */
    private function purge(Project|Plan $model, bool $commit): int
    {
        $files = collect($model->storedMediaPaths())
            ->filter(fn (string $path): bool => Storage::disk('local')->exists($path))
            ->count();

        if (! $commit) {
            $this->line("Excluiria {$this->label($model)} — {$files} arquivo(s).");

            return $files;
        }

        $model->forceDelete();
        $this->info("Excluída {$this->label($model)} — {$files} arquivo(s) removido(s).");

        return $files;
    }

    /**
     * A obra ainda é a origem de um projecto que não vai desaparecer com ela.
     *
     * OBRA-01: a chave estrangeira é `nullOnDelete`, portanto apagar a origem
     * a sério não rebenta nada — mas deixa a obra derivada sem ligação e sem
     * as plantas aprovadas que herdava, e isso não volta atrás. Enquanto
     * houver uma obra ativa (ou uma ainda dentro do prazo de restauro) a
     * apontar para esta, a exclusão fica adiada. No dia em que a derivada
     * também expirar, as duas saem juntas.
     */
    private function isStillASource(Project $project, Carbon $cutoff): bool
    {
        return Project::withTrashed()
            ->where('source_project_id', $project->getKey())
            ->where(fn ($query) => $query
                ->whereNull('deleted_at')
                ->orWhere('deleted_at', '>=', $cutoff))
            ->exists();
    }

    /**
     * As plantas expiradas que têm de ser tratadas à parte.
     *
     * PLAN-09: apagar uma planta é reversível e mantém o histórico de versões
     * — e os arquivos dessas versões — intacto. Passado o prazo, é o mesmo
     * caso da obra.
     *
     * As plantas de obras que estão na reciclagem ficam todas de fora, tenham
     * expirado ou não: se a obra pode voltar, volta inteira, e se a obra
     * expirar sai com as plantas atrás por chave estrangeira — com os arquivos
     * já contados no `storedMediaPaths()` dela.
     *
     * @return Collection<int, Plan>
     */
    private function expiredPlans(Carbon $cutoff): Collection
    {
        return Plan::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->whereNotIn('project_id', Project::onlyTrashed()->pluck('id'))
            ->orderBy('id')
            ->get();
    }

    private function label(Project|Plan $model): string
    {
        $noun = $model instanceof Plan ? 'planta' : $model->noun();

        return "{$noun} #{$model->getKey()} ({$model->name})";
    }

    /**
     * Devolve o prazo de retenção pedido, ou null se a opção for inválida.
     *
     * Um prazo de zero ou negativo esvaziaria a reciclagem toda, incluindo o
     * que foi apagado por engano há cinco minutos — é recusado em vez de
     * interpretado.
     */
    private function retentionDays(): ?int
    {
        $option = $this->option('days');

        if ($option === null) {
            return self::DEFAULT_DAYS;
        }

        if (! is_numeric($option) || (float) $option !== (float) (int) $option || (int) $option < 1) {
            return null;
        }

        return (int) $option;
    }
}
