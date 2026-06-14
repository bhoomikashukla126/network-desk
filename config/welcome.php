<?php

return [
    'default_locale' => 'en',

    'icon' => '🗺️',
    'logo' => 'images/welcome/logo.svg',
    'central_url' => config('central.url'),
    'hero_image' => 'images/welcome/hero.svg',

    'accent' => [
        'primary' => '#3b82f6',
        'primary_dark' => '#2563eb',
        'gradient_from' => '#eff6ff',
        'gradient_to' => '#ffffff',
        'glow' => 'rgba(59, 130, 246, 0.18)',
        'glow_secondary' => 'rgba(37, 99, 235, 0.12)',
        'badge_bg' => 'rgba(59, 130, 246, 0.14)',
    ],

    'preview' => [
        'image' => 'images/welcome/complaints-dashboard.png',
    ],

    'gallery' => [
        ['image' => 'images/welcome/complaints-dashboard.png'],
        ['image' => 'images/welcome/new-complaint.png'],
        ['image' => 'images/welcome/roles-permissions.png'],
        ['image' => 'images/welcome/bulk-import.png'],
        ['image' => 'images/welcome/export-complaints.png'],
    ],
];
