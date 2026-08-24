<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OBRA-01: a obra fica ligada ao projecto de arquitectura ou de design que
     * lhe deu origem. É por aqui — e só por aqui — que as plantas aprovadas
     * chegam à obra: não há cópia de ficheiros, a planta tem um único dono, e
     * uma correcção no projecto aparece na obra sem ninguém a repetir.
     *
     * A coluna é auto-referente e opcional: a esmagadora maioria das obras
     * nasce sem projecto nosso por trás.
     *
     * `nullOnDelete` trata do apagar definitivo — uma linha removida a sério da
     * tabela deixa a obra sem origem em vez de a deixar a apontar para o vazio.
     * O apagar do dia-a-dia é `SoftDeletes` e nem toca nesta coluna: a ligação
     * sobrevive, a obra deixa de mostrar as plantas herdadas enquanto a origem
     * estiver na reciclagem, e restaurar o projecto devolve a continuidade
     * inteira. Ver Project::sourceProject().
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('source_project_id')
                ->nullable()
                ->after('type')
                ->constrained('projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_project_id');
        });
    }
};
