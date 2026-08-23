<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TIPO-01/TIPO-02: cada projecto passa a ter um tipo — construção,
     * remodelação, projecto de arquitectura ou design de interiores.
     *
     * A coluna não é anulável e nasce com `construcao` por omissão, de modo que
     * o próprio ALTER TABLE preenche as linhas que já existem em produção. O
     * backfill a seguir é a rede de segurança: garante o mesmo resultado em
     * motores onde o valor por omissão só valesse para inserções futuras.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('type', 40)->default('construcao')->after('code');
        });

        DB::table('projects')
            ->whereNull('type')
            ->orWhere('type', '')
            ->update(['type' => 'construcao']);

        Schema::table('projects', function (Blueprint $table) {
            $table->index('type', 'projects_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_type_idx');
            $table->dropColumn('type');
        });
    }
};
