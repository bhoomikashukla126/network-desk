<?php

namespace App\Services;

use App\Support\WorkspaceLocale;
use App\Support\WorkspaceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WorkspaceQuotaService
{
    /**
     * @param  array<string, mixed>  $workspace
     */
    public function quotasEnabled(array $workspace): bool
    {
        return array_key_exists('max_activities', $workspace)
            && $workspace['max_activities'] !== null;
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    public function requestLimitReached(array $workspace): bool
    {
        if (! $this->quotasEnabled($workspace)) {
            return false;
        }

        return (int) ($workspace['remaining_activities'] ?? 0) <= 0;
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    public function storageLimitReached(array $workspace): bool
    {
        if (! array_key_exists('max_storage_mb', $workspace) || $workspace['max_storage_mb'] === null) {
            return false;
        }

        return (float) ($workspace['remaining_storage_mb'] ?? 0) <= 0;
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    public function blockReasonForRequest(array $workspace, Request $request): ?string
    {
        WorkspaceLocale::apply($workspace['language'] ?? null);

        if ($this->requestLimitReached($workspace)) {
            return __('quota.api_request_limit');
        }

        if ($request->isMethodSafe() === false && $this->storageLimitReached($workspace)) {
            return __('quota.storage_limit');
        }

        return null;
    }

    public function incrementRequestCount(string $workspaceId): int
    {
        if (! $this->tableExists('workspace_request_counts')) {
            return 0;
        }

        $existing = DB::table('workspace_request_counts')
            ->where('workspace_id', $workspaceId)
            ->first();

        if ($existing) {
            $count = (int) $existing->request_count + 1;
            DB::table('workspace_request_counts')
                ->where('workspace_id', $workspaceId)
                ->update([
                    'request_count' => $count,
                    'updated_at' => now(),
                ]);

            return $count;
        }

        DB::table('workspace_request_counts')->insert([
            'workspace_id' => $workspaceId,
            'request_count' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return 1;
    }

    public function requestCount(string $workspaceId): int
    {
        return (int) (DB::table('workspace_request_counts')
            ->where('workspace_id', $workspaceId)
            ->value('request_count') ?? 0);
    }

    public function measureStorageBytes(string $workspaceId): int
    {
        $bytes = 0;

        foreach (['network_points', 'cable_segments', 'point_images', 'workspace_members', 'roles'] as $table) {
            $bytes += $this->measureTableStorageBytes($table, $workspaceId);
        }

        return $bytes;
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    public function recordRequest(Request $request, string $workspaceId, array $workspace): void
    {
        try {
            $requestCount = $this->incrementRequestCount($workspaceId);
            $storageBytes = $this->measureStorageBytes($workspaceId);

            $this->decrementSessionQuotas($request, $workspace);
            $this->reportToCentral($request, $workspaceId, $requestCount, $storageBytes);
        } catch (\Throwable $exception) {
            Log::warning('Workspace quota tracking failed', [
                'workspace_id' => $workspaceId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function decrementSessionQuotas(Request $request, array $workspace): void
    {
        if (! $this->quotasEnabled($workspace)) {
            return;
        }

        $workspace['usage_activities'] = (int) ($workspace['usage_activities'] ?? 0) + 1;
        $workspace['remaining_activities'] = max(0, (int) ($workspace['remaining_activities'] ?? 0) - 1);

        $request->session()->put('central_workspace', $workspace);
    }

    private function reportToCentral(Request $request, string $workspaceId, int $requestCount, int $storageBytes): void
    {
        $clientId = (string) config('central.client_id');
        $clientSecret = (string) config('central.client_secret');
        $centralUrl = rtrim((string) config('central.url'), '/');

        if ($clientId === '' || $clientSecret === '' || $centralUrl === '') {
            return;
        }

        $signature = hash_hmac(
            'sha256',
            "{$workspaceId}:{$requestCount}:{$storageBytes}",
            $clientSecret,
        );

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post("{$centralUrl}/api/extensions/workspaces/{$workspaceId}/usage", [
                    'client_id' => $clientId,
                    'activities_count' => $requestCount,
                    'storage_bytes' => $storageBytes,
                    'signature' => $signature,
                ]);

            if ($response->successful()) {
                $quotas = $response->json('data.quotas');

                if (is_array($quotas)) {
                    $this->updateSessionQuotas($request, $quotas);
                }

                return;
            }

            Log::warning('Central rejected workspace usage report', [
                'workspace_id' => $workspaceId,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'url' => "{$centralUrl}/api/extensions/workspaces/{$workspaceId}/usage",
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to report workspace usage to central', [
                'workspace_id' => $workspaceId,
                'message' => $exception->getMessage(),
                'url' => "{$centralUrl}/api/extensions/workspaces/{$workspaceId}/usage",
            ]);
        }
    }

    /**
     * @param  array<string, int|float>  $quotas
     */
    public function updateSessionQuotas(Request $request, array $quotas): void
    {
        $workspace = WorkspaceSession::get($request) ?? [];

        $request->session()->put('central_workspace', array_merge($workspace, [
            'max_activities' => (int) ($quotas['max_activities'] ?? $workspace['max_activities'] ?? 0),
            'max_storage_mb' => (int) ($quotas['max_storage_mb'] ?? $workspace['max_storage_mb'] ?? 0),
            'max_storage_bytes' => (int) ($quotas['max_storage_bytes'] ?? $workspace['max_storage_bytes'] ?? 0),
            'usage_activities' => (int) ($quotas['usage_activities'] ?? $workspace['usage_activities'] ?? 0),
            'usage_storage_mb' => (float) ($quotas['usage_storage_mb'] ?? $workspace['usage_storage_mb'] ?? 0),
            'usage_storage_bytes' => (int) ($quotas['usage_storage_bytes'] ?? $workspace['usage_storage_bytes'] ?? 0),
            'remaining_activities' => (int) ($quotas['remaining_activities'] ?? $workspace['remaining_activities'] ?? 0),
            'remaining_storage_mb' => (float) ($quotas['remaining_storage_mb'] ?? $workspace['remaining_storage_mb'] ?? 0),
            'remaining_storage_bytes' => (int) ($quotas['remaining_storage_bytes'] ?? $workspace['remaining_storage_bytes'] ?? 0),
        ]));
    }

    protected function measureTableStorageBytes(string $table, string $workspaceId): int
    {
        if (! $this->tableHasWorkspaceScope($table)) {
            return 0;
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            $result = DB::selectOne(
                "SELECT COALESCE(SUM(pg_column_size(t.*)), 0) AS bytes FROM {$table} t WHERE workspace_id = ?",
                [$workspaceId],
            );

            return (int) ($result->bytes ?? 0);
        }

        return $this->estimateTableStorageBytes($table, $workspaceId);
    }

    protected function estimateTableStorageBytes(string $table, string $workspaceId): int
    {
        $rows = DB::table($table)->where('workspace_id', $workspaceId)->get();
        $bytes = 0;

        foreach ($rows as $row) {
            $bytes += 128;

            foreach ((array) $row as $value) {
                if (is_string($value)) {
                    $bytes += strlen($value);
                }
            }
        }

        return $bytes;
    }

    protected function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    protected function tableHasWorkspaceScope(string $table): bool
    {
        return $this->tableExists($table) && Schema::hasColumn($table, 'workspace_id');
    }

    /**
     * @return array<string, int|float>
     */
    public function refreshUsage(Request $request): array
    {
        $workspaceId = WorkspaceSession::id($request);
        $requestCount = $this->requestCount($workspaceId);
        $storageBytes = $this->measureStorageBytes($workspaceId);

        $this->reportToCentral($request, $workspaceId, $requestCount, $storageBytes);

        return WorkspaceSession::quotas($request);
    }
}
