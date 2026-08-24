<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;

class DashboardController extends Controller
{
    public function index()
    {
        $clientId = auth()->user()->client_id;
        // Só a camada global: a vista cruza-a com o interruptor de cada obra
        // ao percorrer a lista.
        $canViewFinancials = Project::clientFinancialsEnabled();

        $projects = Project::with(['client', 'latestImage'])
            ->where('client_id', $clientId)
            ->latest('updated_at')
            ->get()
            ->map(function ($project) {
                // A capa escolhida à mão manda sobre a última foto: era ao contrário e
                // bastava alguém carregar uma foto para a capa deliberada desaparecer.
                // A última imagem fica como recurso para as obras que não têm capa.
                //
                // Fica em `null` quando não há nem uma nem outra: a imagem padrão não
                // entra aqui, entra no componente <x-project-cover>, o único sítio que
                // decide qual é o URL da imagem padrão em toda a aplicação.
                $project->cover_url = $project->cover_image_path
                    ? route('portal.projects.cover', $project)
                    : ($project->latestImage ? route('portal.projects.photos.show', [$project, $project->latestImage]) : null);

                return $project;
            });

        return view('client.dashboard', compact('projects', 'canViewFinancials'));
    }
}
