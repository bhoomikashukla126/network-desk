<?php

namespace App\Http\Middleware;

use App\Services\CentralAuthService;
use App\Services\WorkspaceAccessService;
use App\Support\LocalDevAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralAuthenticated
{
    public function __construct(
        protected CentralAuthService $centralAuth,
        protected WorkspaceAccessService $workspaceAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('central_user')) {
            return $next($request);
        }

        if (LocalDevAuth::enabled()) {
            LocalDevAuth::bootstrap($request, $this->workspaceAccess);

            return $next($request);
        }

        if (blank(config('central.client_id'))) {
            abort(500, 'SSO is not configured. Set CLIENT_ID and CLIENT_SECRET, or enable LOCAL_DEV_AUTH=true for local development.');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->route('welcome');
    }
}
