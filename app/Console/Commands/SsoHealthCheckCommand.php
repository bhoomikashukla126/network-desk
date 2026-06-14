<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SsoHealthCheckCommand extends Command
{
    protected $signature = 'sso:health-check';

    protected $description = 'Check SSO configuration and connectivity to central';

    public function handle(): int
    {
        $this->info('Complaint Desk SSO health check');
        $this->newLine();

        $checks = [
            'APP_URL' => config('app.url'),
            'CENTRAL_URL' => config('central.url'),
            'CLIENT_ID' => config('central.client_id'),
            'CLIENT_SECRET' => config('central.client_secret'),
            'REDIRECT_URI' => config('central.redirect_uri'),
        ];

        $hasError = false;

        foreach ($checks as $name => $value) {
            if (blank($value)) {
                $this->error("  {$name}: missing");
                $hasError = true;
            } else {
                $display = $name === 'CLIENT_SECRET' ? str_repeat('*', 8) : $value;
                $this->info("  {$name}: {$display}");
            }
        }

        if (str_starts_with((string) config('app.url'), 'https://') && ! config('session.secure')) {
            $this->warn('  SESSION_SECURE_COOKIE: not enabled — set true for HTTPS production');
        }

        $this->newLine();
        $this->line('Database / sessions:');

        try {
            if (! Schema::hasTable('sessions')) {
                $this->error('  sessions table: missing — run php artisan migrate --force');
                $hasError = true;
            } else {
                $this->info('  sessions table: present');
            }
        } catch (\Throwable $exception) {
            $this->error('  Database check failed: '.$exception->getMessage());
            $hasError = true;
        }

        $centralUrl = rtrim((string) config('central.url'), '/');

        $this->newLine();
        $this->line('Central connectivity:');

        if ($centralUrl === '') {
            $this->warn('Skipped — CENTRAL_URL is empty.');
        } else {
            try {
                $response = Http::timeout(10)->acceptJson()->get($centralUrl.'/.well-known/openid-configuration');

                if ($response->successful()) {
                    $this->info('  Central discovery: OK (HTTP '.$response->status().')');
                } else {
                    $this->error('  Central discovery failed (HTTP '.$response->status().')');
                    $hasError = true;
                }
            } catch (\Throwable $exception) {
                $this->error('  Central discovery failed: '.$exception->getMessage());
                $hasError = true;
            }

            try {
                $response = Http::timeout(10)
                    ->acceptJson()
                    ->asForm()
                    ->post($centralUrl.'/oauth/token', [
                        'grant_type' => 'authorization_code',
                        'client_id' => 'health-check-invalid-client',
                        'client_secret' => 'health-check-invalid-secret',
                        'redirect_uri' => (string) config('central.redirect_uri'),
                        'code' => 'health-check-invalid-code',
                    ]);

                if ($response->status() === 500) {
                    $this->error('  Central token endpoint: HTTP 500 — fix central Passport keys first');
                    $hasError = true;
                } elseif ($response->status() >= 400 && $response->status() < 500) {
                    $this->info('  Central token endpoint: HTTP '.$response->status().' (central is responding correctly)');
                } else {
                    $this->warn('  Central token endpoint: unexpected HTTP '.$response->status());
                }
            } catch (\Throwable $exception) {
                $this->error('  Central token endpoint failed: '.$exception->getMessage());
                $hasError = true;
            }
        }

        $this->newLine();

        if ($hasError) {
            $this->error('SSO is not ready. Fix the errors above, then run php artisan config:clear');

            return self::FAILURE;
        }

        $this->info('SSO configuration looks ready.');
        $this->comment('Launch from central: workspace → Open app on Complaint Desk.');

        return self::SUCCESS;
    }
}
