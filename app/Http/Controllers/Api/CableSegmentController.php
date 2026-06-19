<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CableCore;
use App\Models\CableCoreEnd;
use App\Models\CableImage;
use App\Models\CableSegment;
use App\Models\NetworkPoint;
use App\Services\ActivityLogService;
use App\Services\CableCoreConnectionCleanupService;
use App\Services\CableCoreSyncService;
use App\Services\CableSplitJoinService;
use App\Support\CableRoute;
use App\Support\CableTypeCatalog;
use App\Support\DeviceConnectionLabel;
use App\Support\FiberCoreColors;
use App\Support\Geo;
use App\Support\MediaStorage;
use App\Support\NetworkCatalog;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CableSegmentController extends Controller
{
    public function __construct(
        protected CableCoreSyncService $coreSync,
        protected CableSplitJoinService $splitJoin,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);

        app(CableCoreConnectionCleanupService::class)->repairOrphanedConnections($workspaceId);

        $cables = CableSegment::query()
            ->with([
                'fromPoint:id,name,type,types,latitude,longitude',
                'toPoint:id,name,type,types,latitude,longitude',
                'images',
                'cores.ends.networkPoint:id,name',
                'cores.ends.networkPointPort:id,label,direction,network_point_id,network_point_device_id',
                'cores.ends.networkPointPort.device:id,label,type',
                'cores.ends.networkPointDevice:id,label,type',
                'cores.ends.connectedCoreEnd.core.cable:id,name',
            ])
            ->where('workspace_id', $workspaceId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('cable_type'), fn ($q) => $q->where('cable_type', $request->string('cable_type')))
            ->orderBy('id')
            ->get()
            ->map(fn (CableSegment $cable) => $this->presentCable($cable));

        return response()->json([
            'data' => $cables,
            'permissions' => [
                'view' => WorkspaceSession::hasPermission('network.view', $request),
                'create' => WorkspaceSession::hasPermission('network.create', $request),
                'edit' => WorkspaceSession::hasPermission('network.edit', $request),
                'delete' => WorkspaceSession::hasPermission('network.delete', $request),
            ],
        ]);
    }

    public function show(CableSegment $cableSegment): JsonResponse
    {
        $this->authorizeCable($cableSegment);

        return response()->json([
            'data' => $this->presentCable($this->loadCableRelations($cableSegment)),
        ]);
    }

    public function coreConnectionOptions(CableSegment $cableSegment): JsonResponse
    {
        $this->authorizeCable($cableSegment);

        return response()->json([
            'data' => $this->coreSync->connectionOptions($cableSegment->workspace_id, $cableSegment),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);
        $user = $request->session()->get('central_user', []);

        [$validated, $corePayload] = $this->validated($request);
        $this->assertRoutePointsInWorkspace($workspaceId, $validated['route']);

        $cable = CableSegment::query()->create([
            ...$validated,
            'workspace_id' => $workspaceId,
            'created_by' => $user['name'] ?? $user['email'] ?? 'Unknown',
        ]);

        $this->coreSync->sync($cable, $corePayload);

        $cable = $this->loadCableRelations($cable->fresh());

        if ($cable->length_m === null) {
            $mapDistance = Geo::cableMapDistanceM($cable);

            if ($mapDistance !== null) {
                $cable->update(['length_m' => round($mapDistance, 2)]);
                $cable = $this->loadCableRelations($cable->fresh());
            }
        }

        app(ActivityLogService::class)->record(
            $request,
            'cable.created',
            'cable_segment',
            (string) $cable->id,
            $cable->name ?? "Cable #{$cable->id}",
        );

        return response()->json(['data' => $this->presentCable($cable)], 201);
    }

    public function update(Request $request, CableSegment $cableSegment): JsonResponse
    {
        $this->authorizeCable($cableSegment);

        [$validated, $corePayload] = $this->validated($request, $cableSegment);
        $this->assertRoutePointsInWorkspace(
            $cableSegment->workspace_id,
            $validated['route'],
        );

        $cableSegment->update($validated);
        $this->coreSync->sync($cableSegment, $corePayload);

        app(ActivityLogService::class)->record(
            $request,
            'cable.updated',
            'cable_segment',
            (string) $cableSegment->id,
            $cableSegment->name ?? "Cable #{$cableSegment->id}",
        );

        return response()->json(['data' => $this->presentCable($this->loadCableRelations($cableSegment->fresh()))]);
    }

    public function destroy(Request $request, CableSegment $cableSegment): JsonResponse
    {
        $this->authorizeCable($cableSegment);

        $id = (string) $cableSegment->id;
        $name = $cableSegment->name ?? "Cable #{$id}";

        $cableSegment->load('images');

        $cableSegment->images->each(function (CableImage $image): void {
            MediaStorage::deleteReference($image->url);
        });

        $cableSegment->delete();

        app(ActivityLogService::class)->record(
            $request,
            'cable.deleted',
            'cable_segment',
            $id,
            $name,
        );

        return response()->json(['message' => 'Cable segment deleted.']);
    }

    public function splitOptions(CableSegment $cableSegment): JsonResponse
    {
        $this->authorizeCable($cableSegment);

        return response()->json([
            'data' => $this->splitJoin->splitOptions($cableSegment),
        ]);
    }

    public function joinCandidates(CableSegment $cableSegment): JsonResponse
    {
        $this->authorizeCable($cableSegment);

        return response()->json([
            'data' => $this->splitJoin->joinCandidates($cableSegment->workspace_id, $cableSegment),
        ]);
    }

    public function split(Request $request, CableSegment $cableSegment): JsonResponse
    {
        $this->authorizeCable($cableSegment);

        $validated = $request->validate([
            'split_point_id' => ['required', 'integer'],
        ]);

        $this->assertRoutePointsInWorkspace(
            $cableSegment->workspace_id,
            CableRoute::normalize($cableSegment->route, $cableSegment),
        );

        $result = $this->splitJoin->split($cableSegment, (int) $validated['split_point_id']);

        app(ActivityLogService::class)->record(
            $request,
            'cable.split',
            'cable_segment',
            (string) $cableSegment->id,
            $cableSegment->name ?? "Cable #{$cableSegment->id}",
        );

        return response()->json([
            'data' => [
                'first' => $this->presentCable($this->loadCableRelations($result['first'])),
                'second' => $this->presentCable($this->loadCableRelations($result['second'])),
            ],
        ]);
    }

    public function join(Request $request, CableSegment $cableSegment): JsonResponse
    {
        $this->authorizeCable($cableSegment);

        $validated = $request->validate([
            'other_cable_id' => ['required', 'integer'],
        ]);

        $other = CableSegment::query()->find($validated['other_cable_id']);

        abort_unless($other && $other->workspace_id === $cableSegment->workspace_id, 422, 'The selected cable was not found.');

        $merged = $this->splitJoin->join($cableSegment, $other);

        app(ActivityLogService::class)->record(
            $request,
            'cable.joined',
            'cable_segment',
            (string) $merged->id,
            $merged->name ?? "Cable #{$merged->id}",
        );

        return response()->json([
            'data' => $this->presentCable($this->loadCableRelations($merged)),
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function validated(Request $request, ?CableSegment $existing = null): array
    {
        $workspaceId = $existing?->workspace_id ?? WorkspaceSession::id($request);

        $validated = $request->validate([
            'route' => ['required', 'array', 'min:2'],
            'route.*.type' => ['required', 'in:point,bend'],
            'route.*.point_id' => ['required_if:route.*.type,point', 'integer'],
            'route.*.lat' => ['required_if:route.*.type,bend', 'numeric', 'between:-90,90'],
            'route.*.lng' => ['required_if:route.*.type,bend', 'numeric', 'between:-180,180'],
            'name' => ['nullable', 'string', 'max:255'],
            'cable_type' => ['required', 'string', 'max:32'],
            'status' => ['required', Rule::in(array_keys(NetworkCatalog::cableStatuses()))],
            'length_m' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'core_count' => ['nullable', 'integer', 'min:0', 'max:288'],
            'cores' => ['nullable', 'array'],
            'cores.*.core_number' => ['required_with:cores', 'integer', 'min:1', 'max:288'],
            'cores.*.color' => ['nullable', 'string', 'max:16'],
            'cores.*.label' => ['nullable', 'string', 'max:255'],
            'cores.*.status' => ['nullable', 'string', 'max:32'],
            'cores.*.ends' => ['nullable', 'array'],
            'cores.*.ends.start' => ['nullable', 'array'],
            'cores.*.ends.end' => ['nullable', 'array'],
            'cores.*.ends.start.connection_type' => ['nullable', 'in:device,core_end'],
            'cores.*.ends.end.connection_type' => ['nullable', 'in:device,core_end'],
            'cores.*.ends.start.network_point_port_id' => ['nullable', 'integer'],
            'cores.*.ends.end.network_point_port_id' => ['nullable', 'integer'],
            'cores.*.ends.start.device_port_label' => ['nullable', 'string', 'max:255'],
            'cores.*.ends.end.device_port_label' => ['nullable', 'string', 'max:255'],
            'cores.*.ends.start.device_type' => ['nullable', 'string', 'max:32'],
            'cores.*.ends.end.device_type' => ['nullable', 'string', 'max:32'],
            'cores.*.ends.start.device_label' => ['nullable', 'string', 'max:255'],
            'cores.*.ends.end.device_label' => ['nullable', 'string', 'max:255'],
            'cores.*.ends.start.device_port_direction' => ['nullable', 'in:input,output'],
            'cores.*.ends.end.device_port_direction' => ['nullable', 'in:input,output'],
            'cores.*.ends.start.connected_core_end_id' => ['nullable', 'integer'],
            'cores.*.ends.end.connected_core_end_id' => ['nullable', 'integer'],
        ]);

        abort_unless(
            CableTypeCatalog::isAllowed($workspaceId, $validated['cable_type']),
            422,
            'The selected cable type is not allowed.',
        );

        $corePayload = [
            'core_count' => $validated['core_count'] ?? null,
            'cores' => $validated['cores'] ?? [],
        ];

        unset($validated['core_count'], $validated['cores']);

        return [
            CableRoute::apply($validated),
            $corePayload,
        ];
    }

    protected function authorizeCable(CableSegment $cableSegment): void
    {
        abort_unless(
            $cableSegment->workspace_id === WorkspaceSession::id(),
            404,
        );
    }

    /**
     * @param  list<array{type: string, point_id?: int, lat?: float, lng?: float}>  $route
     */
    protected function assertRoutePointsInWorkspace(string $workspaceId, array $route): void
    {
        $pointIds = CableRoute::pointIds($route);

        abort_unless(count($pointIds) >= 2, 422, 'A cable route must include at least two points.');

        $uniqueIds = array_values(array_unique($pointIds));

        $count = NetworkPoint::query()
            ->where('workspace_id', $workspaceId)
            ->whereIn('id', $uniqueIds)
            ->count();

        abort_unless($count === count($uniqueIds), 422, 'All route points must belong to this workspace.');
    }

    protected function loadCableRelations(CableSegment $cable): CableSegment
    {
        return $cable->load([
            'fromPoint.devices.ports',
            'toPoint.devices.ports',
            'images',
            'cores.ends.networkPoint:id,name',
            'cores.ends.networkPointPort:id,label,direction,network_point_id,network_point_device_id',
            'cores.ends.networkPointPort.device:id,label,type',
            'cores.ends.networkPointDevice:id,label,type',
            'cores.ends.connectedCoreEnd.core.cable:id,name',
        ]);
    }

    protected function presentCable(CableSegment $cable): array
    {
        $data = $cable->toArray();
        $data['map_distance_m'] = Geo::cableMapDistanceM($cable);
        $data['fiber_core_colors'] = FiberCoreColors::palette();
        $data['fiber_core_color_options'] = FiberCoreColors::options();
        $data['cores'] = $cable->relationLoaded('cores')
            ? $cable->cores->map(fn (CableCore $core) => $this->presentCore($core))->values()->all()
            : [];

        return $data;
    }

    protected function presentCore(CableCore $core): array
    {
        $ends = $core->relationLoaded('ends')
            ? $core->ends->keyBy('side')
            : collect();

        return [
            'id' => $core->id,
            'core_number' => $core->core_number,
            'color' => $core->color,
            'label' => $core->label,
            'status' => $core->status,
            'ends' => [
                'start' => $this->presentCoreEnd($ends->get('start')),
                'end' => $this->presentCoreEnd($ends->get('end')),
            ],
        ];
    }

    protected function presentCoreEnd(?CableCoreEnd $end): ?array
    {
        if (! $end) {
            return null;
        }

        return [
            'id' => $end->id,
            'side' => $end->side,
            'network_point_id' => $end->network_point_id,
            'network_point_name' => $end->networkPoint?->name,
            'network_point_port_id' => $end->network_point_port_id,
            'network_point_device_id' => $end->network_point_device_id,
            'device_type' => $end->device_type,
            'device_label' => $end->device_label,
            'device_type_label' => $end->device_type
                ? (NetworkCatalog::pointTypes()[$end->device_type] ?? $end->device_type)
                : null,
            'connection_type' => $end->connection_type,
            'device_port_label' => $end->device_port_label,
            'device_port_direction' => $end->device_port_direction,
            'connected_core_end_id' => $end->connected_core_end_id,
            'connection_label' => $this->connectionLabel($end),
        ];
    }

    protected function connectionLabel(CableCoreEnd $end): ?string
    {
        if ($end->connection_type === 'device') {
            return DeviceConnectionLabel::forCoreEnd($end, NetworkCatalog::pointTypes());
        }

        if ($end->connection_type === 'core_end' && $end->connectedCoreEnd) {
            $target = $end->connectedCoreEnd;
            $cableName = $target->core?->cable?->name ?? ('Cable #'.$target->core?->cable_segment_id);
            $coreNumber = $target->core?->core_number;

            return "{$cableName} · Core {$coreNumber} · {$target->side} end";
        }

        return null;
    }
}
