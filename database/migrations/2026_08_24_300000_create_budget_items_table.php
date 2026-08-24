<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADV-04: o orçamento deixa de ser um número só.
 *
 * As linhas são o **plano** por categoria, e não substituem o total da obra —
 * comparam-se com o que foi realmente gasto, que já vem agrupado pelas mesmas
 * categorias das despesas. É daí que sai a pergunta útil: «orçámos 500 mil em
 * material e já vamos em 620 mil».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('category', 40);
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
