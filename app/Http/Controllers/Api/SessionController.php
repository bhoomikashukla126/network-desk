<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WorkspaceQuotaService;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->session()->get('central_user', []);
        $member = WorkspaceSession::member($request);

        return response()->json([
            'user' => [
                'name' => $user['name'] ?? null,
                'email' => $user['email'] ?? null,
                'sub' => $user['sub'] ?? null,
            ],
            'workspace' => WorkspaceSession::get($request),
            'locale' => app()->getLocale(),
            'member' => $member,
            'role' => $member['role'] ?? null,
            'permissions' => WorkspaceSession::permissions($request),
            'can_edit' => WorkspaceSession::canEdit($request),
            'can_manage_roles' => WorkspaceSession::hasPermission('roles.manage', $request),
            'can_manage_members' => WorkspaceSession::hasPermission('members.manage', $request),
            'can_view_activity' => WorkspaceSession::hasPermission('activity.view', $request),
            'quotas' => WorkspaceSession::quotas($request),
            'central_url' => config('central.url'),
        ]);
    }

    public function refreshQuotas(Request $request, WorkspaceQuotaService $quotaService): JsonResponse
    {
        abort_unless(WorkspaceSession::isOwner($request), 403);

        return response()->json([
            'quotas' => $quotaService->refreshUsage($request),
        ]);
    }
}
