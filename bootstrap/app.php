<?php

use App\Http\Middleware\Authenticate36Blocks;
use App\Http\Middleware\ResolveCurrentWorkspace;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            '36blocks.auth' => Authenticate36Blocks::class,
            'workspace.resolve' => ResolveCurrentWorkspace::class,
        ]);

        // Route model binding (SubstituteBindings) must run after the current workspace
        // is resolved, since workspace-scoped models rely on it being bound in the
        // container to filter the query used to resolve {skill}/{tree}/etc. route params.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolveCurrentWorkspace::class,
        );
        $middleware->prependToPriorityList(
            before: ResolveCurrentWorkspace::class,
            prepend: Authenticate36Blocks::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
