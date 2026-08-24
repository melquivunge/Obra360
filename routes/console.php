<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Manutenção agendada
|--------------------------------------------------------------------------
|
| Sessões, cache e filas vivem todas na base de dados (SESSION_DRIVER,
| CACHE_STORE e QUEUE_CONNECTION = database) e o registo de auditoria recebe
| uma linha por cada escrita em nove modelos. Em produção o alojamento é
| partilhado (cPanel/HostGator), portanto espaço e tamanho de base de dados
| são finitos: sem estas tarefas nada é limpo, nunca.
|
| Nada disto corre sozinho. O agendador do Laravel só é acordado por um cron
| que chame `schedule:run` a cada minuto — ver docs/production-readiness.md.
|
| Horas em UTC (config/app.php), escolhidas de madrugada para não competir com
| o uso normal do portal. As tarefas são espaçadas entre si para não haver duas
| a carregar o MySQL partilhado ao mesmo tempo.
|
| Frequências que ficaram deliberadamente de fora:
|  - `cache:prune-stale-tags` só funciona em Redis; aqui a cache é `database`.
|  - `queue:prune-batches` só tem trabalho se se usar `Bus::batch()`, o que não
|    acontece em lado nenhum da aplicação — a tabela `job_batches` está sempre
|    vazia. Acrescentar quando (e se) forem introduzidos lotes.
|  - A tabela `sessions` é limpa pelo próprio Laravel por sorteio a cada
|    pedido (`'lottery' => [2, 100]` em config/session.php), não precisa de
|    tarefa agendada.
|
*/

// Tokens de recuperação de password. Expiram ao fim de 60 minutos, mas as
// linhas ficam na tabela até serem varridas. Diariamente chega e sobra: a
// tabela é minúscula e de hora a hora só gastava processo por nada.
Schedule::command('auth:clear-resets')
    ->dailyAt('03:00');

// Trabalhos falhados (notificações de obra em fila, sobretudo). Sete dias de
// retenção deixam sempre à disposição a semana inteira de falhas para
// diagnóstico; corre uma vez por semana porque a tabela só cresce quando algo
// corre mal, e nesse caso são dezenas de linhas, não milhares.
Schedule::command('queue:prune-failed --hours=168')
    ->weeklyOn(0, '03:30');

// Retenção do registo de auditoria — a tabela que cresce mais depressa.
// Diária e não semanal: apagar um dia de cada vez mantém cada execução curta,
// em vez de acumular sete dias e fazer um delete grande de uma vez só num
// MySQL partilhado. O `--commit` é obrigatório, o comando é ensaio por omissão.
Schedule::command('obra360:audit:prune --commit')
    ->dailyAt('04:00')
    ->withoutOverlapping();

// Retenção da reciclagem. Obras e plantas apagadas ficam recuperáveis durante
// 90 dias; passado esse prazo a exclusão passa a definitiva e os arquivos
// (fotos, recibos, documentos, plantas) saem do disco — sem isto nada os
// apagava, nunca. Semanal e não diária: a reciclagem não cresce todos os dias
// e cada execução mexe no disco, não só na base de dados. Segunda-feira às
// 04:30, depois da limpeza da auditoria, para não haver duas tarefas a carregar
// o alojamento partilhado ao mesmo tempo.
Schedule::command('obra360:trash:prune --commit')
    ->weeklyOn(1, '04:30')
    ->withoutOverlapping();
