<?php

namespace App\Support;

use App\Models\NetworkPoint;

class NetworkPointSnapshot
{
    /**
     * @return array<string, array{value: mixed, label?: string}>
     */
    public static function fields(NetworkPoint $point): array
    {
        $types = is_array($point->types) ? $point->types : array_filter([$point->type]);

        return [
            'name' => ActivityLogDetailsBuilder::field($point->name),
            'types' => ActivityLogDetailsBuilder::field($types),
            'status' => ActivityLogDetailsBuilder::field($point->status),
            'area' => ActivityLogDetailsBuilder::field($point->area),
            'location' => ActivityLogDetailsBuilder::field(
                $point->latitude !== null && $point->longitude !== null
                    ? "{$point->latitude}, {$point->longitude}"
                    : null,
            ),
            'address' => ActivityLogDetailsBuilder::field($point->address),
            'notes' => ActivityLogDetailsBuilder::field($point->notes),
            'contact_name' => ActivityLogDetailsBuilder::field($point->contact_name),
            'contact_phone' => ActivityLogDetailsBuilder::field($point->contact_phone),
            'port_count' => ActivityLogDetailsBuilder::field($point->port_count),
            'devices' => ActivityLogDetailsBuilder::field(self::devicesLabel($point)),
        ];
    }

    private static function devicesLabel(NetworkPoint $point): ?string
    {
        if (! $point->relationLoaded('devices')) {
            return null;
        }

        $parts = $point->devices
            ->map(function ($device) {
                $label = trim((string) $device->label);
                $type = (string) $device->type;
                $summary = $label !== '' ? "{$label} ({$type})" : $type;

                if ($device->relationLoaded('ports') && $device->ports->isNotEmpty()) {
                    $ports = $device->ports
                        ->map(fn ($port) => "{$port->label} ({$port->direction})")
                        ->implode(', ');

                    return "{$summary}: {$ports}";
                }

                return $summary;
            })
            ->filter()
            ->values();

        return $parts->isEmpty() ? null : $parts->implode(' · ');
    }
}
