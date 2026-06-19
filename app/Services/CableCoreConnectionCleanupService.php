<?php

namespace App\Services;

use App\Models\CableCoreEnd;
use App\Models\CableSegment;
use App\Support\DeviceConnectionLabel;

class CableCoreConnectionCleanupService
{
    /**
     * Detach splice and device mappings before a cable segment is removed.
     */
    public function detachCable(CableSegment $cable): void
    {
        $cable->loadMissing('cores.ends');

        foreach ($cable->cores as $core) {
            foreach ($core->ends as $end) {
                $this->detachCoreEnd($end);
            }
        }
    }

    /**
     * Clear partners wired to this core end (splice or device context).
     */
    public function detachCoreEnd(CableCoreEnd $end): void
    {
        CableCoreEnd::query()
            ->where('connected_core_end_id', $end->id)
            ->each(fn (CableCoreEnd $partner) => $this->clearSpliceConnection($partner));

        if ($end->connection_type === 'core_end' && $end->connected_core_end_id) {
            $partner = CableCoreEnd::query()->find($end->connected_core_end_id);

            if ($partner && (int) $partner->connected_core_end_id === (int) $end->id) {
                $this->clearSpliceConnection($partner);
            }
        }
    }

    public function detachPort(int $portId): void
    {
        CableCoreEnd::query()
            ->where('network_point_port_id', $portId)
            ->each(fn (CableCoreEnd $end) => $this->clearDeviceConnection($end));
    }

    public function detachDevice(int $deviceId): void
    {
        CableCoreEnd::query()
            ->where('network_point_device_id', $deviceId)
            ->each(fn (CableCoreEnd $end) => $this->clearDeviceConnection($end));
    }

    /**
     * Fix stale rows where FK was nulled by cascade but connection_type remained set.
     */
    public function repairOrphanedConnections(?string $workspaceId = null): int
    {
        $query = CableCoreEnd::query()
            ->where(function ($builder): void {
                $builder->where(function ($splice) {
                    $splice->where('connection_type', 'core_end')
                        ->whereNull('connected_core_end_id');
                })->orWhere(function ($device) {
                    $device->where('connection_type', 'device')
                        ->whereNull('network_point_port_id')
                        ->where(function ($label) {
                            $label->whereNull('device_port_label')
                                ->orWhere('device_port_label', '');
                        });
                });
            });

        if ($workspaceId) {
            $query->whereHas('core.cable', fn ($cable) => $cable->where('workspace_id', $workspaceId));
        }

        $repaired = 0;

        $query->each(function (CableCoreEnd $end) use (&$repaired): void {
            if ($end->connection_type === 'core_end') {
                $this->clearSpliceConnection($end);
            } else {
                $this->clearDeviceConnection($end);
            }

            $repaired += 1;
        });

        return $repaired;
    }

    public function clearSpliceConnection(CableCoreEnd $end): void
    {
        DeviceConnectionLabel::clearPort($end);

        $end->update([
            'connection_type' => null,
            'connected_core_end_id' => null,
        ]);
    }

    public function clearDeviceConnection(CableCoreEnd $end): void
    {
        DeviceConnectionLabel::clearPort($end);

        $end->update([
            'connection_type' => null,
            'connected_core_end_id' => null,
        ]);
    }

    public function isCoreEndAvailable(CableCoreEnd $end): bool
    {
        if ($end->connection_type === 'device') {
            return $end->network_point_port_id === null
                && trim((string) ($end->device_port_label ?? '')) === '';
        }

        if ($end->connection_type === 'core_end') {
            return $end->connected_core_end_id === null;
        }

        return true;
    }
}
