<?php

use App\Enums\PermissionKey;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Permission::query()->where('key', 'activity.view_all')->delete();

        $permission = Permission::query()->updateOrCreate(
            ['key' => PermissionKey::ActivityView->value],
            [
                'label' => PermissionKey::ActivityView->label(),
                'group' => PermissionKey::ActivityView->group(),
            ],
        );

        $ownerRoles = Role::query()->where('slug', 'owner')->get();

        foreach ($ownerRoles as $ownerRole) {
            $ownerRole->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    public function down(): void
    {
        $permission = Permission::query()->where('key', PermissionKey::ActivityView->value)->first();

        if (! $permission) {
            return;
        }

        DB::table('permission_role')->where('permission_id', $permission->id)->delete();
        $permission->delete();
    }
};
