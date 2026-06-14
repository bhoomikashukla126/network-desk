<?php

namespace App\Services;

use App\Enums\PermissionKey;
use App\Models\Permission;
use App\Models\Role;
use App\Models\WorkspaceMember;
use App\Support\WorkspaceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceAccessService
{
    public function ensurePermissionCatalog(): void
    {
        foreach (PermissionKey::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['key' => $permission->value],
                [
                    'label' => $permission->label(),
                    'group' => $permission->group(),
                ],
            );
        }
    }

    public function ensureWorkspaceRoles(string $workspaceId): void
    {
        $this->ensurePermissionCatalog();

        $owner = Role::query()->firstOrCreate(
            ['workspace_id' => $workspaceId, 'slug' => 'owner'],
            [
                'name' => 'Owner',
                'description' => 'Full access to network mapping and workspace access control.',
                'is_system' => true,
            ],
        );

        $guest = Role::query()->firstOrCreate(
            ['workspace_id' => $workspaceId, 'slug' => 'guest'],
            [
                'name' => 'Guest',
                'description' => 'Default role for first-time users. View-only access unless changed by the owner.',
                'is_system' => true,
            ],
        );

        $allPermissionIds = Permission::query()->pluck('id');
        $owner->permissions()->sync($allPermissionIds);

        $guestPermissionIds = Permission::query()
            ->where('key', PermissionKey::NetworkView->value)
            ->pluck('id');

        $guest->permissions()->sync($guestPermissionIds);
    }

    public function syncMember(Request $request): WorkspaceMember
    {
        $workspaceId = WorkspaceSession::id($request);
        $user = $request->session()->get('central_user', []);
        $workspace = WorkspaceSession::get($request) ?? [];
        $centralUserId = (string) ($user['sub'] ?? '');

        abort_if($centralUserId === '', 403, 'Authenticated user is missing a subject identifier.');

        $this->ensureWorkspaceRoles($workspaceId);

        $member = WorkspaceMember::query()->firstOrNew([
            'workspace_id' => $workspaceId,
            'central_user_id' => $centralUserId,
        ]);

        $isNew = ! $member->exists;
        $member->name = (string) ($user['name'] ?? 'User');
        $member->email = $user['email'] ?? null;

        $ownerRole = $this->ownerRole($workspaceId);
        $guestRole = $this->guestRole($workspaceId);

        if ($isNew) {
            $member->role_id = $this->isCentralOwner($workspace) ? $ownerRole->id : $guestRole->id;
        } elseif ($this->isCentralOwner($workspace) && $member->role_id !== $ownerRole->id) {
            $member->role_id = $ownerRole->id;
        }

        $member->save();
        $member->load('role.permissions');

        $this->storeMemberSession($request, $member);

        return $member;
    }

    public function refreshMemberSession(Request $request): void
    {
        $memberId = $request->session()->get('workspace_member.id');

        if (! $memberId) {
            $this->syncMember($request);

            return;
        }

        $member = WorkspaceMember::query()
            ->with('role.permissions')
            ->where('workspace_id', WorkspaceSession::id($request))
            ->find($memberId);

        if (! $member) {
            $this->syncMember($request);

            return;
        }

        $this->storeMemberSession($request, $member);
    }

    public function storeMemberSession(Request $request, WorkspaceMember $member): void
    {
        $member->loadMissing('role.permissions');

        $request->session()->put('workspace_member', [
            'id' => $member->id,
            'central_user_id' => $member->central_user_id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => [
                'id' => $member->role->id,
                'name' => $member->role->name,
                'slug' => $member->role->slug,
                'is_system' => $member->role->is_system,
            ],
            'permissions' => $member->role->permissionKeys(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function isCentralOwner(array $workspace): bool
    {
        return ($workspace['is_owner'] ?? false) === true
            || ($workspace['role'] ?? '') === 'owner';
    }

    private function ownerRole(string $workspaceId): Role
    {
        return Role::query()
            ->where('workspace_id', $workspaceId)
            ->where('slug', 'owner')
            ->firstOrFail();
    }

    private function guestRole(string $workspaceId): Role
    {
        return Role::query()
            ->where('workspace_id', $workspaceId)
            ->where('slug', 'guest')
            ->firstOrFail();
    }

    public function makeRoleSlug(string $workspaceId, string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $counter = 1;

        while (Role::query()->where('workspace_id', $workspaceId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
