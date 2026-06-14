<?php

namespace App\Services;

use App\Models\NetworkPoint;
use App\Models\NetworkPointPort;
use Illuminate\Support\Facades\DB;

class PointPortSyncService
{
    /**
     * @param  array<int, array<string, mixed>>|null  $portsInput
     */
    public function sync(NetworkPoint $point, ?array $portsInput): void
    {
        if ($portsInput === null) {
            return;
        }

        DB::transaction(function () use ($point, $portsInput): void {
            $keptIds = [];
            $sortOrder = 0;

            foreach ($portsInput as $input) {
                $label = trim((string) ($input['label'] ?? ''));

                if ($label === '') {
                    continue;
                }

                $direction = $input['direction'] ?? null;
                abort_unless(in_array($direction, ['input', 'output'], true), 422, 'Port direction must be input or output.');

                /** @var NetworkPointPort|null $port */
                $port = null;

                if (! empty($input['id'])) {
                    $port = NetworkPointPort::query()
                        ->where('network_point_id', $point->id)
                        ->whereKey($input['id'])
                        ->first();
                }

                if (! $port) {
                    $port = new NetworkPointPort(['network_point_id' => $point->id]);
                }

                $port->fill([
                    'label' => $label,
                    'direction' => $direction,
                    'sort_order' => $sortOrder,
                ]);
                $port->save();

                $keptIds[] = $port->id;
                $sortOrder += 1;
            }

            NetworkPointPort::query()
                ->where('network_point_id', $point->id)
                ->whereNotIn('id', $keptIds)
                ->delete();

            $point->update(['port_count' => count($keptIds) ?: null]);
        });
    }
}
