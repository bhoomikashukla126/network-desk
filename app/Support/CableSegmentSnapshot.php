<?php

namespace App\Support;

use App\Models\CableSegment;

class CableSegmentSnapshot
{
    /**
     * @return array<string, array{value: mixed, label?: string}>
     */
    public static function fields(CableSegment $cable): array
    {
        $cable->loadMissing(['fromPoint:id,name', 'toPoint:id,name']);

        return [
            'name' => ActivityLogDetailsBuilder::field($cable->name),
            'cable_type' => ActivityLogDetailsBuilder::field($cable->cable_type),
            'status' => ActivityLogDetailsBuilder::field($cable->status),
            'length_m' => ActivityLogDetailsBuilder::field($cable->length_m),
            'core_count' => ActivityLogDetailsBuilder::field($cable->core_count),
            'from_point' => ActivityLogDetailsBuilder::field($cable->fromPoint?->name),
            'to_point' => ActivityLogDetailsBuilder::field($cable->toPoint?->name),
            'notes' => ActivityLogDetailsBuilder::field($cable->notes),
        ];
    }
}
