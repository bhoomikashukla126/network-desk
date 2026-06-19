<?php

namespace App\Console\Commands;

use Database\Seeders\NetworkMapMiniSeeder;
use Illuminate\Console\Command;

class SeedNetworkMiniCommand extends Command
{
    protected $signature = 'network:seed-mini {--workspace-id= : Central workspace UUID to seed}';

    protected $description = 'Seed a small Ji One test FTTH map (1 OLT, 2 splitters, 10 customers)';

    public function handle(): int
    {
        $workspaceId = (string) ($this->option('workspace-id') ?: '');

        if ($workspaceId === '') {
            $this->error('Pass --workspace-id=<uuid> from central (workspace "ji one").');

            return self::FAILURE;
        }

        NetworkMapMiniSeeder::$workspaceId = $workspaceId;

        $this->call('db:seed', [
            '--class' => NetworkMapMiniSeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
