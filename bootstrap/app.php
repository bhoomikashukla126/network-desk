<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'central.auth' => \App\Http\Middleware\EnsureCentralAuthenticated::class,
            'workspace.member' => \App\Http\Middleware\SyncWorkspaceMember::class,
            'workspace.locale' => \App\Http\Middleware\SetLocaleFromWorkspace::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'workspace.quota' => \App\Http\Middleware\TrackWorkspaceQuota::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
