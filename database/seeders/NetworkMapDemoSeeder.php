<?php

namespace Database\Seeders;

use App\Models\CableCore;
use App\Models\CableCoreEnd;
use App\Models\CableSegment;
use App\Models\NetworkPoint;
use App\Models\NetworkPointDevice;
use App\Models\NetworkPointPort;
use App\Support\DeviceConnectionLabel;
use App\Support\FiberCoreColors;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NetworkMapDemoSeeder extends Seeder
{
    private const DEMO_PREFIX = 'Demo ';

    private const CUSTOMER_COUNT = 600;

    /** Even split across six OLTs (100 subscribers each). */
    private const CUSTOMERS_PER_OLT = 100;

    /** OLT 1 sits at the control room; OLTs 2–6 are deployed across the city. */
    private const OLT_COUNT = 6;

    private const PON_PER_OLT = 8;

    private const CUSTOMERS_PER_PON = 16;

    /** Minimum utility poles placed on the demo map. */
    private const MIN_POLES = 42;

    /** Distance from splitter to the colony street grid center (meters). */
    private const COLONY_DISTANCE_M = 320;

    /** Spacing between parallel streets inside a colony (meters). */
    private const COLONY_STREET_SPACING_M = 30;

    /** Spacing between houses on the same street (meters). */
    private const COLONY_HOUSE_SPACING_M = 24;

    /** Streets and houses per colony grid (4 × 4 = 16 homes). */
    private const STREETS_PER_COLONY = 4;

    private const HOUSES_PER_STREET = 4;

    /** Distance from OLT to feeder junction in meters. */
    private const FEEDER_JUNCTION_DISTANCE_M = 480;

    /** Distance from feeder junction to splitter in meters. */
    private const SPLITTER_DISTANCE_M = 220;

    /** @var array<int, CableCoreEnd> */
    private array $coreEndIndex = [];

    /** @var int */
    private int $poleSerial = 0;

    public function run(): void
    {
        $workspaceId = NetworkPoint::query()->value('workspace_id');

        if (! $workspaceId) {
            $this->command?->error('No workspace found. Add at least one network point first.');

            return;
        }

        $centroid = NetworkPoint::query()
            ->selectRaw('AVG(latitude) as lat, AVG(longitude) as lng')
            ->where('workspace_id', $workspaceId)
            ->where('name', 'not like', self::DEMO_PREFIX.'%')
            ->first();

        if (! $centroid?->lat) {
            $centroid = NetworkPoint::query()
                ->selectRaw('AVG(latitude) as lat, AVG(longitude) as lng')
                ->where('workspace_id', $workspaceId)
                ->first();
        }

        $centerLat = (float) ($centroid->lat ?? 22.069);
        $centerLng = (float) ($centroid->lng ?? 78.942);

        DB::transaction(function () use ($workspaceId, $centerLat, $centerLng): void {
            $this->coreEndIndex = [];
            $this->clearPreviousDemo($workspaceId);

            $stats = [
                'points' => 0,
                'cables' => 0,
                'cores' => 0,
                'splices' => 0,
                'customers' => 0,
                'poles' => 0,
            ];

            $this->poleSerial = 0;

            $headEnd = $this->buildHeadEndChain($workspaceId, $stats, $centerLat, $centerLng);
            $odf = $headEnd['odf'];
            $nocUplinkPorts = $headEnd['backbonePorts'];
            $coreSwitch = $headEnd['switch'];

            $cityOltAngles = [30, 90, 150, 210, 270];
            $cityAreas = ['Sector 12', 'Sector 14', 'Ward 3', 'Green Valley', 'Industrial Zone'];
            $customerSerial = 0;

            // OLT 1 — co-located at the control room (fiber from ODF + ethernet from switch).
            $olt1Bearing = 90.0;
            [$olt1Lat, $olt1Lng] = $this->offsetMeters($centerLat, $centerLng, $olt1Bearing, 130);

            $olt1 = $this->createPoint(
                $workspaceId,
                self::DEMO_PREFIX.'OLT 1 (Control Room)',
                ['router'],
                $olt1Lat,
                $olt1Lng,
                'Central NOC',
                'Head-end OLT at the control room. Fiber from ODF MUX; Ethernet from core switch.',
            );
            $stats['points']++;

            $olt1Device = $this->createDevice($olt1, 'OLT 1', 'router');
            $olt1UplinkIn = $this->createPort($olt1, $olt1Device, 'UPLINK IN', 'input', 0);
            $olt1EthIn = $this->createPort($olt1, $olt1Device, 'ETH IN', 'input', 1);
            $olt1PonPorts = $this->createPonPorts($olt1, $olt1Device);

            $this->createEthernetPatch(
                $workspaceId,
                $stats,
                $coreSwitch,
                $headEnd['switchOlt1Port'],
                $olt1,
                $olt1EthIn,
                self::DEMO_PREFIX.'Ethernet: Switch → OLT 1',
            );

            $odfPatchCable = $this->createCable(
                $workspaceId,
                $odf,
                $olt1,
                self::DEMO_PREFIX.'Patch 24C: ODF MUX → OLT 1',
                24,
                'fiber',
            );
            $stats['cables']++;

            $this->createCoreWithEnds(
                $odfPatchCable,
                1,
                $odf,
                $nocUplinkPorts[1],
                $olt1,
                $olt1UplinkIn,
                'OLT 1 fiber uplink',
            );
            $stats['cores'] += 1;

            for ($spare = 2; $spare <= 24; $spare += 1) {
                $this->createCoreWithEnds($odfPatchCable, $spare, $odf, null, $olt1, null, "Spare {$spare}");
                $stats['cores'] += 1;
            }

            $customerSerial = $this->buildPonDistribution(
                $workspaceId,
                $stats,
                $olt1,
                1,
                $olt1Lat,
                $olt1Lng,
                $olt1Bearing,
                $olt1PonPorts,
                'Central NOC',
                $customerSerial,
            );

            // OLTs 2–6 — always deployed across the city; customers stop at CUSTOMER_COUNT.
            $cityOlts = [];

            for ($cityIndex = 0; $cityIndex < count($cityOltAngles); $cityIndex += 1) {
                $oltNo = $cityIndex + 2;
                $area = $cityAreas[$cityIndex];
                $sectorBearing = $cityOltAngles[$cityIndex];
                [$oltLat, $oltLng] = $this->offset($centerLat, $centerLng, $sectorBearing, 0.0055 + ($cityIndex * 0.0003));

                [$regionalLat, $regionalLng] = $this->spread($oltLat, $oltLng, $sectorBearing + 180, 0.0012);

                $regionalJunction = $this->createPoint(
                    $workspaceId,
                    self::DEMO_PREFIX."Backbone Junction {$oltNo}",
                    ['junction'],
                    $regionalLat,
                    $regionalLng,
                    $area,
                    '24-core to 16-core splice node on the main backbone.',
                );
                $stats['points']++;

                $backboneCable = $this->createCable(
                    $workspaceId,
                    $odf,
                    $regionalJunction,
                    self::DEMO_PREFIX."Backbone 24C: ODF → Junction {$oltNo}",
                    24,
                    'fiber',
                    $this->backboneRouteWithPoles($workspaceId, $stats, $odf, $regionalJunction, $sectorBearing, $oltNo),
                );
                $stats['cables']++;

                $backboneCore = $this->createCoreWithEnds(
                    $backboneCable,
                    1,
                    $odf,
                    $nocUplinkPorts[$oltNo],
                    $regionalJunction,
                    null,
                    "Backbone lane {$oltNo}",
                );
                $stats['cores'] += 1;

                for ($spare = 2; $spare <= 24; $spare += 1) {
                    $this->createCoreWithEnds($backboneCable, $spare, $regionalJunction, null, $regionalJunction, null, "Spare {$spare}");
                    $stats['cores'] += 1;
                }

                $olt = $this->createPoint(
                    $workspaceId,
                    self::DEMO_PREFIX."OLT Hub {$oltNo}",
                    ['router'],
                    $oltLat,
                    $oltLng,
                    $area,
                    'City OLT with 8 PON ports. Each PON serves 16 customers via 16C→8C splice and 2C drops.',
                );
                $stats['points']++;

                $oltDevice = $this->createDevice($olt, "OLT {$oltNo}", 'router');
                $oltUplinkIn = $this->createPort($olt, $oltDevice, 'UPLINK IN', 'input', 0);
                $ponPorts = $this->createPonPorts($olt, $oltDevice);

                $feederCable = $this->createCable(
                    $workspaceId,
                    $regionalJunction,
                    $olt,
                    self::DEMO_PREFIX."Feeder 16C: Junction {$oltNo} → OLT {$oltNo}",
                    16,
                    'fiber',
                );
                $stats['cables']++;

                $feederCore = $this->createCoreWithEnds(
                    $feederCable,
                    1,
                    $regionalJunction,
                    null,
                    $olt,
                    $oltUplinkIn,
                    "OLT {$oltNo} feeder",
                );
                $stats['cores'] += 1;

                $this->spliceCoreEnds($backboneCore['end'], $feederCore['start']);
                $stats['splices'] += 1;

                for ($spare = 2; $spare <= 16; $spare += 1) {
                    $this->createCoreWithEnds($feederCable, $spare, $regionalJunction, null, $olt, null, "Spare {$spare}");
                    $stats['cores'] += 1;
                }

                $cityOlts[] = [
                    'olt' => $olt,
                    'oltNo' => $oltNo,
                    'lat' => $oltLat,
                    'lng' => $oltLng,
                    'bearing' => $sectorBearing,
                    'ponPorts' => $ponPorts,
                    'area' => $area,
                ];
            }

            foreach ($cityOlts as $cityOlt) {
                $customerSerial = $this->buildPonDistribution(
                    $workspaceId,
                    $stats,
                    $cityOlt['olt'],
                    $cityOlt['oltNo'],
                    $cityOlt['lat'],
                    $cityOlt['lng'],
                    $cityOlt['bearing'],
                    $cityOlt['ponPorts'],
                    $cityOlt['area'],
                    $customerSerial,
                );
            }

            $this->command?->info(sprintf(
                'Demo FTTH network: %d customers, %d poles, %d points, %d cables, %d cores, %d splices (RailTel -> BRAS -> Switch -> ODF -> 6 OLTs) near %.5f, %.5f.',
                $stats['customers'],
                $stats['poles'],
                $stats['points'],
                $stats['cables'],
                $stats['cores'],
                $stats['splices'],
                $centerLat,
                $centerLng,
            ));
        });
    }

    /**
     * Build RailTel → BRAS → Switch → DWDM → ODF control-room chain.
     *
     * @param  array<string, int>  $stats
     * @return array{
     *     railtel: NetworkPoint,
     *     bras: NetworkPoint,
     *     switch: NetworkPoint,
     *     dwdm: NetworkPoint,
     *     odf: NetworkPoint,
     *     backbonePorts: array<int, NetworkPointPort>,
     *     switchOlt1Port: NetworkPointPort
     * }
     */
    protected function buildHeadEndChain(
        string $workspaceId,
        array &$stats,
        float $centerLat,
        float $centerLng,
    ): array {
        [$railLat, $railLng] = $this->offsetMeters($centerLat, $centerLng, 270, 50);

        $railtel = $this->createPoint(
            $workspaceId,
            self::DEMO_PREFIX.'RailTel Uplink',
            ['uplink'],
            $railLat,
            $railLng,
            'Central NOC',
            'Single ISP handoff from RailTel. Ethernet to BRAS; fiber to DWDM for transport.',
        );
        $stats['points']++;

        $railDevice = $this->createDevice($railtel, 'RailTel CPE', 'uplink');
        $railWanOut = $this->createPort($railtel, $railDevice, 'WAN OUT', 'output', 0);
        $railFiberOut = $this->createPort($railtel, $railDevice, 'FIBER OUT', 'output', 1);

        [$brasLat, $brasLng] = $this->offsetMeters($centerLat, $centerLng, 315, 32);

        $bras = $this->createPoint(
            $workspaceId,
            self::DEMO_PREFIX.'BRAS / Edge Router',
            ['bras'],
            $brasLat,
            $brasLng,
            'Central NOC',
            'PPPoE, routing, and subscriber session aggregation.',
        );
        $stats['points']++;

        $brasDevice = $this->createDevice($bras, 'Edge Router', 'bras');
        $brasWanIn = $this->createPort($bras, $brasDevice, 'WAN IN', 'input', 0);
        $brasLanOut = $this->createPort($bras, $brasDevice, 'LAN OUT', 'output', 1);

        [$switchLat, $switchLng] = $this->offsetMeters($centerLat, $centerLng, 0, 26);

        $switch = $this->createPoint(
            $workspaceId,
            self::DEMO_PREFIX.'Core Switch',
            ['switch'],
            $switchLat,
            $switchLng,
            'Central NOC',
            'L3 aggregation switch. One 10G/1G port per OLT uplink.',
        );
        $stats['points']++;

        $switchDevice = $this->createDevice($switch, 'Core Switch', 'switch');
        $switchUplinkIn = $this->createPort($switch, $switchDevice, 'UPLINK IN', 'input', 0);
        $switchOlt1Port = $this->createPort($switch, $switchDevice, 'OLT-1', 'output', 1);

        for ($oltPort = 2; $oltPort <= self::OLT_COUNT; $oltPort += 1) {
            $this->createPort($switch, $switchDevice, "OLT-{$oltPort}", 'output', $oltPort);
        }

        [$dwdmLat, $dwdmLng] = $this->offsetMeters($centerLat, $centerLng, 180, 38);

        $dwdm = $this->createPoint(
            $workspaceId,
            self::DEMO_PREFIX.'DWDM MUX',
            ['dwdm'],
            $dwdmLat,
            $dwdmLng,
            'Central NOC',
            'Optional wavelength MUX on the RailTel fiber before ODF distribution.',
        );
        $stats['points']++;

        $dwdmDevice = $this->createDevice($dwdm, 'CWDM MUX', 'dwdm');
        $dwdmLineIn = $this->createPort($dwdm, $dwdmDevice, 'LINE IN', 'input', 0);
        $dwdmLineOut = $this->createPort($dwdm, $dwdmDevice, 'LINE OUT', 'output', 0);

        $odf = $this->createPoint(
            $workspaceId,
            self::DEMO_PREFIX.'Control Room ODF MUX',
            ['odf'],
            $centerLat,
            $centerLng,
            'Central NOC',
            'Fiber patch panel. Six backbone lanes to OLTs 1–6.',
        );
        $stats['points']++;

        $odfDevice = $this->createDevice($odf, 'NOC MUX', 'odf');
        $odfUplinkIn = $this->createPort($odf, $odfDevice, 'UPLINK IN', 'input', 0);
        $backbonePorts = [];

        for ($u = 1; $u <= self::OLT_COUNT; $u += 1) {
            $backbonePorts[$u] = $this->createPort($odf, $odfDevice, "BACKBONE-{$u}", 'output', $u);
        }

        $this->createEthernetPatch(
            $workspaceId,
            $stats,
            $railtel,
            $railWanOut,
            $bras,
            $brasWanIn,
            self::DEMO_PREFIX.'Ethernet: RailTel → BRAS',
        );

        $this->createEthernetPatch(
            $workspaceId,
            $stats,
            $bras,
            $brasLanOut,
            $switch,
            $switchUplinkIn,
            self::DEMO_PREFIX.'Ethernet: BRAS → Core Switch',
        );

        $railFiberCable = $this->createCable(
            $workspaceId,
            $railtel,
            $dwdm,
            self::DEMO_PREFIX.'Fiber 2C: RailTel → DWDM',
            2,
            'fiber',
        );
        $stats['cables']++;

        $this->createCoreWithEnds(
            $railFiberCable,
            1,
            $railtel,
            $railFiberOut,
            $dwdm,
            $dwdmLineIn,
            'RailTel transport',
        );
        $stats['cores'] += 1;

        $this->createCoreWithEnds($railFiberCable, 2, $railtel, null, $dwdm, null, 'Spare');
        $stats['cores'] += 1;

        $dwdmOdfCable = $this->createCable(
            $workspaceId,
            $dwdm,
            $odf,
            self::DEMO_PREFIX.'Fiber 24C: DWDM → ODF',
            24,
            'fiber',
        );
        $stats['cables']++;

        $this->createCoreWithEnds(
            $dwdmOdfCable,
            1,
            $dwdm,
            $dwdmLineOut,
            $odf,
            $odfUplinkIn,
            'Transport lane',
        );
        $stats['cores'] += 1;

        for ($spare = 2; $spare <= 24; $spare += 1) {
            $this->createCoreWithEnds($dwdmOdfCable, $spare, $dwdm, null, $odf, null, "Spare {$spare}");
            $stats['cores'] += 1;
        }

        return [
            'railtel' => $railtel,
            'bras' => $bras,
            'switch' => $switch,
            'dwdm' => $dwdm,
            'odf' => $odf,
            'backbonePorts' => $backbonePorts,
            'switchOlt1Port' => $switchOlt1Port,
        ];
    }

    /**
     * @param  array<string, int>  $stats
     */
    protected function createEthernetPatch(
        string $workspaceId,
        array &$stats,
        NetworkPoint $from,
        NetworkPointPort $fromPort,
        NetworkPoint $to,
        NetworkPointPort $toPort,
        string $name,
    ): void {
        $cable = $this->createCable(
            $workspaceId,
            $from,
            $to,
            $name,
            2,
            'ethernet',
        );
        $stats['cables']++;

        $this->createCoreWithEnds(
            $cable,
            1,
            $from,
            $fromPort,
            $to,
            $toPort,
            'Service',
        );
        $stats['cores'] += 1;

        $this->createCoreWithEnds($cable, 2, $from, null, $to, null, 'Spare');
        $stats['cores'] += 1;
    }

    /**
     * @return array<int, NetworkPointPort>
     */
    protected function createPonPorts(NetworkPoint $olt, NetworkPointDevice $device): array
    {
        $ports = [];

        for ($pon = 1; $pon <= self::PON_PER_OLT; $pon += 1) {
            $ports[$pon] = $this->createPort($olt, $device, "PON {$pon}", 'output', $pon);
        }

        return $ports;
    }

    /**
     * @param  array<string, int>  $stats
     * @param  array<int, NetworkPointPort>  $ponPorts
     */
    protected function buildPonDistribution(
        string $workspaceId,
        array &$stats,
        NetworkPoint $olt,
        int $oltNo,
        float $oltLat,
        float $oltLng,
        float $sectorBearing,
        array $ponPorts,
        string $area,
        int $customerSerial,
    ): int {
        $oltCustomerCount = 0;

        for ($pon = 1; $pon <= self::PON_PER_OLT; $pon += 1) {
            if ($oltCustomerCount >= self::CUSTOMERS_PER_OLT || $customerSerial >= self::CUSTOMER_COUNT) {
                break;
            }

            $oltWedge = 360 / self::OLT_COUNT;
            $ponSector = $oltWedge / self::PON_PER_OLT;
            $ponBearing = fmod(
                $sectorBearing - ($oltWedge / 2) + (($pon - 1) * $ponSector) + ($ponSector / 2),
                360,
            );

            [$feederJLat, $feederJLng] = $this->offsetMeters($oltLat, $oltLng, $ponBearing, self::FEEDER_JUNCTION_DISTANCE_M);

            $feederJunction = $this->createPoint(
                $workspaceId,
                self::DEMO_PREFIX."Feeder Junction O{$oltNo}-P{$pon}",
                ['junction'],
                $feederJLat,
                $feederJLng,
                $area,
                '16-core to 8-core distribution splice.',
            );
            $stats['points']++;

            $ponTrunk = $this->createCable(
                $workspaceId,
                $olt,
                $feederJunction,
                self::DEMO_PREFIX."Distribution 16C: OLT{$oltNo} PON{$pon} → Feeder J",
                16,
                'fiber',
            );
            $stats['cables']++;

            $ponTrunkCore = $this->createCoreWithEnds(
                $ponTrunk,
                1,
                $olt,
                $ponPorts[$pon],
                $feederJunction,
                null,
                "PON {$pon} distribution",
            );
            $stats['cores'] += 1;

            for ($spare = 2; $spare <= 16; $spare += 1) {
                $this->createCoreWithEnds($ponTrunk, $spare, $feederJunction, null, $feederJunction, null, "Spare {$spare}");
                $stats['cores'] += 1;
            }

            [$splitterLat, $splitterLng] = $this->offsetMeters($feederJLat, $feederJLng, $ponBearing, self::SPLITTER_DISTANCE_M);

            $splitter = $this->createPoint(
                $workspaceId,
                self::DEMO_PREFIX."Splitter O{$oltNo}-P{$pon}",
                ['splitter'],
                $splitterLat,
                $splitterLng,
                $area,
                '1:16 passive splitter cabinet.',
            );
            $stats['points']++;

            $splitterDevice = $this->createDevice($splitter, "Splitter P{$pon}", 'splitter');
            $splitterIn = $this->createPort($splitter, $splitterDevice, 'IN', 'input', 0);
            $splitterOutPorts = [];

            for ($out = 1; $out <= self::CUSTOMERS_PER_PON; $out += 1) {
                $splitterOutPorts[$out] = $this->createPort($splitter, $splitterDevice, "OUT {$out}", 'output', $out);
            }

            $accessRoute = $this->accessRouteWithPoles(
                $workspaceId,
                $stats,
                $feederJunction,
                $splitter,
                $ponBearing,
                $oltNo,
                $pon,
                $area,
            );

            $accessCable = $this->createCable(
                $workspaceId,
                $feederJunction,
                $splitter,
                self::DEMO_PREFIX."Access 8C: Feeder J → Splitter O{$oltNo}-P{$pon}",
                8,
                'fiber',
                $accessRoute,
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

            $colonyName = $this->colonyName($area, $oltNo, $pon);
            [$colonyCenterLat, $colonyCenterLng] = $this->offsetMeters(
                $splitterLat,
                $splitterLng,
                $ponBearing,
                self::COLONY_DISTANCE_M,
            );

            $gatePole = $this->createPole(
                $workspaceId,
                $stats,
                $colonyCenterLat,
                $colonyCenterLng,
                $colonyName,
                'Colony distribution pole at the street entry.',
            );

            [$trunkLat, $trunkLng] = $this->offsetMeters($splitterLat, $splitterLng, $ponBearing, self::COLONY_DISTANCE_M * 0.55);

            $trunkPole = $this->createPole(
                $workspaceId,
                $stats,
                $trunkLat,
                $trunkLng,
                $colonyName,
                'Aerial trunk pole on the colony feeder.',
            );

            $streetPoles = [];
            $streetSideBearing = fmod($ponBearing + 90, 360);

            for ($street = 0; $street < self::STREETS_PER_COLONY; $street += 1) {
                $streetOffsetM = (($street - (self::STREETS_PER_COLONY - 1) / 2) * self::COLONY_STREET_SPACING_M);
                [$streetLat, $streetLng] = $this->offsetMeters($colonyCenterLat, $colonyCenterLng, $streetSideBearing, $streetOffsetM);

                $streetPoles[$street] = $this->createPole(
                    $workspaceId,
                    $stats,
                    $streetLat,
                    $streetLng,
                    $colonyName,
                    sprintf('Street pole %d — parallel drops, no cross-over.', $street + 1),
                );
            }

            for ($out = 1; $out <= self::CUSTOMERS_PER_PON; $out += 1) {
                if ($oltCustomerCount >= self::CUSTOMERS_PER_OLT || $customerSerial >= self::CUSTOMER_COUNT) {
                    break 2;
                }

                $customerSerial += 1;
                $oltCustomerCount += 1;

                [$custLat, $custLng, $streetIndex, $houseIndex] = $this->colonyCustomerCoordinates(
                    $colonyCenterLat,
                    $colonyCenterLng,
                    $ponBearing,
                    $out - 1,
                );

                $customer = $this->createPoint(
                    $workspaceId,
                    self::DEMO_PREFIX.sprintf('Customer %04d', $customerSerial),
                    ['customer'],
                    $custLat,
                    $custLng,
                    $colonyName,
                    sprintf(
                        'FTTH subscriber #%04d in %s on OLT %d PON %d (street %d, house %d).',
                        $customerSerial,
                        $colonyName,
                        $oltNo,
                        $pon,
                        $streetIndex + 1,
                        $houseIndex + 1,
                    ),
                    sprintf('Subscriber %04d', $customerSerial),
                    $this->fakePhone($customerSerial),
                );
                $stats['points']++;
                $stats['customers']++;

                $ontDevice = $this->createDevice($customer, 'ONT', 'customer');
                $ontIn = $this->createPort($customer, $ontDevice, 'ONT', 'input', 0);
                $this->createPort($customer, $ontDevice, 'LAN', 'output', 1);

                $dropRoute = $this->colonyDropRoute(
                    $splitter,
                    $trunkPole,
                    $gatePole,
                    $streetPoles[$streetIndex],
                    $customer,
                    $ponBearing,
                    $houseIndex,
                );

                $dropCable = $this->createCable(
                    $workspaceId,
                    $splitter,
                    $customer,
                    self::DEMO_PREFIX.sprintf('Drop 2C: %s → C%04d', $colonyName, $customerSerial),
                    2,
                    'fiber',
                    $dropRoute,
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

        return $customerSerial;
    }

    /**
     * @param  array<string, int>  $stats
     * @return list<array<string, mixed>>
     */
    protected function backboneRouteWithPoles(
        string $workspaceId,
        array &$stats,
        NetworkPoint $from,
        NetworkPoint $to,
        float $bearing,
        int $oltNo,
    ): array {
        $route = [
            ['type' => 'point', 'point_id' => $from->id],
        ];

        foreach ([0.35, 0.7] as $fraction) {
            [$lat, $lng] = $this->interpolateMeters($from->latitude, $from->longitude, $to->latitude, $to->longitude, $fraction);
            $pole = $this->createPole(
                $workspaceId,
                $stats,
                $lat,
                $lng,
                "Backbone OLT {$oltNo}",
                'Backbone aerial pole on the ODF feeder route.',
            );
            $route[] = ['type' => 'point', 'point_id' => $pole->id];
        }

        $route[] = ['type' => 'point', 'point_id' => $to->id];

        return $route;
    }

    /**
     * @param  array<string, int>  $stats
     * @return list<array<string, mixed>>
     */
    protected function accessRouteWithPoles(
        string $workspaceId,
        array &$stats,
        NetworkPoint $from,
        NetworkPoint $to,
        float $bearing,
        int $oltNo,
        int $pon,
        string $area,
    ): array {
        $route = [
            ['type' => 'point', 'point_id' => $from->id],
        ];

        [$midLat, $midLng] = $this->interpolateMeters(
            $from->latitude,
            $from->longitude,
            $to->latitude,
            $to->longitude,
            0.5,
        );

        $pole = $this->createPole(
            $workspaceId,
            $stats,
            $midLat,
            $midLng,
            $this->colonyName($area, $oltNo, $pon),
            'Distribution pole on the feeder-to-splitter span.',
        );
        $route[] = ['type' => 'point', 'point_id' => $pole->id];
        $route[] = ['type' => 'point', 'point_id' => $to->id];

        return $route;
    }

    /**
     * L-shaped drop: splitter → trunk → gate → street pole → house.
     * All houses on a street share the same street pole; streets run parallel so drops never cross.
     *
     * @return list<array<string, mixed>>
     */
    protected function colonyDropRoute(
        NetworkPoint $splitter,
        NetworkPoint $trunkPole,
        NetworkPoint $gatePole,
        NetworkPoint $streetPole,
        NetworkPoint $customer,
        float $trunkBearing,
        int $houseIndex,
    ): array {
        $houseOffsetM = (($houseIndex - (self::HOUSES_PER_STREET - 1) / 2) * self::COLONY_HOUSE_SPACING_M);
        [$tapLat, $tapLng] = $this->offsetMeters($streetPole->latitude, $streetPole->longitude, $trunkBearing, $houseOffsetM);

        return [
            ['type' => 'point', 'point_id' => $splitter->id],
            ['type' => 'point', 'point_id' => $trunkPole->id],
            ['type' => 'point', 'point_id' => $gatePole->id],
            ['type' => 'point', 'point_id' => $streetPole->id],
            ['type' => 'bend', 'lat' => round($tapLat, 7), 'lng' => round($tapLng, 7)],
            ['type' => 'point', 'point_id' => $customer->id],
        ];
    }

    /**
     * @param  array<string, int>  $stats
     */
    protected function createPole(
        string $workspaceId,
        array &$stats,
        float $latitude,
        float $longitude,
        string $colonyName,
        string $notes,
    ): NetworkPoint {
        $this->poleSerial += 1;

        $pole = $this->createPoint(
            $workspaceId,
            self::DEMO_PREFIX.sprintf('Pole %03d', $this->poleSerial),
            ['pole'],
            $latitude,
            $longitude,
            $colonyName,
            $notes,
        );
        $stats['points']++;
        $stats['poles']++;

        return $pole;
    }

    protected function colonyName(string $area, int $oltNo, int $ponNo): string
    {
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        return trim(str_replace(self::DEMO_PREFIX, '', $area)).' Colony '.($letters[$ponNo - 1] ?? (string) $ponNo);
    }

    /**
     * 4×4 colony grid: streets run parallel (perpendicular to trunk), houses sit along each street.
     *
     * @return array{0: float, 1: float, 2: int, 3: int}
     */
    protected function colonyCustomerCoordinates(
        float $colonyCenterLat,
        float $colonyCenterLng,
        float $trunkBearing,
        int $houseSlot,
    ): array {
        $streetIndex = intdiv($houseSlot, self::HOUSES_PER_STREET);
        $houseIndex = $houseSlot % self::HOUSES_PER_STREET;

        $streetSideBearing = fmod($trunkBearing + 90, 360);
        $streetOffsetM = (($streetIndex - (self::STREETS_PER_COLONY - 1) / 2) * self::COLONY_STREET_SPACING_M);
        $houseOffsetM = (($houseIndex - (self::HOUSES_PER_STREET - 1) / 2) * self::COLONY_HOUSE_SPACING_M) + 18;

        [$streetLat, $streetLng] = $this->offsetMeters($colonyCenterLat, $colonyCenterLng, $streetSideBearing, $streetOffsetM);
        [$custLat, $custLng] = $this->offsetMeters($streetLat, $streetLng, $trunkBearing, $houseOffsetM);

        return [$custLat, $custLng, $streetIndex, $houseIndex];
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function interpolateMeters(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        float $fraction,
    ): array {
        $bearing = $this->bearing($fromLat, $fromLng, $toLat, $toLng);
        $distance = $this->distanceBetween($fromLat, $fromLng, $toLat, $toLng) * $fraction;

        return $this->offsetMeters($fromLat, $fromLng, $bearing, $distance);
    }

    protected function distanceBetween(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    protected function clearPreviousDemo(string $workspaceId): void
    {
        $demoPointIds = NetworkPoint::query()
            ->where('workspace_id', $workspaceId)
            ->where('name', 'like', self::DEMO_PREFIX.'%')
            ->pluck('id');

        CableSegment::query()
            ->where('workspace_id', $workspaceId)
            ->where('name', 'like', self::DEMO_PREFIX.'%')
            ->delete();

        if ($demoPointIds->isNotEmpty()) {
            CableSegment::query()
                ->where('workspace_id', $workspaceId)
                ->where(function ($query) use ($demoPointIds): void {
                    $query->whereIn('from_point_id', $demoPointIds)
                        ->orWhereIn('to_point_id', $demoPointIds);
                })
                ->delete();

            NetworkPoint::query()->whereIn('id', $demoPointIds)->delete();
        }
    }

    /**
     * @param  list<string>  $types
     */
    protected function createPoint(
        string $workspaceId,
        string $name,
        array $types,
        float $latitude,
        float $longitude,
        string $area,
        ?string $notes = null,
        ?string $contactName = null,
        ?string $contactPhone = null,
    ): NetworkPoint {
        return NetworkPoint::query()->create([
            'workspace_id' => $workspaceId,
            'name' => $name,
            'types' => $types,
            'type' => $types[0],
            'status' => 'active',
            'area' => $area,
            'latitude' => round($latitude, 7),
            'longitude' => round($longitude, 7),
            'address' => "{$area}, Chhindwara region",
            'notes' => $notes,
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone,
            'created_by' => 'Demo Seeder',
        ]);
    }

    protected function bearing(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLng = deg2rad($lng2 - $lng1);

        $y = sin($deltaLng) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($deltaLng);

        return fmod(rad2deg(atan2($y, $x)) + 360, 360);
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function offsetMeters(float $lat, float $lng, float $bearingDeg, float $distanceM): array
    {
        $earthRadius = 6371000;
        $bearingRad = deg2rad($bearingDeg);
        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);
        $angularDistance = $distanceM / $earthRadius;

        $newLatRad = asin(
            sin($latRad) * cos($angularDistance)
            + cos($latRad) * sin($angularDistance) * cos($bearingRad),
        );

        $newLngRad = $lngRad + atan2(
            sin($bearingRad) * sin($angularDistance) * cos($latRad),
            cos($angularDistance) - sin($latRad) * sin($newLatRad),
        );

        return [rad2deg($newLatRad), rad2deg($newLngRad)];
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function spread(float $lat, float $lng, float $angleDeg, float $distanceDeg): array
    {
        return $this->offset($lat, $lng, $angleDeg, $distanceDeg);
    }

    protected function createDevice(NetworkPoint $point, string $label, string $type): NetworkPointDevice
    {
        return NetworkPointDevice::query()->create([
            'network_point_id' => $point->id,
            'label' => $label,
            'type' => $type,
            'sort_order' => 0,
        ]);
    }

    protected function createPort(
        NetworkPoint $point,
        NetworkPointDevice $device,
        string $label,
        string $direction,
        int $sortOrder,
    ): NetworkPointPort {
        return NetworkPointPort::query()->create([
            'network_point_id' => $point->id,
            'network_point_device_id' => $device->id,
            'label' => $label,
            'direction' => $direction,
            'sort_order' => $sortOrder,
        ]);
    }

    protected function createCable(
        string $workspaceId,
        NetworkPoint $from,
        NetworkPoint $to,
        string $name,
        int $coreCount,
        string $cableType = 'fiber',
        ?array $route = null,
    ): CableSegment {
        $route ??= [
            ['type' => 'point', 'point_id' => $from->id],
            ['type' => 'point', 'point_id' => $to->id],
        ];

        return CableSegment::query()->create([
            'workspace_id' => $workspaceId,
            'from_point_id' => $from->id,
            'to_point_id' => $to->id,
            'name' => $name,
            'cable_type' => $cableType,
            'status' => 'active',
            'core_count' => $coreCount,
            'length_m' => $this->distanceM($from, $to),
            'route' => $route,
            'notes' => "{$coreCount}-core demo segment with wired cores.",
            'created_by' => 'Demo Seeder',
        ]);
    }

    /**
     * @return array{core: CableCore, start: CableCoreEnd, end: CableCoreEnd}
     */
    protected function createCoreWithEnds(
        CableSegment $cable,
        int $coreNumber,
        NetworkPoint $startPoint,
        ?NetworkPointPort $startPort,
        NetworkPoint $endPoint,
        ?NetworkPointPort $endPort,
        ?string $label = null,
    ): array {
        $core = CableCore::query()->create([
            'cable_segment_id' => $cable->id,
            'core_number' => $coreNumber,
            'color' => FiberCoreColors::forCoreNumber($coreNumber),
            'label' => $label,
            'status' => 'active',
        ]);

        $start = $this->createCoreEnd($core, 'start', $startPoint, $startPort);
        $end = $this->createCoreEnd($core, 'end', $endPoint, $endPort);

        return compact('core', 'start', 'end');
    }

    protected function createCoreEnd(
        CableCore $core,
        string $side,
        NetworkPoint $point,
        ?NetworkPointPort $port,
    ): CableCoreEnd {
        $end = CableCoreEnd::query()->create([
            'cable_core_id' => $core->id,
            'side' => $side,
            'network_point_id' => $point->id,
            'connection_type' => null,
            'connected_core_end_id' => null,
        ]);

        if ($port) {
            $port->loadMissing('device');
            DeviceConnectionLabel::applyPort($end, $port);
            $end->save();
        }

        $this->coreEndIndex[$end->id] = $end;

        return $end;
    }

    protected function spliceCoreEnds(CableCoreEnd $a, CableCoreEnd $b): void
    {
        $a->update([
            'connection_type' => 'core_end',
            'connected_core_end_id' => $b->id,
            'network_point_port_id' => null,
            'network_point_device_id' => null,
            'device_type' => null,
            'device_label' => null,
            'device_port_label' => null,
            'device_port_direction' => null,
        ]);

        $b->update([
            'connection_type' => 'core_end',
            'connected_core_end_id' => $a->id,
            'network_point_port_id' => null,
            'network_point_device_id' => null,
            'device_type' => null,
            'device_label' => null,
            'device_port_label' => null,
            'device_port_direction' => null,
        ]);

        $this->coreEndIndex[$a->id] = $a->fresh();
        $this->coreEndIndex[$b->id] = $b->fresh();
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function offset(float $lat, float $lng, float $angleDeg, float $distanceDeg): array
    {
        $rad = deg2rad($angleDeg);

        return [
            $lat + ($distanceDeg * cos($rad)),
            $lng + ($distanceDeg * sin($rad)),
        ];
    }

    protected function distanceM(NetworkPoint $from, NetworkPoint $to): float
    {
        $earthRadius = 6371000;
        $lat1 = deg2rad($from->latitude);
        $lat2 = deg2rad($to->latitude);
        $deltaLat = deg2rad($to->latitude - $from->latitude);
        $deltaLng = deg2rad($to->longitude - $from->longitude);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;

        return round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }

    protected function fakePhone(int $index): string
    {
        return '+91 98'.str_pad((string) (10000000 + $index), 8, '0', STR_PAD_LEFT);
    }
}
