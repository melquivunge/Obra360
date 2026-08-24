<?php

namespace App\Support;

use App\Models\Project;

/**
 * Etapas de entrega pré-definidas por tipo de projeto.
 *
 * Porquê constantes e não uma tabela: estas etapas são conhecimento do ofício,
 * não dados do cliente. As de arquitetura seguem a NBR 13532 (ABNT —
 * «Elaboração de projetos de edificações — Arquitetura»), que muda de década em
 * década, não de mês em mês. Uma tela de gestão de modelos seria construir
 * administração para algo que ninguém pediu para editar; no dia em que for
 * pedido, o lugar certo é a tela de configurações.
 *
 * Construção e remodelação **não** têm modelo, de propósito: cada obra tem o
 * seu faseamento, e sugerir um seria dar a entender que existe um padrão que
 * não existe. Quem constrói escreve as suas fases, como já fazia.
 */
class PhaseTemplates
{
    /**
     * Arquitetura: subconjunto da NBR 13532, com as etapas que aparecem em
     * praticamente todos os contratos. Ficaram de fora as que raramente são
     * contratadas em separado (programa de necessidades, estudo de
     * viabilidade), para o modelo ser um ponto de partida e não uma lista a
     * podar.
     *
     * Design de interiores: não há norma equivalente — esta sequência é a
     * prática corrente do mercado, e é por isso que se assume como convenção e
     * não como norma.
     *
     * @var array<string, list<array{name: string, description: string}>>
     */
    private const TEMPLATES = [
        'arquitetura' => [
            ['name' => 'Levantamento', 'description' => 'Medição do terreno ou da edificação existente e coleta dos dados de partida.'],
            ['name' => 'Estudo preliminar', 'description' => 'Primeira proposta de partido arquitetônico, para discussão com o cliente.'],
            ['name' => 'Anteprojeto', 'description' => 'Proposta consolidada, com definição de materiais, dimensões e sistema construtivo.'],
            ['name' => 'Projeto legal', 'description' => 'Peças para aprovação nos órgãos competentes.'],
            ['name' => 'Projeto executivo', 'description' => 'Detalhamento completo para execução da obra.'],
        ],
        'design_interiores' => [
            ['name' => 'Levantamento', 'description' => 'Medição dos ambientes e leitura das necessidades de uso.'],
            ['name' => 'Conceito', 'description' => 'Proposta de linguagem, paleta e referências, para alinhar expectativas.'],
            ['name' => 'Layout', 'description' => 'Distribuição dos ambientes, circulação e mobiliário.'],
            ['name' => 'Detalhamento', 'description' => 'Desenhos executivos de marcenaria, forros, iluminação e pontos elétricos.'],
            ['name' => 'Especificação', 'description' => 'Acabamentos, mobiliário e louças, com quantitativos.'],
        ],
    ];

    public static function has(string $type): bool
    {
        return isset(self::TEMPLATES[$type]);
    }

    /** @return list<array{name: string, description: string}> */
    public static function for(string $type): array
    {
        return self::TEMPLATES[$type] ?? [];
    }

    /**
     * O modelo só se oferece a um projeto que ainda não tem fase nenhuma.
     * Depois de o trabalho começar, acrescentar cinco etapas de uma vez é mais
     * atrapalho do que ajuda.
     */
    public static function availableFor(Project $project): bool
    {
        return self::has($project->type) && $project->phases()->count() === 0;
    }
}
