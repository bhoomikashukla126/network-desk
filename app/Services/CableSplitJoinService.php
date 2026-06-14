<?php

namespace App\Services;

use App\Models\CableCore;
use App\Models\CableCoreEnd;
use App\Models\CableSegment;
use App\Models\NetworkPoint;
use App\Support\CableRoute;
use App\Support\Geo;
use Illuminate\Support\Facades\DB;

class CableSplitJoinService
{
    /**
     * @return array{first: CableSegment, second: CableSegment}
     */
    public function split(CableSegment $cable, int $splitPointId): array
    {
        $route = CableRoute::normalize($cable->route, $cable);
        [$routeA, $routeB] = CableRoute::splitAtPoint($route, $splitPointId);
        $originalEndPointId = CableRoute::pointIds($route)[array_key_last(CableRoute::pointIds($route))];

        return DB::transaction(function () use ($cable, $routeA, $routeB, $splitPointId, $originalEndPointId) {
            $cable->load('cores.ends');

            $originalLength = $cable->length_m;
            $originalMapDistance = Geo::cableMapDistanceM($cable);

            $appliedA = CableRoute::apply([
                'route' => $routeA,
                'name' => $cable->name,
                'cable_type' => $cable->cable_type,
                'status' => $cable->status,
                'length_m' => $cable->length_m,
                'notes' => $cable->notes,
            ]);

            $cable->update([
                'route' => $appliedA['route'],
                'from_point_id' => $appliedA['from_point_id'],
                'to_point_id' => $appliedA['to_point_id'],
                'path' => $appliedA['path'],
                'name' => $this->segmentName($cable->name, 1),
            ]);

            $cableB = CableSegment::query()->create([
                'workspace_id' => $cable->workspace_id,
                'route' => $routeB,
                'from_point_id' => CableRoute::pointIds($routeB)[0],
                'to_point_id' => CableRoute::pointIds($routeB)[array_key_last(CableRoute::pointIds($routeB))],
                'path' => CableRoute::legacyPath($routeB),
                'name' => $this->segmentName($cable->name, 2),
                'cable_type' => $cable->cable_type,
                'status' => $cable->status,
                'core_count' => $cable->core_count,
                'length_m' => null,
                'notes' => $cable->notes,
                'created_by' => $cable->created_by,
            ]);

            $this->splitLengths($cable, $cableB, $originalLength, $originalMapDistance);
            $this->splitCores($cable, $cableB, $splitPointId, $originalEndPointId);

            return [
                'first' => $cable->fresh(),
                'second' => $cableB->fresh(),
            ];
        });
    }

    public function join(CableSegment $primary, CableSegment $secondary): CableSegment
    {
        abort_if((int) $primary->id === (int) $secondary->id, 422, 'Select a different cable to join.');
        abort_if($primary->workspace_id !== $secondary->workspace_id, 422, 'Cables must belong to the same workspace.');

        $oriented = CableRoute::orientForJoin(
            CableRoute::normalize($primary->route, $primary),
            CableRoute::normalize($secondary->route, $secondary),
        );

        $mergedRoute = $oriented['route'];
        $junctionPointId = $oriented['junction_point_id'];
        $mergedEndPointId = CableRoute::pointIds($mergedRoute)[array_key_last(CableRoute::pointIds($mergedRoute))];
        $secondaryEndIds = [];

        return DB::transaction(function () use ($primary, $secondary, $mergedRoute, $junctionPointId, $mergedEndPointId, &$secondaryEndIds) {
            $primary->load('cores.ends');
            $secondary->load('cores.ends');

            $secondaryEndIds = $secondary->cores
                ->flatMap(fn (CableCore $core) => $core->ends->pluck('id'))
                ->all();

            $this->mergeCores($primary, $secondary, $junctionPointId, $mergedEndPointId);

            $applied = CableRoute::apply([
                'route' => $mergedRoute,
                'name' => $primary->name ?: $secondary->name,
                'cable_type' => $primary->cable_type,
                'status' => $primary->status,
                'length_m' => $this->combinedLength($primary, $secondary),
                'notes' => trim(implode("\n", array_filter([$primary->notes, $secondary->notes]))),
            ]);

            $primary->update([
                'route' => $applied['route'],
                'from_point_id' => $applied['from_point_id'],
                'to_point_id' => $applied['to_point_id'],
                'path' => $applied['path'],
                'name' => $applied['name'],
                'length_m' => $applied['length_m'],
                'notes' => $applied['notes'] ?: null,
                'core_count' => max((int) $primary->core_count, (int) $secondary->core_count) ?: null,
            ]);

            $secondary->delete();

            if ($secondaryEndIds !== []) {
                CableCoreEnd::query()
                    ->whereIn('connected_core_end_id', $secondaryEndIds)
                    ->update(['connected_core_end_id' => null, 'connection_type' => null]);
            }

            return $primary->fresh();
        });
    }

