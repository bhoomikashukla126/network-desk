<?php

namespace App\Services;

use App\Models\NetworkPoint;
use App\Models\NetworkPointDevice;
use App\Models\NetworkPointPort;
use App\Support\NetworkCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PointDeviceSyncService
{
    /**
     * @param  array<int, array<string, mixed>>|null  $devicesInput
     */
    public function sync(NetworkPoint $point, ?array $devicesInput): void
    {
        if ($devicesInput === null) {
            return;
        }

        $allowedTypes = array_keys(NetworkCatalog::pointTypes());

        DB::transaction(function () use ($point, $devicesInput, $allowedTypes): void {
            $keptDeviceIds = [];
            $deviceSortOrder = 0;
            $totalPorts = 0;

            foreach ($devicesInput as $deviceInput) {
                $deviceLabel = trim((string) ($deviceInput['label'] ?? ''));

                if ($deviceLabel === '') {
                    continue;
                }

                $deviceType = $deviceInput['type'] ?? null;
                abort_unless(in_array($deviceType, $allowedTypes, true), 422, 'Device type is invalid.');

                /** @var NetworkPointDevice|null $device */
                $device = null;

                if (! empty($deviceInput['id'])) {
                    $device = NetworkPointDevice::query()
                        ->where('network_point_id', $point->id)
                        ->whereKey($deviceInput['id'])
                        ->first();
                }

                if (! $device) {
                    $device = new NetworkPointDevice(['network_point_id' => $point->id]);
                }

                $device->fill([
                    'label' => $deviceLabel,
                    'type' => $deviceType,
                    'sort_order' => $deviceSortOrder,
                ]);
                $device->save();

                $keptDeviceIds[] = $device->id;
                $deviceSortOrder += 1;

                $keptPortIds = [];
                $portSortOrder = 0;

                foreach ($deviceInput['ports'] ?? [] as $portInput) {
                    $portLabel = trim((string) ($portInput['label'] ?? ''));

                    if ($portLabel === '') {
                        continue;
                    }

                    $direction = $portInput['direction'] ?? null;
                    abort_unless(in_array($direction, ['input', 'output'], true), 422, 'Port direction must be input or output.');

                    /** @var NetworkPointPort|null $port */
                    $port = null;

                    if (! empty($portInput['id'])) {
                        $port = NetworkPointPort::query()
                            ->where('network_point_id', $point->id)
                            ->where('network_point_device_id', $device->id)
                            ->whereKey($portInput['id'])
                            ->first();
                    }

                    if (! $port) {
                        $port = new NetworkPointPort([
                            'network_point_id' => $point->id,
                            'network_point_device_id' => $device->id,
                        ]);
                    }

                    $port->fill([
                        'label' => $portLabel,
                        'direction' => $direction,
                        'sort_order' => $portSortOrder,
                        'network_point_device_id' => $device->id,
                    ]);
                    $port->save();

                    $keptPortIds[] = $port->id;
                    $portSortOrder += 1;
                    $totalPorts += 1;
                }

                NetworkPointPort::query()
                    ->where('network_point_device_id', $device->id)
                    ->whereNotIn('id', $keptPortIds)
                    ->delete();
            }

            NetworkPointDevice::query()
                ->where('network_point_id', $point->id)
                ->whereNotIn('id', $keptDeviceIds)
                ->delete();

            NetworkPointPort::query()
                ->where('network_point_id', $point->id)
                ->whereNull('network_point_device_id')
                ->delete();

            $point->update(['port_count' => $totalPorts ?: null]);
        });
    }

    /**
     * @return array<int, string>
     */
    public static function deviceTypeRules(): array
    {
        return [Rule::in(array_keys(NetworkCatalog::pointTypes()))];
    }
}
