<?php

use App\Console\Commands\MigratePublicProjectMedia;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\ExplainTruncatedUpload;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        MigratePublicProjectMedia::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            EnsureUserIsActive::class,
            SecurityHeaders::class,
        ]);

        // LIM-02: um upload acima do `post_max_size` chega vazio e sem o token
        // CSRF; sem isto a resposta seria 419. Toma o lugar do middleware de
        // CSRF porque é aí que a resposta errada nasce, e porque é o primeiro
        // ponto do pipeline com sessão disponível para devolver o erro.
        $middleware->replaceInGroup('web', PreventRequestForgery::class, ExplainTruncatedUpload::class);

        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
