<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class QuotaHealthCheckCommand extends Command
{
    protected $signature = 'quota:health-check {workspace_id? : Workspace UUID to test usage reporting against}';

    protected $description = 'Check workspace quota reporting configuration and central usage API connectivity';

    public function handle(): int
    {
        $this->info('Workspace quota health check');
        $this->newLine();

        $hasError = false;
        $centralUrl = rtrim((string) config('central.url'), '/');
        $clientId = (string) config('central.client_id');
        $clientSecret = (string) config('central.client_secret');

        foreach ([
            'CENTRAL_URL' => $centralUrl,
            'CLIENT_ID' => $clientId,
            'CLIENT_SECRET' => $clientSecret,
        ] as $name => $value) {
            if ($value === '') {
                $this->error("  {$name}: missing — usage cannot be reported to central");
                $hasError = true;
            } else {
                $display = $name === 'CLIENT_SECRET' ? str_repeat('*', 8) : $value;
                $this->info("  {$name}: {$display}");
            }
        }

        $this->newLine();
        $this->line('Local quota tables:');

        if (! Schema::hasTable('workspace_request_counts')) {
            $this->error('  workspace_request_counts: missing — run php artisan migrate --force');
            $hasError = true;
        } else {
            $this->info('  workspace_request_counts: present');
        }

        if ($centralUrl === '' || $clientId === '' || $clientSecret === '') {
            $this->newLine();
            $this->error('Fix missing env vars, then run php artisan config:clear');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Central usage API:');

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post("{$centralUrl}/api/extensions/workspaces/test-workspace/usage", [
                    'client_id' => $clientId,
                    'activities_count' => 0,
                    'storage_bytes' => 0,
                    'signature' => 'invalid-signature-for-health-check',
                ]);

            if ($response->status() === 404) {
                $this->error('  Usage API returned 404 — central route/domain mismatch. Check CENTRAL_URL matches central APP_URL host.');
                $hasError = true;
            } elseif (in_array($response->status(), [403, 422], true)) {
                $this->info('  Usage API reachable (HTTP '.$response->status().') — central is responding');
                $message = $response->json('message');
                if (is_string($message) && $message !== '') {
                    $this->comment('  Response: '.$message);
                }
            } else {
                $this->warn('  Unexpected HTTP '.$response->status());
            }
        } catch (\Throwable $exception) {
            $this->error('  Usage API request failed: '.$exception->getMessage());
            $hasError = true;
        }

        $workspaceId = $this->argument('workspace_id');

        if (is_string($workspaceId) && $workspaceId !== '') {
            $this->newLine();
            $this->line("Signed test report for workspace {$workspaceId}:");

            $signature = hash_hmac('sha256', "{$workspaceId}:0:0", $clientSecret);

            try {
                $response = Http::timeout(10)
                    ->acceptJson()
                    ->post("{$centralUrl}/api/extensions/workspaces/{$workspaceId}/usage", [
                        'client_id' => $clientId,
                        'activities_count' => 0,
                        'storage_bytes' => 0,
                        'signature' => $signature,
                    ]);

                if ($response->successful()) {
                    $this->info('  Signed usage report: OK');
                } else {
                    $this->error('  Signed usage report failed (HTTP '.$response->status().')');
                    $message = $response->json('message') ?? $response->body();
                    $this->line('  '.$message);
                    $hasError = true;
                }
            } catch (\Throwable $exception) {
                $this->error('  Signed usage report failed: '.$exception->getMessage());
                $hasError = true;
            }
        }

        $this->newLine();

        if ($hasError) {
            $this->error('Quota reporting is not healthy. Check extension logs after using the app.');

            return self::FAILURE;
        }

        $this->info('Quota reporting configuration looks ready.');
        $this->comment('Tip: pass a workspace UUID to test a real signed report: php artisan quota:health-check {workspace-id}');

        return self::SUCCESS;
    }
}
