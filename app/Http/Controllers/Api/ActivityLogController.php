<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\WorkspaceMember;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const SUBJECT_TYPES = ['network_point', 'cable_segment', 'role', 'member'];

    public function index(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);
        $canViewAll = WorkspaceSession::hasPermission('activity.view', $request);
        $currentUserId = $this->currentCentralUserId($request);

        $validated = $request->validate([
            'central_user_id' => ['nullable', 'string'],
            'action' => ['nullable', 'string'],
            'subject_type' => ['nullable', 'string', 'in:'.implode(',', self::SUBJECT_TYPES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $perPage = min(max((int) ($validated['per_page'] ?? 25), 5), 100);

        $query = ActivityLog::query()
            ->where('workspace_id', $workspaceId)
            ->orderByDesc('created_at');

        if ($canViewAll) {
            if (! empty($validated['central_user_id'])) {
                $query->where('central_user_id', $validated['central_user_id']);
            }
        } else {
            $query->where('central_user_id', $currentUserId);
        }

        if (! empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        if (! empty($validated['subject_type'])) {
            $query->where('subject_type', $validated['subject_type']);
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $logs = $query->paginate($perPage);

        $actors = $canViewAll
            ? WorkspaceMember::query()
                ->where('workspace_id', $workspaceId)
                ->orderBy('name')
                ->get(['central_user_id', 'name'])
                ->map(fn (WorkspaceMember $member) => [
                    'central_user_id' => $member->central_user_id,
                    'name' => $member->name,
                ])
            : collect();

        return response()->json([
            'data' => collect($logs->items())->map(fn (ActivityLog $log) => $this->formatLog($log)),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
            'scope' => $canViewAll ? 'all' : 'own',
            'can_view_all' => $canViewAll,
            'actors' => $actors,
            'action_options' => $this->actionOptions(),
        ]);
    }

    private function currentCentralUserId(Request $request): string
    {
        $user = $request->session()->get('central_user', []);

        return (string) ($user['sub'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLog(ActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'central_user_id' => $log->central_user_id,
            'actor_name' => $log->actor_name,
            'action' => $log->action,
            'action_label' => $this->actionLabel($log->action),
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'description' => $log->description,
            'metadata' => $log->metadata ?? [],
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'network_point.created' => 'Network point created',
            'network_point.updated' => 'Network point updated',
            'network_point.deleted' => 'Network point deleted',
            'cable.created' => 'Cable segment created',
            'cable.updated' => 'Cable segment updated',
            'cable.deleted' => 'Cable segment deleted',
            'cable.split' => 'Cable segment split',
            'cable.joined' => 'Cable segments joined',
            'role.created' => 'Role created',
            'role.updated' => 'Role updated',
            'role.deleted' => 'Role deleted',
            'member.updated' => 'Member role updated',
            default => ucfirst(str_replace('.', ' ', $action)),
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function actionOptions(): array
    {
        return [
            ['value' => 'network_point.created', 'label' => 'Network point created'],
            ['value' => 'network_point.updated', 'label' => 'Network point updated'],
            ['value' => 'network_point.deleted', 'label' => 'Network point deleted'],
            ['value' => 'cable.created', 'label' => 'Cable segment created'],
            ['value' => 'cable.updated', 'label' => 'Cable segment updated'],
            ['value' => 'cable.deleted', 'label' => 'Cable segment deleted'],
            ['value' => 'cable.split', 'label' => 'Cable segment split'],
            ['value' => 'cable.joined', 'label' => 'Cable segments joined'],
            ['value' => 'role.created', 'label' => 'Role created'],
            ['value' => 'role.updated', 'label' => 'Role updated'],
            ['value' => 'role.deleted', 'label' => 'Role deleted'],
            ['value' => 'member.updated', 'label' => 'Member role updated'],
        ];
    }
}
