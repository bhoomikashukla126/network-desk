<?php

namespace App\Support;

use App\Services\WorkspaceAccessService;
use Illuminate\Http\Request;

class LocalDevAuth
{
    public static function enabled(): bool
    {
        return app()->environment(['local', 'testing'])
            && config('dev.local_auth')
            && blank(config('central.client_id'));
    }

    public static function bootstrap(Request $request, WorkspaceAccessService $workspaceAccess): void
    {
        $request->session()->put('central_user', [
            'sub' => 'local-dev-user-1',
            'name' => 'Local Dev User',
            'email' => 'dev@network-desk.test',
        ]);

        $request->session()->put('central_workspace', [
            'id' => 'local-dev-workspace',
            'name' => 'Local Dev Workspace',
            'slug' => 'local-dev',
            'domain' => 'local-dev.localhost',
            'is_owner' => true,
            'role' => 'owner',
            'permission' => 'edit',
            'max_activities' => 10000,
            'max_storage_mb' => 100,
            'usage_activities' => 0,
            'usage_storage_mb' => 0.0,
            'remaining_activities' => 10000,
            'remaining_storage_mb' => 100.0,
        ]);

        $workspaceAccess->syncMember($request);
    }
}
