<?php

namespace App\Support;

use App\Models\CableSegment;
use App\Models\WorkspaceCableType;
use Illuminate\Support\Str;

class CableTypeCatalog
{
    /**
     * @return array<string, string>
     */
    public static function defaultColors(): array
    {
        return [
            'fiber' => '#8b5cf6',
            'coax' => '#f97316',
            'ethernet' => '#0ea5e9',
            'wireless' => '#10b981',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function forWorkspace(string $workspaceId): array
    {
        $custom = WorkspaceCableType::query()
            ->where('workspace_id', $workspaceId)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->pluck('label', 'key')
            ->all();

        return array_merge(NetworkCatalog::cableTypes(), $custom);
    }

    /**
     * @return array<string, string>
     */
    public static function colorsForWorkspace(string $workspaceId): array
    {
        $colors = self::defaultColors();

        WorkspaceCableType::query()
            ->where('workspace_id', $workspaceId)
            ->get(['key', 'color'])
            ->each(function (WorkspaceCableType $type) use (&$colors): void {
                $colors[$type->key] = $type->color;
            });

        return $colors;
    }

    /**
     * @return array<int, array{id: int, key: string, label: string, color: string}>
     */
    public static function customTypesForWorkspace(string $workspaceId): array
    {
        return WorkspaceCableType::query()
            ->where('workspace_id', $workspaceId)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['id', 'key', 'label', 'color'])
            ->map(fn (WorkspaceCableType $type) => [
                'id' => $type->id,
                'key' => $type->key,
                'label' => $type->label,
                'color' => $type->color,
            ])
            ->values()
            ->all();
    }

    public static function isAllowed(string $workspaceId, string $key): bool
    {
        if (array_key_exists($key, self::forWorkspace($workspaceId))) {
            return true;
        }

        return CableSegment::query()
            ->where('workspace_id', $workspaceId)
            ->where('cable_type', $key)
            ->exists();
    }

    public static function makeKey(string $workspaceId, string $label): string
    {
        $base = Str::slug($label, '_');

        if ($base === '') {
            $base = 'cable_type';
        }

        $base = Str::limit($base, 28, '');
        $key = $base;
        $suffix = 2;

        while (
            array_key_exists($key, NetworkCatalog::cableTypes())
            || WorkspaceCableType::query()->where('workspace_id', $workspaceId)->where('key', $key)->exists()
        ) {
            $key = Str::limit($base, 28 - strlen((string) $suffix), '').'_'.$suffix;
            $suffix++;
        }

        return $key;
    }

    public static function nextColor(string $workspaceId): string
    {
        $palette = [
            '#ec4899',
            '#14b8a6',
            '#eab308',
            '#6366f1',
            '#ef4444',
            '#84cc16',
            '#06b6d4',
            '#a855f7',
        ];

        $used = array_values(self::colorsForWorkspace($workspaceId));

        foreach ($palette as $color) {
            if (! in_array($color, $used, true)) {
                return $color;
            }
        }

        return $palette[WorkspaceCableType::query()->where('workspace_id', $workspaceId)->count() % count($palette)];
    }
}
