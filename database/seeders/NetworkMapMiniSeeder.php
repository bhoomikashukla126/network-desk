<?php

namespace Database\Seeders;

use App\Models\NetworkPoint;
use Illuminate\Support\Facades\DB;

class NetworkMapMiniSeeder extends NetworkMapDemoSeeder
{
    public const PREFIX = 'Ji ';

    public const CUSTOMER_COUNT = 10;

    public const SPLITTER_COUNT = 2;

    public const CUSTOMERS_PER_SPLITTER = 5;

    public static ?string $workspaceId = null;

    public function run(): void
    {
        $workspaceId = self::$workspaceId ?? NetworkPoint::query()->value('workspace_id');

        if (! $workspaceId) {
            $this->command?->error('No workspace ID. Pass --workspace-id= or create a workspace in central first.');

            return;
        }

        $centroid = NetworkPoint::query()
            ->selectRaw('AVG(latitude) as lat, AVG(longitude) as lng')
            ->where('workspace_id', $workspaceId)
            ->where('name', 'not like', self::PREFIX.'%')
            ->where('name', 'not like', 'Demo %')
            ->first();

        $centerLat = (float) ($centroid->lat ?? 22.069);
        $centerLng = (float) ($centroid->lng ?? 78.942);

        DB::transaction(function () use ($workspaceId, $centerLat, $centerLng): void {
            $this->coreEndIndex = [];
            $this->clearPreviousMini($workspaceId);

            $stats = [
                'points' => 0,
                'cables' => 0,
                'cores' => 0,
                'splices' => 0,
                'customers' => 0,
            ];

            $area = 'Ji One FTTH';

            $olt = $this->createPoint(
                $workspaceId,
                self::PREFIX.'OLT 1',
                ['router'],
                $centerLat,
                $centerLng,
                $area,
                'Test OLT with 2 PON ports feeding 2 splitters and 10 customers.',
            );
            $stats['points']++;

            $oltDevice = $this->createDevice($olt, 'OLT 1', 'router');
            $ponPorts = [];

            for ($pon = 1; $pon <= self::SPLITTER_COUNT; $pon += 1) {
                $ponPorts[$pon] = $this->createPort($olt, $oltDevice, "PON {$pon}", 'output', $pon);
            }

            $customerSerial = 0;
            $splitterBearings = [45.0, 165.0];

            for ($splitterNo = 1; $splitterNo <= self::SPLITTER_COUNT; $splitterNo += 1) {
                $bearing = $splitterBearings[$splitterNo - 1];

                [$feederJLat, $feederJLng] = $this->offsetMeters($centerLat, $centerLng, $bearing, 480);

                $feederJunction = $this->createPoint(
                    $workspaceId,
                    self::PREFIX."Feeder Junction {$splitterNo}",
                    ['junction'],
                    $feederJLat,
                    $feederJLng,
                    $area,
                    'Distribution splice between OLT PON and splitter.',
                );
                $stats['points']++;

                $ponTrunk = $this->createCable(
                    $workspaceId,
                    $olt,
                    $feederJunction,
                    self::PREFIX."Distribution 16C: OLT PON{$splitterNo} → Feeder J{$splitterNo}",
                    16,
                    'fiber',
                );
                $stats['cables']++;

                $ponTrunkCore = $this->createCoreWithEnds(
                    $ponTrunk,
                    1,
                    $olt,
                    $ponPorts[$splitterNo],
                    $feederJunction,
                    null,
                    "PON {$splitterNo} distribution",
                );
                $stats['cores'] += 1;

                for ($spare = 2; $spare <= 16; $spare += 1) {
                    $this->createCoreWithEnds($ponTrunk, $spare, $feederJunction, null, $feederJunction, null, "Spare {$spare}");
                    $stats['cores'] += 1;
                }

                [$splitterLat, $splitterLng] = $this->offsetMeters($feederJLat, $feederJLng, $bearing, 220);

                $splitter = $this->createPoint(
                    $workspaceId,
                    self::PREFIX."Splitter {$splitterNo}",
                    ['splitter'],
                    $splitterLat,
                    $splitterLng,
                    $area,
                    '1:8 passive splitter for Ji One test subscribers.',
                );
                $stats['points']++;

                $splitterDevice = $this->createDevice($splitter, "Splitter {$splitterNo}", 'splitter');
                $splitterIn = $this->createPort($splitter, $splitterDevice, 'IN', 'input', 0);
                $splitterOutPorts = [];

                for ($out = 1; $out <= self::CUSTOMERS_PER_SPLITTER; $out += 1) {
                    $splitterOutPorts[$out] = $this->createPort($splitter, $splitterDevice, "OUT {$out}", 'output', $out);
                }

                $accessCable = $this->createCable(
                    $workspaceId,
                    $feederJunction,
                    $splitter,
                    self::PREFIX."Access 8C: Feeder J{$splitterNo} → Splitter {$splitterNo}",
                    8,
                    'fiber',
                );
                $stats['cables']++;

                $accessCore = $this->createCoreWithEnds(
                    $accessCable,
                    1,
                    $feederJunction,
                    null,
                    $splitter,
                    $splitterIn,
                    'Splitter feed',
                );
                $stats['cores'] += 1;

                $this->spliceCoreEnds($ponTrunkCore['end'], $accessCore['start']);
                $stats['splices'] += 1;

                for ($spare = 2; $spare <= 8; $spare += 1) {
                    $this->createCoreWithEnds($accessCable, $spare, $feederJunction, null, $splitter, null, "Spare {$spare}");
                    $stats['cores'] += 1;
                }

                for ($out = 1; $out <= self::CUSTOMERS_PER_SPLITTER; $out += 1) {
                    $customerSerial += 1;

                    $houseOffsetM = (($out - 1) * 28) + 40;
                    [$custLat, $custLng] = $this->offsetMeters($splitterLat, $splitterLng, $bearing, $houseOffsetM);

                    $customer = $this->createPoint(
                        $workspaceId,
                        self::PREFIX.sprintf('Customer %02d', $customerSerial),
                        ['customer'],
                        $custLat,
                        $custLng,
                        $area,
                        sprintf(
                            'Test subscriber #%02d on Splitter %d (PON %d).',
                            $customerSerial,
                            $splitterNo,
                            $splitterNo,
                        ),
                        sprintf('Subscriber %02d', $customerSerial),
                        $this->fakePhone($customerSerial),
                    );
                    $stats['points']++;
                    $stats['customers']++;

                    $ontDevice = $this->createDevice($customer, 'ONT', 'customer');
                    $ontIn = $this->createPort($customer, $ontDevice, 'ONT', 'input', 0);
                    $this->createPort($customer, $ontDevice, 'LAN', 'output', 1);

                    $dropCable = $this->createCable(
                        $workspaceId,
                        $splitter,
                        $customer,
                        self::PREFIX.sprintf('Drop 2C: Splitter %d → C%02d', $splitterNo, $customerSerial),
                        2,
                        'fiber',
                    );
                    $stats['cables']++;

                    $this->createCoreWithEnds(
                        $dropCable,
                        1,
                        $splitter,
                        $splitterOutPorts[$out],
                        $customer,
                        $ontIn,
                        'Service fiber',
                    );
                    $stats['cores'] += 1;

                    $this->createCoreWithEnds(
                        $dropCable,
                        2,
                        $splitter,
                        null,
                        $customer,
                        null,
                        'Spare',
                    );
                    $stats['cores'] += 1;
                }
            }

            $this->command?->info(sprintf(
                'Ji One test network (workspace %s): %d customers, 1 OLT, %d splitters, %d points, %d cables, %d cores, %d splices near %.5f, %.5f.',
                $workspaceId,
                $stats['customers'],
                self::SPLITTER_COUNT,
                $stats['points'],
                $stats['cables'],
                $stats['cores'],
                $stats['splices'],
                $centerLat,
                $centerLng,
            ));
        });
    }

    protected function clearPreviousMini(string $workspaceId): void
    {
        $pointIds = NetworkPoint::query()
            ->where('workspace_id', $workspaceId)
            ->where('name', 'like', self::PREFIX.'%')
            ->pluck('id');

        \App\Models\CableSegment::query()
            ->where('workspace_id', $workspaceId)
            ->where('name', 'like', self::PREFIX.'%')
            ->delete();

        if ($pointIds->isNotEmpty()) {
            \App\Models\CableSegment::query()
                ->where('workspace_id', $workspaceId)
                ->where(function ($query) use ($pointIds): void {
                    $query->whereIn('from_point_id', $pointIds)
                        ->orWhereIn('to_point_id', $pointIds);
                })
                ->delete();

            NetworkPoint::query()->whereIn('id', $pointIds)->delete();
        }
    }
}
