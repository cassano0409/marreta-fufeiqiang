<?php
/**
 * PWA Web Manifest Generator
 * 
 * This file generates the Web App Manifest (manifest.json) for Progressive Web App (PWA) functionality.
 * It defines the application's behavior when installed on a device and its appearance.
 * 
 * Este arquivo gera o Manifesto Web (manifest.json) para funcionalidade de Progressive Web App (PWA).
 * Ele define o comportamento da aplicação quando instalada em um dispositivo e sua aparência.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../inc/Language.php';

header('Content-Type: application/json');

$manifest = [
    'name' => SITE_NAME,
    'short_name' => SITE_NAME,
    'description' => SITE_DESCRIPTION,
    'start_url' => SITE_URL,
    'id' => SITE_URL,
    'scope' => '/',
    'display' => 'standalone',
    'display_override' => ['window-controls-overlay', 'minimal-ui'],
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
        'action' => '/p/',
        'method' => 'GET',
        'enctype' => 'application/x-www-form-urlencoded',
        'params' => [
            'title' => 'title',
            'text' => 'text',
            'url' => 'url',
        ]
    ],
    'prefer_related_applications' => false,
    'lang' => Language::getCurrentLanguage(),
    'dir' => 'ltr'
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
