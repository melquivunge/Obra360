<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PLAN-02/PLAN-03/PLAN-04: cada revisão da mesma planta é uma versão
     * numerada a partir de 1. Nada aqui é apagado — uma versão substituída
     * continua legível e descarregável.
     *
     * As colunas do portal (`sent_to_client_at`, `approved_by`, `approved_at`)
     * nascem já nesta migração, ainda que só o envio as preencha por agora: a
     * área interna e o portal partilham a mesma tabela, e uma segunda migração
     * sobre uma tabela já povoada em produção seria trabalho evitável.
     */
    public function up(): void
    {
        Schema::create('plan_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->unsignedInteger('version');

            // PLAN-06/PLAN-08: ou o ficheiro no disco privado, ou um endereço
            // externo para os desenhos que excedem o tecto do servidor. Um dos
            // dois é obrigatório; a regra vive na validação, porque um CHECK
            // não é portável entre MySQL e SQLite.
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('format', 10)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('external_url', 2048)->nullable();

            $table->text('change_note')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            // PLAN-04: marcada quando a versão seguinte é carregada.
            $table->timestamp('superseded_at')->nullable();

            // Preenchidas pelo envio ao cliente e, na fatia seguinte, pela
            // aprovação no portal (PORT-04).
            $table->timestamp('sent_to_client_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // Duas versões com o mesmo número na mesma planta seria histórico
            // ambíguo: qual delas é a v2?
            $table->unique(['plan_id', 'version'], 'plan_versions_plan_version_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_versions');
    }
};
