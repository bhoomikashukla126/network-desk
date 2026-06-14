<?php

namespace App\Http\Middleware;

use App\Services\WorkspaceAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncWorkspaceMember
{
    public function __construct(
        protected WorkspaceAccessService $workspaceAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('central_user')) {
            if (! $request->session()->has('workspace_member')) {
                $this->workspaceAccess->syncMember($request);
            } else {
                $this->workspaceAccess->refreshMemberSession($request);
            }
        }

        return $next($request);
    }
}
