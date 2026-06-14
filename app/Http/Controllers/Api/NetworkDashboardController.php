<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CableSegment;
use App\Models\NetworkPoint;
use App\Models\PointImage;
use App\Support\CableTypeCatalog;
use App\Support\MediaStorage;
use App\Support\NetworkCatalog;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkDashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $workspaceId = WorkspaceSession::id($request);

        $typeFilter = $request->string('type')->value();
        $statusFilter = $request->string('status')->value();
        $areaFilter = $request->string('area')->value();
        $cableStatusFilter = $request->string('cable_status')->value();
        $cableTypeFilter = $request->string('cable_type')->value();
        $showCables = $request->string('show_cables', '1')->value() !== '0';

        $pointsQuery = NetworkPoint::query()
            ->with('images')
            ->where('workspace_id', $workspaceId);

        if ($typeFilter !== '') {
            $pointsQuery->where(function ($query) use ($typeFilter) {
                $query->where('type', $typeFilter)
                    ->orWhereJsonContains('types', $typeFilter);
            });
        }

        if ($statusFilter !== '') {
            $pointsQuery->where('status', $statusFilter);
        }

        if ($areaFilter !== '') {
            $pointsQuery->where('area', $areaFilter);
        }

        $points = $pointsQuery->get();
        $pointIds = $points->pluck('id');

        $cables = collect();

        if ($showCables) {
            $cablesQuery = CableSegment::query()
                ->with([
                    'fromPoint:id,name,type,types,latitude,longitude',
                    'toPoint:id,name,type,types,latitude,longitude',
                    'cores.ends',
                ])
                ->where('workspace_id', $workspaceId)
                ->whereIn('from_point_id', $pointIds)
                ->whereIn('to_point_id', $pointIds);

            if ($cableStatusFilter !== '') {
                $cablesQuery->where('status', $cableStatusFilter);
            }

            if ($cableTypeFilter !== '') {
                $cablesQuery->where('cable_type', $cableTypeFilter);
            }

            $cables = $cablesQuery->get();
        }

        $typeCounts = [];

        foreach (array_keys(NetworkCatalog::pointTypes()) as $typeKey) {
            $typeCounts[$typeKey] = NetworkPoint::query()
                ->where('workspace_id', $workspaceId)
                ->where(function ($query) use ($typeKey) {
                    $query->where('type', $typeKey)
                        ->orWhereJsonContains('types', $typeKey);
                })
                ->count();
        }

        $statusCounts = NetworkPoint::query()
            ->where('workspace_id', $workspaceId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $cableTypeCounts = [];

        foreach (array_keys(CableTypeCatalog::forWorkspace($workspaceId)) as $cableTypeKey) {
            $cableTypeCounts[$cableTypeKey] = CableSegment::query()
                ->where('workspace_id', $workspaceId)
                ->where('cable_type', $cableTypeKey)
                ->count();
        }

        $cableStatusCounts = CableSegment::query()
            ->where('workspace_id', $workspaceId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $areas = NetworkPoint::query()
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('area')
            ->where('area', '!=', '')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');

        return response()->json([
            'points' => $points,
            'cables' => $cables,
            'summary' => [
                'total_points' => NetworkPoint::query()->where('workspace_id', $workspaceId)->count(),
                'total_cables' => CableSegment::query()->where('workspace_id', $workspaceId)->count(),
                'total_images' => PointImage::query()
                    ->whereIn('network_point_id', NetworkPoint::query()->where('workspace_id', $workspaceId)->select('id'))
                    ->count(),
                'filtered_points' => $points->count(),
                'filtered_cables' => $cables->count(),
                'by_type' => $typeCounts,
                'by_status' => $statusCounts,
                'by_cable_type' => $cableTypeCounts,
                'by_cable_status' => $cableStatusCounts,
            ],
            'filters' => [
                'types' => NetworkCatalog::pointTypes(),
                'statuses' => NetworkCatalog::pointStatuses(),
                'cable_types' => CableTypeCatalog::forWorkspace($workspaceId),
                'cable_type_colors' => CableTypeCatalog::colorsForWorkspace($workspaceId),
                'cable_statuses' => NetworkCatalog::cableStatuses(),
                'areas' => $areas,
            ],
        ]);
    }
}
