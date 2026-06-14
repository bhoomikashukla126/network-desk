<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NetworkPoint;
use App\Services\ActivityLogService;
use App\Services\PointDeviceSyncService;
use App\Support\CableTypeCatalog;
use App\Support\NetworkCatalog;
use App\Support\PointTypes;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NetworkPointController extends Controller
{
    public function __construct(
        protected PointDeviceSyncService $deviceSync,
    ) {}

    public function meta(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);

        return response()->json([
            'types' => NetworkCatalog::pointTypes(),
            'statuses' => NetworkCatalog::pointStatuses(),
            'cable_types' => CableTypeCatalog::forWorkspace($workspaceId),
            'cable_type_colors' => CableTypeCatalog::colorsForWorkspace($workspaceId),
            'custom_cable_types' => CableTypeCatalog::customTypesForWorkspace($workspaceId),
            'cable_statuses' => NetworkCatalog::cableStatuses(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);
        $search = $request->string('search')->value();
        $area = trim($request->string('area')->value());
        $type = trim($request->string('type')->value());
        $status = trim($request->string('status')->value());
        $perPage = min(max((int) $request->input('per_page', 10), 5), 50);

        $query = $this->filteredPointsQuery($workspaceId, $search, $area, $type, $status)
            ->with(['images', 'devices.ports'])
            ->orderBy('name');

        $areas = NetworkPoint::query()
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('area')
            ->where('area', '!=', '')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');

        $payload = [
            'areas' => $areas,
            'filter_stats' => $this->filterStats($workspaceId, $search, $area),
            'permissions' => $this->permissions($request),
        ];

        if ($request->boolean('all')) {
            $items = $query->get();

            return response()->json([
                ...$payload,
                'data' => $items,
            ]);
        }

        $points = $query->paginate($perPage)->withQueryString();

        return response()->json([
            ...$payload,
            'data' => $points->items(),
            'meta' => [
                'current_page' => $points->currentPage(),
                'last_page' => $points->lastPage(),
                'per_page' => $points->perPage(),
                'total' => $points->total(),
                'from' => $points->firstItem(),
                'to' => $points->lastItem(),
            ],
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<NetworkPoint>
     */
    private function filteredPointsQuery(
        string $workspaceId,
        string $search,
        string $area,
        string $type,
        string $status,
    ) {
        return NetworkPoint::query()
            ->where('workspace_id', $workspaceId)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhere('notes', 'like', $like)
                        ->orWhere('area', 'like', $like);
                });
            })
            ->when($area !== '', fn ($query) => $query->where('area', $area))
            ->when($type !== '', fn ($query) => $query->where(function ($inner) use ($type) {
                $inner->where('type', $type)
                    ->orWhereJsonContains('types', $type);
            }))
            ->when($status !== '', fn ($query) => $query->where('status', $status));
    }

    /**
     * @return array{total: int, by_type: array<string, int>, by_status: array<string, int>}
     */
    private function filterStats(string $workspaceId, string $search, string $area): array
    {
        $base = $this->filteredPointsQuery($workspaceId, $search, $area, '', '');

        $total = (clone $base)->count();

        $byType = [];

        foreach (array_keys(NetworkCatalog::pointTypes()) as $typeKey) {
            $byType[$typeKey] = (clone $base)
                ->where(function ($query) use ($typeKey) {
                    $query->where('type', $typeKey)
                        ->orWhereJsonContains('types', $typeKey);
                })
                ->count();
        }

        $byStatus = (clone $base)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'total' => $total,
            'by_type' => $byType,
            'by_status' => $byStatus,
        ];
    }

    public function show(NetworkPoint $networkPoint): JsonResponse
    {
        $this->authorizePoint($networkPoint);

        $networkPoint->load(['images', 'devices.ports', 'cablesFrom.toPoint', 'cablesTo.fromPoint']);

        return response()->json(['data' => $networkPoint]);
    }

    public function store(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);
        $user = $request->session()->get('central_user', []);

        [$validated, $devices] = $this->validated($request);

        $point = NetworkPoint::query()->create([
            ...$validated,
            'workspace_id' => $workspaceId,
            'created_by' => $user['name'] ?? $user['email'] ?? 'Unknown',
        ]);

        $this->deviceSync->sync($point, $devices);

        app(ActivityLogService::class)->record(
            $request,
            'network_point.created',
            'network_point',
            (string) $point->id,
            $point->name,
        );

        return response()->json(['data' => $point->load(['images', 'devices.ports'])], 201);
    }

    public function update(Request $request, NetworkPoint $networkPoint): JsonResponse
    {
        $this->authorizePoint($networkPoint);

        [$validated, $devices] = $this->validated($request);

        $networkPoint->update($validated);
        $this->deviceSync->sync($networkPoint, $devices);

        app(ActivityLogService::class)->record(
            $request,
            'network_point.updated',
            'network_point',
            (string) $networkPoint->id,
            $networkPoint->name,
        );

        return response()->json(['data' => $networkPoint->fresh()->load(['images', 'devices.ports'])]);
    }

    public function destroy(Request $request, NetworkPoint $networkPoint): JsonResponse
    {
        $this->authorizePoint($networkPoint);

        $name = $networkPoint->name;
        $id = (string) $networkPoint->id;

        $networkPoint->delete();

        app(ActivityLogService::class)->record(
            $request,
            'network_point.deleted',
            'network_point',
            $id,
            $name,
        );

        return response()->json(['message' => 'Point deleted.']);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>|null}
     */
    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'types' => ['required_without:type', 'array', 'min:1'],
            'types.*' => [Rule::in(array_keys(NetworkCatalog::pointTypes()))],
            'type' => ['required_without:types', Rule::in(array_keys(NetworkCatalog::pointTypes()))],
            'status' => ['required', Rule::in(array_keys(NetworkCatalog::pointStatuses()))],
            'area' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'port_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'metadata' => ['nullable', 'array'],
            'devices' => ['nullable', 'array'],
            'devices.*.id' => ['nullable', 'integer'],
            'devices.*.label' => ['required_with:devices', 'string', 'max:255'],
            'devices.*.type' => ['required_with:devices', Rule::in(array_keys(NetworkCatalog::pointTypes()))],
            'devices.*.ports' => ['nullable', 'array'],
            'devices.*.ports.*.id' => ['nullable', 'integer'],
            'devices.*.ports.*.label' => ['required_with:devices.*.ports', 'string', 'max:255'],
            'devices.*.ports.*.direction' => ['required_with:devices.*.ports', 'in:input,output'],
        ]);

        $devices = array_key_exists('devices', $validated) ? $validated['devices'] : null;
        unset($validated['devices']);

        return [
            PointTypes::apply($validated),
            $devices,
        ];
    }

    protected function authorizePoint(NetworkPoint $networkPoint): void
    {
        abort_unless(
            $networkPoint->workspace_id === WorkspaceSession::id(),
            404,
        );
    }

    /**
     * @return array<string, bool>
     */
    protected function permissions(Request $request): array
    {
        return [
            'view' => WorkspaceSession::hasPermission('network.view', $request),
            'create' => WorkspaceSession::hasPermission('network.create', $request),
            'edit' => WorkspaceSession::hasPermission('network.edit', $request),
            'delete' => WorkspaceSession::hasPermission('network.delete', $request),
        ];
    }
}
