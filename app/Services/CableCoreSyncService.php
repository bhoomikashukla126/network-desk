<?php

namespace App\Services;

use App\Models\CableCore;
use App\Models\CableCoreEnd;
use App\Models\CableSegment;
use App\Models\NetworkPointPort;
use App\Support\CableRoute;
use App\Support\DeviceConnectionLabel;
use App\Support\FiberCoreColors;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CableCoreSyncService
{
    /**
     * @param  array{core_count?: int|null, cores?: array<int, array<string, mixed>>}  $payload
     */
    public function sync(CableSegment $cable, array $payload): void
    {
        if (! array_key_exists('core_count', $payload) && ! array_key_exists('cores', $payload)) {
            return;
        }

        $coreCount = (int) ($payload['core_count'] ?? 0);
        $coresInput = $payload['cores'] ?? [];

        abort_if($coreCount < 0, 422, 'Core count cannot be negative.');
        abort_if($coreCount > 288, 422, 'Core count cannot exceed 288.');

        if ($coreCount === 0) {
            $cable->cores()->delete();
            $cable->update(['core_count' => null]);

            return;
        }

        $routePointIds = CableRoute::pointIds(CableRoute::normalize($cable->route, $cable));
        abort_unless(count($routePointIds) >= 2, 422, 'Cable route must have at least two points before defining cores.');

        $startPointId = $routePointIds[0];
        $endPointId = $routePointIds[array_key_last($routePointIds)];

        DB::transaction(function () use ($cable, $coreCount, $coresInput, $startPointId, $endPointId): void {
            $cable->update(['core_count' => $coreCount]);

            $existing = $cable->cores()->with('ends')->get()->keyBy('core_number');
            $keptNumbers = [];
            $claimedPortIds = [];

            for ($number = 1; $number <= $coreCount; $number += 1) {
                $keptNumbers[] = $number;
                $input = collect($coresInput)->firstWhere('core_number', $number) ?? [];

                /** @var CableCore $core */
                $core = $existing->get($number) ?? new CableCore([
                    'cable_segment_id' => $cable->id,
                    'core_number' => $number,
                ]);

                $core->fill([
                    'color' => $input['color'] ?? $core->color ?? FiberCoreColors::forCoreNumber($number),
                    'label' => $input['label'] ?? null,
                    'status' => $input['status'] ?? $core->status ?? 'active',
                ]);
                $core->save();

                $this->syncEnd($core, 'start', $startPointId, $input['ends']['start'] ?? $input['start'] ?? [], $claimedPortIds);
                $this->syncEnd($core, 'end', $endPointId, $input['ends']['end'] ?? $input['end'] ?? [], $claimedPortIds);
            }

            $cable->cores()->whereNotIn('core_number', $keptNumbers)->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, int>  $claimedPortIds
     */
    protected function syncEnd(CableCore $core, string $side, int $pointId, array $input, array &$claimedPortIds): void
    {
        /** @var CableCoreEnd $end */
        $end = $core->ends()->firstOrNew(['side' => $side]);
        $previousSplicePartnerId = $end->exists && $end->connection_type === 'core_end'
            ? $end->connected_core_end_id
            : null;

        if ($previousSplicePartnerId) {
            $this->unlinkSplicePartner($end);
        }

        $end->network_point_id = $pointId;
        $end->connection_type = null;
        $end->connected_core_end_id = null;
        DeviceConnectionLabel::clearPort($end);

        $type = $input['connection_type'] ?? null;

        if ($type === 'device') {
            $portId = (int) ($input['network_point_port_id'] ?? 0);

            if ($portId > 0) {
                abort_if(isset($claimedPortIds[$portId]), 422, 'Each device port can connect to only one cable core at a time.');

                $port = NetworkPointPort::query()->with('device')->find($portId);

                abort_if(! $port, 422, 'Selected device port was not found.');
                abort_if((int) $port->network_point_id !== (int) $pointId, 422, 'Selected port does not belong to this route point.');

                $this->assertPortAvailable($portId, $end->exists ? (int) $end->id : null);

                DeviceConnectionLabel::applyPort($end, $port);
                $claimedPortIds[$portId] = $portId;
            } else {
                $label = trim((string) ($input['device_port_label'] ?? ''));

                abort_if($label === '', 422, "Device port label is required for core {$core->core_number} {$side} end.");

                $direction = $input['device_port_direction'] ?? null;
                abort_unless(in_array($direction, ['input', 'output'], true), 422, 'Device port direction must be input or output.');

                $end->connection_type = 'device';
                $end->device_port_label = $label;
                $end->device_port_direction = $direction;
                $end->device_type = trim((string) ($input['device_type'] ?? '')) ?: null;
                $end->device_label = trim((string) ($input['device_label'] ?? '')) ?: null;
            }

            $end->save();
        } elseif ($type === 'core_end') {
            $connectedId = (int) ($input['connected_core_end_id'] ?? 0);

            abort_if($connectedId <= 0, 422, 'Select a core end to connect to.');

            $target = CableCoreEnd::query()
                ->with('core.cable')
                ->find($connectedId);

            abort_if(! $target, 422, 'Connected core end was not found.');

            $cableWorkspaceId = CableSegment::query()
                ->whereKey($core->cable_segment_id)
                ->value('workspace_id');

            abort_if(
                $target->core?->cable?->workspace_id !== $cableWorkspaceId,
                422,
                'Connected core end must belong to this workspace.',
            );
            abort_if(
                (int) $target->network_point_id !== (int) $pointId,
                422,
                'Connected core end must be at the same route point.',
            );
            abort_if(
                (int) $target->id === (int) $end->id,
                422,
                'A core end cannot connect to itself.',
            );
            abort_if(
                $target->connection_type === 'device',
                422,
                'Cannot splice to a core end that is already connected to a device port.',
            );

            $this->assertCoreEndAvailableForSplice($target, $end->exists ? (int) $end->id : null);

            $end->connection_type = 'core_end';
            $end->connected_core_end_id = $connectedId;
            $end->save();

            $this->linkSplicePartner($end, $target);

            return;
        }

        $end->save();
    }

    protected function assertPortAvailable(int $portId, ?int $exceptEndId): void
    {
        $query = CableCoreEnd::query()
            ->where('connection_type', 'device')
            ->where('network_point_port_id', $portId);

        if ($exceptEndId) {
            $query->where('id', '!=', $exceptEndId);
        }

        abort_if($query->exists(), 422, 'This device port is already connected to another cable core.');
    }

    protected function assertCoreEndAvailableForSplice(CableCoreEnd $target, ?int $exceptEndId): void
    {
        if ($target->connection_type !== 'core_end' || ! $target->connected_core_end_id) {
            return;
        }

        if ($exceptEndId && (int) $target->connected_core_end_id === $exceptEndId) {
            return;
        }

        abort(422, 'Selected core end is already spliced to another cable core.');
    }

    protected function linkSplicePartner(CableCoreEnd $end, CableCoreEnd $partner): void
    {
        DeviceConnectionLabel::clearPort($partner);

        $partner->update([
            'connection_type' => 'core_end',
            'connected_core_end_id' => $end->id,
        ]);

        $end->update([
            'connection_type' => 'core_end',
            'connected_core_end_id' => $partner->id,
        ]);
    }

    protected function unlinkSplicePartner(CableCoreEnd $end): void
    {
        if (! $end->connected_core_end_id) {
            return;
        }

        $partner = CableCoreEnd::query()->find($end->connected_core_end_id);

        if ($partner && (int) $partner->connected_core_end_id === (int) $end->id) {
            $partner->update([
                'connection_type' => null,
                'connected_core_end_id' => null,
            ]);
        }

        $end->connected_core_end_id = null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function connectionOptions(string $workspaceId, CableSegment $cable): array
    {
        app(CableCoreConnectionCleanupService::class)->repairOrphanedConnections($workspaceId);

        $routePointIds = CableRoute::pointIds(CableRoute::normalize($cable->route, $cable));

        return CableCoreEnd::query()
            ->whereHas('core.cable', fn ($query) => $query->where('workspace_id', $workspaceId))
            ->when($routePointIds !== [], fn ($query) => $query->whereIn('network_point_id', $routePointIds))
            ->with([
                'core.cable:id,name,core_count,cable_type,route,from_point_id,to_point_id',
                'networkPoint:id,name',
                'connectedCoreEnd.core.cable:id,name,core_count',
                'networkPointPort:id,label',
            ])
            ->orderBy('network_point_id')
            ->orderBy('id')
            ->get()
            ->filter(function (CableCoreEnd $end) use ($routePointIds) {
                $cable = $end->core?->cable;

                if (! $cable) {
                    return false;
                }

                $cableRouteIds = CableRoute::pointIds(CableRoute::normalize($cable->route, $cable));

                if (count($cableRouteIds) < 2) {
                    return false;
                }

                $expectedPointId = $end->side === 'start'
                    ? (int) $cableRouteIds[0]
                    : (int) $cableRouteIds[array_key_last($cableRouteIds)];

                if (! in_array($expectedPointId, $routePointIds, true)) {
                    return false;
                }

                return CableRoute::isCoreSideAtRoutePoint($cable, (string) $end->side, $expectedPointId);
            })
            ->map(fn (CableCoreEnd $end) => [
                'id' => $end->id,
                'cable_id' => $end->core?->cable_segment_id,
                'cable_name' => $end->core?->cable?->name ?? ('Cable #'.$end->core?->cable_segment_id),
                'cable_core_count' => $end->core?->cable?->core_count,
                'core_number' => $end->core?->core_number,
                'core_label' => $end->core?->label,
                'core_color' => $end->core?->color,
                'side' => $end->side,
                'network_point_id' => $end->network_point_id,
                'network_point_name' => $end->networkPoint?->name,
                'connection_type' => $end->connection_type,
                'connected_core_end_id' => $end->connected_core_end_id,
                'connection_label' => $this->connectionLabel($end),
            ])
            ->values()
            ->all();
    }

    protected function connectionLabel(CableCoreEnd $end): ?string
    {
        if ($end->connection_type === 'device') {
            return DeviceConnectionLabel::forCoreEnd($end);
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
