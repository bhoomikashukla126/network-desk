<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Support\WorkspaceSession;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function record(
        Request $request,
        string $action,
        string $subjectType,
        ?int $subjectId = null,
        string $description = '',
        ?array $metadata = null,
    ): ActivityLog {
        $user = $request->session()->get('central_user', []);
        $member = WorkspaceSession::member($request);

        return ActivityLog::query()->create([
            'workspace_id' => WorkspaceSession::id($request),
            'central_user_id' => (string) ($user['sub'] ?? ''),
            'workspace_member_id' => $member['id'] ?? null,
            'actor_name' => $user['name'] ?? $user['email'] ?? 'Unknown',
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
