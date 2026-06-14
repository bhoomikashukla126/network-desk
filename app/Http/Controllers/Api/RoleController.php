<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogService;
use App\Services\WorkspaceAccessService;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function __construct(
        protected WorkspaceAccessService $workspaceAccess,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);

        $roles = Role::query()
            ->where('workspace_id', $workspaceId)
            ->with('permissions')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => $this->formatRole($role));

        return response()->json(['data' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);
        $validated = $this->validated($request, $workspaceId);

        $role = Role::query()->create([
            'workspace_id' => $workspaceId,
            'name' => $validated['name'],
            'slug' => $this->workspaceAccess->makeRoleSlug($workspaceId, $validated['name']),
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        $this->syncPermissions($role, $validated['permissions'] ?? []);
        $role->load('permissions');

        app(ActivityLogService::class)->record(
            $request,
            'role.created',
            'role',
            $role->id,
            "Created role {$role->name}",
            ['name' => $role->name],
        );

        return response()->json(['data' => $this->formatRole($role)], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorizeRole($role);
        $workspaceId = WorkspaceSession::id($request);
        $validated = $this->validated($request, $workspaceId, $role);

        if ($role->is_system) {
            $role->update([
                'description' => $validated['description'] ?? $role->description,
            ]);
        } else {
            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);
        }

        $this->syncPermissions($role, $validated['permissions'] ?? []);
        $role->load('permissions');

        $this->workspaceAccess->refreshMemberSession($request);

        app(ActivityLogService::class)->record(
            $request,
            'role.updated',
            'role',
            $role->id,
            "Updated role {$role->name}",
            ['name' => $role->name],
        );

        return response()->json(['data' => $this->formatRole($role->fresh('permissions'))]);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        $this->authorizeRole($role);

        abort_if($role->is_system, 422, 'System roles cannot be deleted.');
        abort_if($role->members()->exists(), 422, 'Remove members from this role before deleting it.');

        $roleName = $role->name;
        $roleId = $role->id;
        $role->delete();

        app(ActivityLogService::class)->record(
            $request,
            'role.deleted',
            'role',
            $roleId,
            "Deleted role {$roleName}",
            ['name' => $roleName],
        );

        return response()->json(['message' => 'Role deleted.']);
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('label')
            ->get()
            ->map(fn (Permission $permission) => [
                'key' => $permission->key,
                'label' => $permission->label,
                'group' => $permission->group,
            ]);

        return response()->json(['data' => $permissions]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'is_system' => $role->is_system,
            'permissions' => $role->permissionKeys(),
            'members_count' => $role->members()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $workspaceId, ?Role $role = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'key'),
            ],
        ]);

        if (! in_array('network.view', $validated['permissions'], true)) {
            throw ValidationException::withMessages([
                'permissions' => 'Every role must include the View network permission.',
            ]);
        }

        return $validated;
    }

    private function authorizeRole(Role $role): void
    {
        abort_unless($role->workspace_id === WorkspaceSession::id(), 404);
    }

    /**
     * @param  array<int, string>  $permissionKeys
     */
    private function syncPermissions(Role $role, array $permissionKeys): void
    {
        $permissionIds = Permission::query()
            ->whereIn('key', $permissionKeys)
            ->pluck('id');

        $role->permissions()->sync($permissionIds);
    }
}
