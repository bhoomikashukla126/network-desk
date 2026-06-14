<?php

namespace App\Support;

use Illuminate\Http\Request;

class WorkspaceSession
{
    /**
     * @return array<string, mixed>|null
     */
    public static function get(?Request $request = null): ?array
    {
        $workspace = ($request ?? request())->session()->get('central_workspace');

        return is_array($workspace) ? $workspace : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function member(?Request $request = null): ?array
    {
        $member = ($request ?? request())->session()->get('workspace_member');

        return is_array($member) ? $member : null;
    }

    /**
     * @return array<int, string>
     */
    public static function permissions(?Request $request = null): array
    {
        $member = self::member($request);

        return is_array($member['permissions'] ?? null) ? $member['permissions'] : [];
    }

    public static function hasPermission(string $permission, ?Request $request = null): bool
    {
        return in_array($permission, self::permissions($request), true);
    }

    public static function canEdit(?Request $request = null): bool
    {
        return self::hasPermission('network.create', $request)
            || self::hasPermission('network.edit', $request);
    }

    public static function isOwner(?Request $request = null): bool
    {
        $member = self::member($request);

        return ($member['role']['slug'] ?? '') === 'owner';
    }

    public static function roleLabel(?Request $request = null): string
    {
        $member = self::member($request);

        return (string) ($member['role']['name'] ?? 'Guest');
    }

    public static function id(?Request $request = null): string
    {
        $workspace = self::get($request);

        return is_array($workspace) && ! empty($workspace['id'])
            ? (string) $workspace['id']
            : 'default';
    }

    /**
     * @return array<string, int|float>
     */
    public static function quotas(?Request $request = null): array
    {
        $workspace = self::get($request) ?? [];

        $maxStorageMb = (int) ($workspace['max_storage_mb'] ?? 0);
        $maxStorageBytes = (int) ($workspace['max_storage_bytes'] ?? ($maxStorageMb * 1024 * 1024));
        $extensionStorageBytes = app(\App\Services\WorkspaceQuotaService::class)->measureStorageBytes(self::id($request));
        $usageStorageBytes = max((int) ($workspace['usage_storage_bytes'] ?? 0), $extensionStorageBytes);
        $remainingStorageBytes = max(0, $maxStorageBytes - $usageStorageBytes);

        return [
            'max_activities' => (int) ($workspace['max_activities'] ?? 0),
            'max_storage_mb' => $maxStorageMb,
            'max_storage_bytes' => $maxStorageBytes,
            'usage_activities' => (int) ($workspace['usage_activities'] ?? 0),
            'usage_storage_mb' => (float) ($workspace['usage_storage_mb'] ?? 0),
            'usage_storage_bytes' => $usageStorageBytes,
            'remaining_activities' => (int) ($workspace['remaining_activities'] ?? 0),
            'remaining_storage_mb' => (float) ($workspace['remaining_storage_mb'] ?? 0),
            'remaining_storage_bytes' => $remainingStorageBytes,
            'extension_requests' => app(\App\Services\WorkspaceQuotaService::class)->requestCount(self::id($request)),
            'extension_storage_bytes' => $extensionStorageBytes,
        ];
    }
}
