<?php

namespace App\Support;

use App\Models\CableSegment;

class Geo
{
    private const EARTH_RADIUS_M = 6371000;

    public static function haversineDistanceM(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_M * asin(min(1, sqrt($a)));
    }

    /**
     * @param  array<int, array{0: float|int, 1: float|int}>  $coordinates
     */
    public static function pathDistanceM(array $coordinates): float
    {
        $total = 0.0;

        for ($index = 1; $index < count($coordinates); $index++) {
            $previous = $coordinates[$index - 1];
            $current = $coordinates[$index];

            $total += self::haversineDistanceM(
                (float) $previous[0],
                (float) $previous[1],
                (float) $current[0],
                (float) $current[1],
            );
        }

        return $total;
    }

    public static function cableMapDistanceM(CableSegment $cable): ?float
    {
        $route = CableRoute::normalize($cable->route, $cable);
        $coordinates = CableRoute::coordinates($route, $cable);

        return count($coordinates) >= 2 ? self::pathDistanceM($coordinates) : null;
    }

    public static function formatDistanceM(?float $meters): string
    {
        if ($meters === null) {
            return '—';
        }

        if ($meters >= 1000) {
            return number_format($meters / 1000, 2).' km';
        }

        return number_format($meters, 0).' m';
    }
}
