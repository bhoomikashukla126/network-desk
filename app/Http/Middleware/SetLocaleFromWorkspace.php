<?php

namespace App\Http\Middleware;

use App\Support\WorkspaceLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $request->session()->get('central_workspace');

        if (is_array($workspace)) {
            WorkspaceLocale::apply($workspace['language'] ?? 'en');
        }

        return $next($request);
    }
}
