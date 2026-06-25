<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);
        $user = $request->session()->get('central_user', []);
        $centralUserId = $user['sub'] ?? null;

        abort_unless($workspaceId && $centralUserId, 403);

        $record = UserPreference::query()
            ->where('workspace_id', $workspaceId)
            ->where('central_user_id', $centralUserId)
            ->first();

        return response()->json([
            'preferences' => $record?->preferences ?? [],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);
        $user = $request->session()->get('central_user', []);
        $centralUserId = $user['sub'] ?? null;

        abort_unless($workspaceId && $centralUserId, 403);

        $validated = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.keyboard_shortcuts' => ['sometimes', 'array'],
        ]);

        $record = UserPreference::query()->updateOrCreate(
            [
                'workspace_id' => $workspaceId,
                'central_user_id' => $centralUserId,
            ],
            [
                'preferences' => $validated['preferences'],
            ],
        );

        return response()->json([
            'preferences' => $record->preferences ?? [],
        ]);
    }
}
