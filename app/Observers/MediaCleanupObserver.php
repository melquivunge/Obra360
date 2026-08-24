<?php

namespace App\Observers;

use App\Models\Plan;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

/**
 * Remove do disco privado os arquivos de uma obra ou de uma planta no momento
 * em que a exclusão passa a ser definitiva.
 *
 * Porquê aqui e não no `destroy` do controlador: a exclusão do dia-a-dia é
 * reversível (`SoftDeletes`). Apagar os arquivos nesse momento tornaria o
 * restauro impossível — devolveria as linhas e deixaria a galeria a apontar
 * para o vazio —, que é precisamente o que a reciclagem existe para evitar. O
 * arquivo só deixa de fazer falta quando a linha deixa de poder voltar.
 *
 * Está num observador e não dentro do comando de manutenção porque o
 * `forceDelete` pode vir de qualquer lado — do comando agendado, de uma sessão
 * de `tinker`, de uma futura tela de reciclagem — e todos esses caminhos têm
 * de limpar o disco. O único que não passa por aqui é o `forceDelete` em massa
 * sobre um query builder, que não dispara eventos de modelo: o comando itera
 * modelo a modelo justamente por isso.
 *
 * `forceDeleting` (antes) e não `forceDeleted` (depois): as linhas filhas —
 * fotos, documentos, despesas, versões de plantas — desaparecem por
 * `cascadeOnDelete` no mesmo instante, e depois do apagar já não há como saber
 * que caminhos eram.
 */
class MediaCleanupObserver
{
    public function forceDeleting(Project|Plan $model): void
    {
        $disk = Storage::disk('local');

        foreach ($model->storedMediaPaths() as $path) {
            $disk->delete($path);
        }
    }
}
