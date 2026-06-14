<?php

namespace App\Support;

class NetworkCatalog
{
    /**
     * @return array<string, string>
     */
    public static function pointTypes(): array
    {
        return [
            'uplink' => 'ISP / RailTel uplink',
            'bras' => 'BRAS / edge router',
            'switch' => 'Core switch',
            'odf' => 'ODF / patch panel',
            'dwdm' => 'DWDM / CWDM MUX',
            'router' => 'OLT / router',
            'splitter' => 'Splitter',
            'junction' => 'Junction box',
            'cabinet' => 'Street cabinet / rack',
            'pole' => 'Pole / tower',
            'customer' => 'Customer premise',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function pointStatuses(): array
    {
        return [
            'active' => 'Active',
            'planned' => 'Planned',
            'maintenance' => 'Maintenance',
            'inactive' => 'Inactive',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function cableTypes(): array
    {
        return [
            'fiber' => 'Fiber optic',
            'coax' => 'Coaxial',
            'ethernet' => 'Ethernet',
            'wireless' => 'Wireless link',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function cableStatuses(): array
    {
        return [
            'active' => 'Active',
            'planned' => 'Planned',
            'damaged' => 'Damaged',
            'inactive' => 'Inactive',
        ];
    }
}
