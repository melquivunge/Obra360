<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comentários presos a uma versão concreta de uma planta.
 *
 * O chat do projeto já existia, mas serve para outra coisa: quem trabalha com
 * desenho quer dizer «nesta parede aqui» a respeito de **um** desenho e de uma
 * revisão dele, e quer isso ao lado da planta, não perdido numa conversa de
 * meses. Ficam presos à versão e não à planta de propósito — um pedido feito
 * sobre a v2 continua a fazer sentido lido na v2 depois de a v3 sair.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_version_id')->constrained('plan_versions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['plan_version_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_comments');
    }
};
