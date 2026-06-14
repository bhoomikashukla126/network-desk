<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceCableType;
use App\Support\CableTypeCatalog;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkspaceCableTypeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $label = trim($validated['label']);
        $key = CableTypeCatalog::makeKey($workspaceId, $label);

        $type = WorkspaceCableType::query()->create([
            'workspace_id' => $workspaceId,
            'key' => $key,
            'label' => $label,
            'color' => $validated['color'] ?? CableTypeCatalog::nextColor($workspaceId),
            'sort_order' => WorkspaceCableType::query()->where('workspace_id', $workspaceId)->count(),
        ]);

        return response()->json([
            'data' => $type,
            'cable_types' => CableTypeCatalog::forWorkspace($workspaceId),
            'cable_type_colors' => CableTypeCatalog::colorsForWorkspace($workspaceId),
            'custom_cable_types' => CableTypeCatalog::customTypesForWorkspace($workspaceId),
        ], 201);
    }

    public function destroy(Request $request, WorkspaceCableType $workspaceCableType): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);

        abort_unless($workspaceCableType->workspace_id === $workspaceId, 404);

        if (\App\Models\CableSegment::query()
            ->where('workspace_id', $workspaceId)
            ->where('cable_type', $workspaceCableType->key)
            ->exists()) {
            throw ValidationException::withMessages([
                'label' => ['This cable type is in use and cannot be removed.'],
            ]);
        }

        $workspaceCableType->delete();

        return response()->json([
            'message' => 'Cable type removed.',
            'cable_types' => CableTypeCatalog::forWorkspace($workspaceId),
            'cable_type_colors' => CableTypeCatalog::colorsForWorkspace($workspaceId),
            'custom_cable_types' => CableTypeCatalog::customTypesForWorkspace($workspaceId),
        ]);
    }
}