    /**
     * @return list<array{id: int, name: string, cable_type: string, junction_point_id: int, junction_point_name: string|null, label: string}>
     */
    public function joinCandidates(string $workspaceId, CableSegment $cable): array
    {
        $route = CableRoute::normalize($cable->route, $cable);
        $pointIds = CableRoute::pointIds($route);
        $endpointIds = [$pointIds[0], $pointIds[array_key_last($pointIds)]];

        return CableSegment::query()
            ->with(['fromPoint:id,name', 'toPoint:id,name'])
            ->where('workspace_id', $workspaceId)
            ->where('id', '!=', $cable->id)
            ->orderBy('name')
            ->get()
            ->filter(function (CableSegment $other) use ($endpointIds) {
                $otherPoints = CableRoute::pointIds(CableRoute::normalize($other->route, $other));

                if (count($otherPoints) < 2) {
                    return false;
                }

                $otherEndpoints = [$otherPoints[0], $otherPoints[array_key_last($otherPoints)]];

                return count(array_intersect($endpointIds, $otherEndpoints)) > 0;
            })
            ->map(function (CableSegment $other) use ($route) {
                try {
                    $oriented = CableRoute::orientForJoin($route, CableRoute::normalize($other->route, $other));
                } catch (\Throwable) {
                    return null;
                }

                $junctionId = $oriented['junction_point_id'];
                $junction = NetworkPoint::query()->find($junctionId);

                return [
                    'id' => $other->id,
                    'name' => $other->name ?? ('Cable #'.$other->id),
                    'cable_type' => $other->cable_type,
                    'junction_point_id' => $junctionId,
                    'junction_point_name' => $junction?->name,
                    'label' => ($other->name ?? 'Cable #'.$other->id).' @ '.($junction?->name ?? "#{$junctionId}"),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{point_id: int, name: string|null}>
     */
    public function splitOptions(CableSegment $cable): array
    {
        $nodes = CableRoute::intermediatePointNodes(CableRoute::normalize($cable->route, $cable));
        $points = NetworkPoint::query()
            ->whereIn('id', collect($nodes)->pluck('point_id'))
            ->get(['id', 'name'])
            ->keyBy('id');

        return collect($nodes)->map(fn (array $node) => [
            'point_id' => $node['point_id'],
            'name' => $points->get($node['point_id'])?->name,
        ])->values()->all();
    }

    protected function segmentName(?string $name, int $part): string
    {
        $base = trim($name ?? '') ?: 'Cable';

        if (preg_match('/\(\d+\)$/', $base)) {
            return preg_replace('/\(\d+\)$/', "({$part})", $base) ?? "{$base} ({$part})";
        }

        return "{$base} ({$part})";
    }

    protected function splitLengths(
        CableSegment $cableA,
        CableSegment $cableB,
        ?float $originalLength,
        ?float $originalMapDistance,
    ): void {
        $mapA = Geo::cableMapDistanceM($cableA);
        $mapB = Geo::cableMapDistanceM($cableB);

        if ($originalLength !== null && $originalMapDistance && $originalMapDistance > 0 && $mapA !== null && $mapB !== null) {
            $ratio = $mapA / $originalMapDistance;
            $cableA->update(['length_m' => round($originalLength * $ratio, 2)]);
            $cableB->update(['length_m' => round($originalLength * (1 - $ratio), 2)]);

            return;
        }

        if ($mapA !== null) {
            $cableA->update(['length_m' => round($mapA, 2)]);
        }

        if ($mapB !== null) {
            $cableB->update(['length_m' => round($mapB, 2)]);
        }
    }

    protected function splitCores(
        CableSegment $cableA,
        CableSegment $cableB,
        int $splitPointId,
        int $originalEndPointId,
    ): void {
        if (! $cableA->cores->count()) {
            return;
        }

        foreach ($cableA->cores as $core) {
            /** @var CableCoreEnd|null $startEnd */
            $startEnd = $core->ends->firstWhere('side', 'start');
            /** @var CableCoreEnd|null $endEnd */
            $endEnd = $core->ends->firstWhere('side', 'end');

            $coreB = CableCore::query()->create([
                'cable_segment_id' => $cableB->id,
                'core_number' => $core->core_number,
                'color' => $core->color,
                'label' => $core->label,
                'status' => $core->status,
            ]);

            $startB = CableCoreEnd::query()->create([
                'cable_core_id' => $coreB->id,
                'side' => 'start',
                'network_point_id' => $splitPointId,
            ]);

            $endB = CableCoreEnd::query()->create([
                'cable_core_id' => $coreB->id,
                'side' => 'end',
                'network_point_id' => $originalEndPointId,
                'connection_type' => $endEnd?->connection_type,
                'network_point_port_id' => $endEnd?->network_point_port_id,
                'network_point_device_id' => $endEnd?->network_point_device_id,
                'device_type' => $endEnd?->device_type,
                'device_label' => $endEnd?->device_label,
                'device_port_label' => $endEnd?->device_port_label,
                'device_port_direction' => $endEnd?->device_port_direction,
                'connected_core_end_id' => $endEnd?->connection_type === 'core_end' ? $endEnd->connected_core_end_id : null,
            ]);

            if ($endEnd) {
                $endEnd->update([
                    'network_point_id' => $splitPointId,
                    'connection_type' => 'core_end',
                    'connected_core_end_id' => $startB->id,
                    'network_point_port_id' => null,
                    'network_point_device_id' => null,
                    'device_type' => null,
                    'device_label' => null,
                    'device_port_label' => null,
                    'device_port_direction' => null,
                ]);
            }

            $startB->update([
                'connection_type' => 'core_end',
                'connected_core_end_id' => $endEnd?->id,
            ]);
        }
    }

    protected function mergeCores(
        CableSegment $primary,
        CableSegment $secondary,
        int $junctionPointId,
        int $mergedEndPointId,
    ): void {
        $secondaryByNumber = $secondary->cores->keyBy('core_number');

        foreach ($primary->cores as $primaryCore) {
            $secondaryCore = $secondaryByNumber->get($primaryCore->core_number);

            if (! $secondaryCore) {
                continue;
            }

            /** @var CableCoreEnd|null $primaryEnd */
            $primaryEnd = $primaryCore->ends->firstWhere('side', 'end');
            /** @var CableCoreEnd|null $secondaryEnd */
            $secondaryEnd = $secondaryCore->ends->firstWhere('side', 'end');

            if ($primaryEnd && $secondaryEnd) {
                $primaryEnd->update([
                    'network_point_id' => $mergedEndPointId,
                    'connection_type' => $secondaryEnd->connection_type,
                    'network_point_port_id' => $secondaryEnd->network_point_port_id,
                    'network_point_device_id' => $secondaryEnd->network_point_device_id,
                    'device_type' => $secondaryEnd->device_type,
                    'device_label' => $secondaryEnd->device_label,
                    'device_port_label' => $secondaryEnd->device_port_label,
                    'device_port_direction' => $secondaryEnd->device_port_direction,
                    'connected_core_end_id' => $secondaryEnd->connection_type === 'core_end'
                        ? $secondaryEnd->connected_core_end_id
                        : null,
                ]);
            }
        }

        $primaryNumbers = $primary->cores->pluck('core_number');
        $missing = $secondary->cores->whereNotIn('core_number', $primaryNumbers);

        foreach ($missing as $secondaryCore) {
            $clone = CableCore::query()->create([
                'cable_segment_id' => $primary->id,
                'core_number' => $secondaryCore->core_number,
                'color' => $secondaryCore->color,
                'label' => $secondaryCore->label,
                'status' => $secondaryCore->status,
            ]);

            foreach ($secondaryCore->ends as $end) {
                CableCoreEnd::query()->create([
                    'cable_core_id' => $clone->id,
                    'side' => $end->side,
                    'network_point_id' => $end->side === 'end' ? $mergedEndPointId : $junctionPointId,
                    'connection_type' => $end->connection_type,
                    'network_point_port_id' => $end->network_point_port_id,
                    'network_point_device_id' => $end->network_point_device_id,
                    'device_type' => $end->device_type,
                    'device_label' => $end->device_label,
                    'device_port_label' => $end->device_port_label,
                    'device_port_direction' => $end->device_port_direction,
                    'connected_core_end_id' => $end->connected_core_end_id,
                ]);
            }
        }
    }

    protected function combinedLength(CableSegment $primary, CableSegment $secondary): ?float
    {
        if ($primary->length_m !== null && $secondary->length_m !== null) {
            return round((float) $primary->length_m + (float) $secondary->length_m, 2);
        }

        $mapA = Geo::cableMapDistanceM($primary);
        $mapB = Geo::cableMapDistanceM($secondary);

        if ($mapA !== null && $mapB !== null) {
            return round($mapA + $mapB, 2);
        }

        return $primary->length_m ?? $secondary->length_m;
    }
}
