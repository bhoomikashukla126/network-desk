<?php

namespace App\Http\Middleware;

use App\Services\WorkspaceQuotaService;
use App\Support\WorkspaceSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackWorkspaceQuota
{
    public function __construct(
        protected WorkspaceQuotaService $workspaceQuota,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $workspace = WorkspaceSession::get($request) ?? [];
        $workspaceId = WorkspaceSession::id($request);

        if ($workspaceId === 'default' || $workspace === []) {
            return $next($request);
        }

        $reason = $this->workspaceQuota->blockReasonForRequest($workspace, $request);

        if ($reason !== null) {
            return response()->json(['message' => $reason], 429);
        }

        $response = $next($request);

        if ($response->getStatusCode() < 400) {
            try {
                $this->workspaceQuota->recordRequest($request, $workspaceId, $workspace);
            } catch (\Throwable) {
                // Quota tracking must never break API responses.
            }
        }

        return $response;
    }
}
