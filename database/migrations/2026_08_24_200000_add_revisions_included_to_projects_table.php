<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O número de revisões que o contrato inclui. Nulo significa «sem limite
 * acordado», que é comum — e é por isso que a coluna é anulável em vez de ter
 * zero por omissão: zero seria «nenhuma revisão incluída», o oposto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedSmallInteger('revisions_included')->nullable()->after('budget');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('revisions_included');
        });
    }
};
