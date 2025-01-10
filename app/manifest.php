<?php
/**
 * PWA Web Manifest Generator
 * 
 * This file generates the Web App Manifest (manifest.json) for Progressive Web App (PWA) functionality.
 * It defines the application's behavior when installed on a device and its appearance in various contexts.
 * 
 * Este arquivo gera o Manifesto Web (manifest.json) para funcionalidade de Progressive Web App (PWA).
 * Ele define o comportamento da aplicação quando instalada em um dispositivo e sua aparência em vários contextos.
 */

require_once 'config.php';

header('Content-Type: application/json');

$manifest = [
    'name' => SITE_NAME,
    'short_name' => SITE_NAME,
    'description' => SITE_DESCRIPTION,
    'start_url' => SITE_URL,
    'display' => 'browser',
    'display_override' => ['window-controls-overlay'],
    'background_color' => '#ffffff',
    'theme_color' => '#2563eb',
    'orientation' => 'any',
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
        'action' => 'pwa.php',
        'method' => 'GET',
        'params' => [
            'title' => 'title',
            'text' => 'text',
            'url' => 'url'
        ]
    ]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
