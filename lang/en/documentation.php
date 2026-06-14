<?php

return [
    'title' => 'Network Desk Documentation',
    'subtitle' => 'Complete guide to mapping cables, routers, split points, and managing your ISP network on the map.',

    'sections' => [
        [
            'id' => 'overview',
            'title' => 'Overview',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Network Desk helps internet and cable providers map their infrastructure across cities and villages. Plot routers, splitters, junction boxes, and customer premises on an interactive map, draw cable segments between them, upload reference photos, and filter the full network from the dashboard.'],
                ['type' => 'list', 'title' => 'Main areas', 'items' => [
                    'Dashboard — filtered overview map with summary stats',
                    'Map editor — add points, drag to reposition, draw cables',
                    'Points — searchable table of all mapped locations',
                    'Roles & members — control who can view or edit the network',
                ]],
            ],
        ],
        [
            'id' => 'dashboard',
            'title' => 'Network dashboard',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'The dashboard shows your full cable and device map with summary cards for total points, cable segments, and reference photos. Use the sidebar filters to narrow by point type, status, area, or cable condition.'],
                ['type' => 'list', 'title' => 'Filters', 'items' => [
                    'Point type — uplink, BRAS, switch, ODF, DWDM, OLT, splitter, junction, cabinet, pole, customer',
                    'Point status — active, planned, maintenance, inactive',
                    'Area / zone — village, sector, or locality name',
                    'Cable status — active, planned, damaged, inactive',
                ]],
            ],
        ],
        [
            'id' => 'map-editor',
            'title' => 'Map editor',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'The map editor is designed for field teams. Click Add point, then click anywhere on the map to place a new device or split point. Drag markers to adjust coordinates. Use Draw cable to connect two points.'],
                ['type' => 'list', 'title' => 'Point details', 'items' => [
                    'Name and type (router, splitter, junction box, etc.)',
                    'Status and area / zone',
                    'GPS coordinates and street address',
                    'Contact name and phone for site access',
                    'Port count and notes',
                    'Reference photos uploaded from the field',
                ]],
            ],
        ],
        [
            'id' => 'permissions',
            'title' => 'Permissions',
            'blocks' => [
                ['type' => 'table', 'headers' => ['Permission', 'Allows'], 'rows' => [
                    ['network.view', 'View dashboard, map, and points list'],
                    ['network.create', 'Add new points and cable segments'],
                    ['network.edit', 'Edit points, upload photos, reposition markers'],
                    ['network.delete', 'Remove points and cable segments'],
                ]],
            ],
        ],
    ],
];
