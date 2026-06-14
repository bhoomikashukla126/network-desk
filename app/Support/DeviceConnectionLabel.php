<?php

namespace App\Support;

use App\Models\CableCoreEnd;
use App\Models\NetworkPointPort;

class DeviceConnectionLabel
{
    public static function applyPort(CableCoreEnd $end, NetworkPointPort $port): void
    {
        $port->loadMissing('device');

        $end->connection_type = 'device';
        $end->network_point_port_id = $port->id;
        $end->network_point_device_id = $port->network_point_device_id;
        $end->device_type = $port->device?->type;
        $end->device_label = $port->device?->label;
        $end->device_port_label = $port->label;
        $end->device_port_direction = $port->direction;
    }

    public static function clearPort(CableCoreEnd $end): void
    {
        $end->network_point_port_id = null;
        $end->network_point_device_id = null;
        $end->device_type = null;
        $end->device_label = null;
        $end->device_port_label = null;
        $end->device_port_direction = null;
    }

    /**
     * @param  array<string, string>  $typeLabels
     */
    public static function forCoreEnd(CableCoreEnd $end, array $typeLabels = []): ?string
    {
        if ($end->connection_type !== 'device') {
            return null;
        }

        $end->loadMissing(['networkPoint', 'networkPointPort.device']);

        $direction = $end->device_port_direction === 'input' ? 'in' : 'out';
        $portLabel = $end->device_port_label ?: $end->networkPointPort?->label ?: 'Port';
        $pointName = $end->networkPoint?->name;
        $deviceType = $end->device_type ?: $end->networkPointPort?->device?->type;
        $typeLabel = $deviceType
            ? ($typeLabels[$deviceType] ?? NetworkCatalog::pointTypes()[$deviceType] ?? $deviceType)
            : null;
        $deviceLabel = $end->device_label ?: $end->networkPointPort?->device?->label;

        $parts = array_values(array_filter([
            $pointName,
            $typeLabel,
            $deviceLabel,
            "{$portLabel} ({$direction})",
        ]));

        return $parts !== [] ? implode(' · ', $parts) : null;
    }
}
