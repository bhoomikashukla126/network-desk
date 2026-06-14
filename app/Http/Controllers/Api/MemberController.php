<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\WorkspaceMember;
use App\Services\ActivityLogService;
use App\Services\WorkspaceAccessService;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function __construct(
        protected WorkspaceAccessService $workspaceAccess,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);

        $members = WorkspaceMember::query()
            ->where('workspace_id', $workspaceId)
            ->with('role')
            ->orderBy('name')
            ->get()
            ->map(fn (WorkspaceMember $member) => $this->formatMember($member));

        $roles = Role::query()
            ->where('workspace_id', $workspaceId)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_system']);

        return response()->json([
            'data' => $members,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, WorkspaceMember $member): JsonResponse
    {
        $this->authorizeMember($member);

        $workspaceId = WorkspaceSession::id($request);

        $validated = $request->validate([
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('workspace_id', $workspaceId)),
            ],
        ]);

        $ownerRole = Role::query()
            ->where('workspace_id', $workspaceId)
            ->where('slug', 'owner')
            ->first();

        if ($ownerRole && $member->role_id === $ownerRole->id && (int) $validated['role_id'] !== $ownerRole->id) {
            $ownerCount = WorkspaceMember::query()
                ->where('workspace_id', $workspaceId)
                ->where('role_id', $ownerRole->id)
                ->count();

            abort_if($ownerCount <= 1, 422, 'At least one owner must remain in the workspace.');
        }

        $member->update(['role_id' => $validated['role_id']]);
        $member->load('role.permissions');

        app(ActivityLogService::class)->record(
            $request,
            'member.updated',
            'member',
            $member->id,
            "Updated role for {$member->name} to {$member->role->name}",
            ['member_name' => $member->name, 'role_name' => $member->role->name],
        );

        if ($member->central_user_id === WorkspaceSession::member($request)['central_user_id'] ?? null) {
            $this->workspaceAccess->storeMemberSession($request, $member);
        }

        return response()->json(['data' => $this->formatMember($member)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMember(WorkspaceMember $member): array
    {
        return [
            'id' => $member->id,
            'central_user_id' => $member->central_user_id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => [
                'id' => $member->role->id,
                'name' => $member->role->name,
                'slug' => $member->role->slug,
            ],
            'is_current_user' => $member->central_user_id === (WorkspaceSession::member()['central_user_id'] ?? null),
        ];
    }

    private function authorizeMember(WorkspaceMember $member): void
    {
        abort_unless($member->workspace_id === WorkspaceSession::id(), 404);
    }
}
