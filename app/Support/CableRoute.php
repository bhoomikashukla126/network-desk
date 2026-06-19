<?php

namespace App\Support;

use App\Models\CableSegment;

class CableRoute
{
    /**
     * @param  array<int, array<string, mixed>>|null  $route
     * @return list<array{type: string, point_id?: int, lat?: float, lng?: float}>
     */
    public static function normalize(?array $route, ?CableSegment $cable = null): array
    {
        if (is_array($route) && count($route) >= 2) {
            return self::sanitize($route);
        }

        if ($cable) {
            return self::fromLegacy($cable);
        }

        return [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $route
     * @return list<array{type: string, point_id?: int, lat?: float, lng?: float}>
     */
    public static function sanitize(array $route): array
    {
        $normalized = [];

        foreach ($route as $node) {
            if (! is_array($node)) {
                continue;
            }

            $type = $node['type'] ?? null;

            if ($type === 'point' && isset($node['point_id'])) {
                $normalized[] = [
                    'type' => 'point',
                    'point_id' => (int) $node['point_id'],
                ];

                continue;
            }

            if ($type === 'bend' && isset($node['lat'], $node['lng'])) {
                $normalized[] = [
                    'type' => 'bend',
                    'lat' => (float) $node['lat'],
                    'lng' => (float) $node['lng'],
                ];
            }
        }

        return $normalized;
    }

    /**
     * @return list<array{type: string, point_id?: int, lat?: float, lng?: float}>
     */
    public static function fromLegacy(CableSegment $cable): array
    {
        $route = [
            ['type' => 'point', 'point_id' => (int) $cable->from_point_id],
        ];

        foreach ($cable->path ?? [] as $pair) {
            if (is_array($pair) && count($pair) === 2) {
                $route[] = [
                    'type' => 'bend',
                    'lat' => (float) $pair[0],
                    'lng' => (float) $pair[1],
                ];
            }
        }

        $route[] = ['type' => 'point', 'point_id' => (int) $cable->to_point_id];

        return $route;
    }

    /**
     * @param  list<array{type: string, point_id?: int, lat?: float, lng?: float}>  $route
     * @return list<int>
     */
    public static function pointIds(array $route): array
    {
        $ids = [];

        foreach ($route as $node) {
            if (($node['type'] ?? null) === 'point' && isset($node['point_id'])) {
                $ids[] = (int) $node['point_id'];
            }
        }

        return $ids;
    }

    /**
     * @param  list<array{type: string, point_id?: int, lat?: float, lng?: float}>  $route
     * @return list<array{0: float, 1: float}>
     */
    public static function coordinates(array $route, CableSegment $cable): array
    {
        $points = self::loadPointsForRoute($route, $cable);
        $coordinates = [];

        foreach ($route as $node) {
            if (($node['type'] ?? null) === 'point') {
                $point = $points[(int) ($node['point_id'] ?? 0)] ?? null;

                if ($point) {
                    $coordinates[] = [(float) $point->latitude, (float) $point->longitude];
                }

                continue;
            }

            if (($node['type'] ?? null) === 'bend') {
                $coordinates[] = [(float) $node['lat'], (float) $node['lng']];
            }
        }

        return $coordinates;
    }

    /**
     * @param  list<array{type: string, point_id?: int, lat?: float, lng?: float}>  $route
     * @return list<array{0: float, 1: float}>
     */
    public static function legacyPath(array $route): array
    {
        $path = [];

        foreach ($route as $node) {
            if (($node['type'] ?? null) === 'bend') {
                $path[] = [(float) $node['lat'], (float) $node['lng']];
            }
        }

        return $path;
    }

    public static function isCoreSideAtRoutePoint(CableSegment $cable, string $side, int $pointId): bool
    {
        $routePointIds = self::pointIds(self::normalize($cable->route, $cable));

        if (count($routePointIds) < 2) {
            return false;
        }

        $first = (int) $routePointIds[0];
        $last = (int) $routePointIds[array_key_last($routePointIds)];

        if ($side === 'start') {
            return $pointId === $first;
        }

        if ($side === 'end') {
            return $pointId === $last;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function apply(array $validated): array
    {
        $route = self::sanitize($validated['route'] ?? []);
        $pointIds = self::pointIds($route);

        abort_unless(count($pointIds) >= 2, 422, 'A cable route must include at least two points.');

        $validated['route'] = $route;
        $validated['from_point_id'] = $pointIds[0];
        $validated['to_point_id'] = $pointIds[array_key_last($pointIds)];
        $validated['path'] = self::legacyPath($route);

        return $validated;
    }

    /**
     * @param  list<array{type: string, point_id?: int, lat?: float, lng?: float}>  $route
     * @return array{0: list<array>, 1: list<array>}
     */
    public static function splitAtPoint(array $route, int $splitPointId): array
    {
        $route = self::sanitize($route);
        $splitIndex = null;

        foreach ($route as $index => $node) {
            if (($node['type'] ?? null) === 'point' && (int) ($node['point_id'] ?? 0) === $splitPointId) {
                $splitIndex = $index;

                break;
            }
        }

        abort_if($splitIndex === null, 422, 'The split point is not on this cable route.');

        $pointNodeIndexes = [];

        foreach ($route as $index => $node) {
            if (($node['type'] ?? null) === 'point') {
                $pointNodeIndexes[] = $index;
            }
        }

        abort_if(
            count($pointNodeIndexes) < 3,
            422,
            'Cable must pass through at least three points to split.',
        );

        abort_if(
            $splitIndex === $pointNodeIndexes[0] || $splitIndex === $pointNodeIndexes[array_key_last($pointNodeIndexes)],
            422,
            'Cannot split at a route endpoint.',
        );

        $routeA = array_values(array_slice($route, 0, $splitIndex + 1));
        $routeB = array_values(array_slice($route, $splitIndex));

        abort_unless(count(self::pointIds($routeA)) >= 2 && count(self::pointIds($routeB)) >= 2, 422, 'Split would create an invalid segment.');

        return [$routeA, $routeB];
    }

    /**
     * @param  list<array{type: string, point_id?: int, lat?: float, lng?: float}>  $route
     * @return list<array{type: string, point_id?: int, lat?: float, lng?: float}>
     */
    public static function reverse(array $route): array
    {
        return array_values(array_reverse(self::sanitize($route)));
    }

    /**
     * @param  list<array{type: string, point_id?: int, lat?: float, lng?: float}>  $routeA
     * @param  list<array{type: string, point_id?: int, lat?: float, lng?: float}>  $routeB
     * @return array{route: list<array>, junction_point_id: int}
     */
    public static function orientForJoin(array $routeA, array $routeB): array
    {
        $routeA = self::sanitize($routeA);
        $routeB = self::sanitize($routeB);
        $aPoints = self::pointIds($routeA);
        $bPoints = self::pointIds($routeB);

        abort_unless(count($aPoints) >= 2 && count($bPoints) >= 2, 422, 'Both cables need at least two route points.');

        $aStart = $aPoints[0];
        $aEnd = $aPoints[array_key_last($aPoints)];
        $bStart = $bPoints[0];
        $bEnd = $bPoints[array_key_last($bPoints)];

        if ($aEnd === $bStart) {
            return [
                'route' => array_merge($routeA, array_slice($routeB, 1)),
                'junction_point_id' => $aEnd,
            ];
        }

        if ($aEnd === $bEnd) {
            return [
                'route' => array_merge($routeA, array_slice(self::reverse($routeB), 1)),
                'junction_point_id' => $aEnd,
            ];
        }

        if ($aStart === $bStart) {
            return [
                'route' => array_merge(self::reverse($routeA), array_slice($routeB, 1)),
                'junction_point_id' => $aStart,
            ];
        }

        if ($aStart === $bEnd) {
            return [
                'route' => array_merge(self::reverse($routeA), array_slice(self::reverse($routeB), 1)),
                'junction_point_id' => $aStart,
            ];
        }

        abort(422, 'These cables do not meet at a shared point.');
    }

    /**
     * @param  list<array{type: string, point_id?: int, lat?: float, lng?: float}>  $route
     * @return list<array{point_id: int}>
     */
    public static function intermediatePointNodes(array $route): array
    {
        $route = self::sanitize($route);
        $points = [];

        foreach ($route as $node) {
            if (($node['type'] ?? null) === 'point' && isset($node['point_id'])) {
                $points[] = ['point_id' => (int) $node['point_id']];
            }
        }

        if (count($points) < 3) {
            return [];
        }

        return array_slice($points, 1, -1);
    }

    private static function loadPointsForRoute(array $route, CableSegment $cable): array
    {
        $ids = self::pointIds($route);

        if ($ids === []) {
            return [];
        }

        $loaded = [];

        if ($cable->relationLoaded('fromPoint') && $cable->fromPoint) {
            $loaded[(int) $cable->fromPoint->id] = $cable->fromPoint;
        }

        if ($cable->relationLoaded('toPoint') && $cable->toPoint) {
            $loaded[(int) $cable->toPoint->id] = $cable->toPoint;
        }

        $missing = array_values(array_diff($ids, array_keys($loaded)));

        if ($missing !== []) {
            foreach (\App\Models\NetworkPoint::query()->whereIn('id', $missing)->get() as $point) {
                $loaded[(int) $point->id] = $point;
            }
        }

        return $loaded;
    }
}
