<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PLAN-01/PLAN-05: uma planta é um desenho identificado — «Planta baixa —
     * piso 0» — e não um ficheiro. O ficheiro vive em `plan_versions`, porque o
     * mesmo desenho é revisto várias vezes e o valor está em saber qual é a
     * revisão boa.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name', 200);
            // 2d (desenho) ou 3d (modelo). Governa o que a interface promete
            // conseguir mostrar: nenhum formato 3D é pré-visualizável.
            $table->string('discipline', 10)->default('2d');
            $table->string('status', 30)->default('em_elaboracao');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            // PLAN-09: apagar é reversível e não leva o histórico de versões.
            $table->softDeletes();

            $table->index(['project_id', 'status'], 'plans_project_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
