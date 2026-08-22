<?php

namespace App\Traits;

use App\Models\Project;

trait AuthorizesProjectAccess
{
    /**
     * O gestor está limitado às obras que gere; admin e financeiro não.
     *
     * Decisão de produto: o financeiro trabalha a parte financeira de TODAS as
     * obras (despesas e cobranças) e lê o resto para contexto, mas nunca toca na
     * estrutura da obra. O âmbito dele é definido pelo middleware `role:` em
     * routes/web.php — que já o mantém fora da escrita estrutural — e não por
     * obra, porque um financeiro nunca é gestor de obra nenhuma. Restringi-lo
     * aqui por `manager_id` dava-lhe 403 em todo o lado, incluindo nas rotas
     * que routes/web.php lhe atribui explicitamente.
     */
    protected function authorizeProjectAccess(Project $project): void
    {
        if (auth()->user()->isManager()) {
            abort_unless((int) $project->manager_id === auth()->id(), 403);
        }
    }

    /**
     * As rotas aninhadas resolvem o filho pelo id, sem o ligar à obra do URL —
     * sem esta verificação seria possível chegar a filhos de outra obra.
     */
    protected function assertBelongsToProject(Project $project, object $child): void
    {
        abort_unless((int) $child->project_id === (int) $project->id, 404);
    }
}
