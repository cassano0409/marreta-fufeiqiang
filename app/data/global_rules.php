<?php

/**
 * Configurações globais de regras aplicadas a todos os domínios
 * 
 * Este arquivo define regras que são aplicadas por padrão a todos os sites,
 * organizadas em categorias para melhor manutenção e compreensão.
 * 
 * Nota: Estas regras podem ser sobrescritas ou desativadas para domínios específicos
 * usando a configuração 'excludeGlobalRules' em domain_rules.php
 */
return [
    'classElementRemove' => [
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
        'paywall-notification',
        'leaky_paywall_message_wrap',
        'subscribe-form',
        'signup-overlay'
    ],
    'scriptTagRemove' => [
        'gtm.js',
        'ga.js',
        'fbevents.js',
        'pixel.js',
        'chartbeat',
        'analytics.js',
        'cmp.js',
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
        'tinypass',
        'tp.min.js',
        'premium.js',
        'amp-access-0.1.js',
        'zephrBarriersScripts',
        'leaky-paywall',
        'cookie',
        'gdpr',
        'lgpd',
        'push',
        'sw.js',
        'stats.js',
        'piano.io',
        'onesignal.com',
        'getsitecontrol.com',
        'navdmp.com',
        'getblue.io',
        'smartocto.com'
    ]
];
