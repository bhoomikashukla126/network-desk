<?php

namespace App\Enums;

enum PermissionKey: string
{
    case NetworkView = 'network.view';
    case NetworkCreate = 'network.create';
    case NetworkEdit = 'network.edit';
    case NetworkDelete = 'network.delete';
    case ActivityView = 'activity.view';
    case RolesManage = 'roles.manage';
    case MembersManage = 'members.manage';

    public function label(): string
    {
        return match ($this) {
            self::NetworkView => 'View network',
            self::NetworkCreate => 'Create network points',
            self::NetworkEdit => 'Edit network points',
            self::NetworkDelete => 'Delete network points',
            self::ActivityView => 'View activity',
            self::RolesManage => 'Manage roles',
            self::MembersManage => 'Manage members',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::NetworkView,
            self::NetworkCreate,
            self::NetworkEdit,
            self::NetworkDelete => 'network',
            self::ActivityView => 'activity',
            self::RolesManage => 'roles',
            self::MembersManage => 'members',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission) => $permission->value, self::cases());
    }
}
