<?php

/**
 * Configurações globais de regras aplicadas a todos os domínios
 * 
 * Este arquivo define regras que são aplicadas por padrão a todos os sites,
 * organizadas em categorias para melhor manutenção e compreensão.
 * 
 * Estrutura das regras globais:
 * 
 * classElementRemove: Classes HTML que devem ser removidas, agrupadas por categoria
 * - paywall: Classes relacionadas a paywalls e conteúdo premium
 * - social: Classes de elementos de compartilhamento social
 * - newsletter: Classes de popups e formulários de newsletter
 * 
 * scriptTagRemove: Scripts que devem ser removidos, agrupados por categoria
 * - tracking: Scripts de analytics e rastreamento
 * - paywall: Scripts relacionados a paywalls e conteúdo premium
 * - cookies: Scripts de gerenciamento de cookies e GDPR/LGPD
 * - misc: Scripts diversos como push notifications
 * 
 * Nota: Estas regras podem ser sobrescritas ou desativadas para domínios específicos
 * usando a configuração 'excludeGlobalRules' em domain_rules.php
 */
return [
    'classElementRemove' => [
        'paywall' => [
            'subscription',
            'subscriber-content',
            'premium-content',
            'signin-wall',
            'register-wall',
            'paid-content',
            'premium-article',
            'subscription-box',
            'piano-offer',
            'piano-inline',
            'piano-modal',
            'paywall-container',
            'paywall-overlay',
            'paywall-wrapper',
            'paywall-notification'
        ],
        'social' => [
            'social-share',
            'social-buttons',
            'share-container'
        ],
        'newsletter' => [
            'newsletter-popup',
            'subscribe-form',
            'signup-overlay'
        ]
    ],
    'scriptTagRemove' => [
        'tracking' => [
            'ga.js',
            'fbevents.js',
            'pixel.js',
            'chartbeat',
            'analytics.js',
        ],
        'paywall' => [
            'wall.js',
            'paywall.js',
            'subscriber.js',
            'piano.js',
            'tiny.js',
            'pywll.js',
            'content-gate.js',
            'signwall.js',
            'pw.js',
            'pw-',
            'piano-',
            'tinypass.js',
            'tinypass.min.js',
            'tp.min.js',
            'premium.js',
            'amp-access-0.1.js'
        ],
        'cookies' => [
            'cookie',
            'gdpr',
            'lgpd'
        ],
        'misc' => [
            'push',
            'sw.js',
            'stats.js'
        ]
    ]
];
