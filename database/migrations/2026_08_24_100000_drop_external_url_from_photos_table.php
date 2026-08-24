<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `photos.external_url` nasceu em 2026_06_27_200001 e nunca foi escrita
     * nem lida por linha nenhuma da aplicação — nem controlador, nem vista,
     * nem teste. Uma coluna assim não é neutra: quem lê o modelo conclui que
     * uma foto pode viver num endereço externo, escreve código a contar com
     * isso, e descobre tarde que não há nada do outro lado.
     *
     * O caso do arquivo que mora fora do servidor já está resolvido onde
     * interessa — `plan_versions.external_url` (PLAN-08), com validação,
     * formulário e as duas vistas a mostrá-lo. As fotos não têm esse problema:
     * são enviadas pelo próprio portal e servidas pela rota autenticada.
     *
     * Nenhuma linha é perdida ao descer: a coluna está vazia em toda a base.
     */
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('external_url');
        });
    }

    /**
     * Repõe a coluna tal como a migração original a criou — mesmo tipo, mesma
     * posição, mesma nulidade — para que o `down()` daquela continue a
     * encontrar o que espera apagar.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('external_url')->nullable()->after('media_type');
        });
    }
};
