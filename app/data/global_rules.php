<?php

/**
 * Global rule configurations applied to all domains
 * Configurações globais de regras aplicadas a todos os domínios
 * 
 * This file defines rules that are applied by default to all sites,
 * organized into categories for better maintenance and understanding.
 * 
 * Este arquivo define regras que são aplicadas por padrão a todos os sites,
 * organizadas em categorias para melhor manutenção e compreensão.
 * 
 * Note: These rules can be overridden or disabled for specific domains
 * using the 'excludeGlobalRules' configuration in domain_rules.php
 * 
 * Nota: Estas regras podem ser sobrescritas ou desativadas para domínios específicos
 * usando a configuração 'excludeGlobalRules' em domain_rules.php
 */
return [
    // HTML classes to be removed from all pages
    // Classes HTML a serem removidas de todas as páginas
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
        'signup-overlay',
        'onesignal-slidedown-container'
    ],
    // Scripts to be removed from all pages
    // Scripts a serem removidos de todas as páginas
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
        'smartocto.com',
        'cdn.pn.vg'
    ]
];
