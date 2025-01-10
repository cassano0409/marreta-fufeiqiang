<?php
require_once 'config.php';

header('Content-Type: application/json');

$manifest = [
    'name' => SITE_NAME,
    'short_name' => SITE_NAME,
    'description' => SITE_DESCRIPTION,
    'start_url' => SITE_URL,
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => '#2563eb',
    'icons' => [
        [
            'src' => 'assets/pwa/192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => 'assets/pwa/512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ],
    'share_target' => [
        'action' => '/p/',
        'method' => 'GET',
        'params' => [
            'url' => 'text'
        ]
    ]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
