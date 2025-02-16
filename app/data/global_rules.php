<?php

/**
 * Global rule configurations applied to all domains
 * 
 * Note: These rules can be overridden or disabled for specific domains
 * using the 'excludeGlobalRules' configuration in domain_rules.php
 */
return [
    // Classes to be removed from all pages:
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
    // Scripts to be removed from all pages:
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
        'cdn.pn.vg',
        'static.vocstatic.com',
        'recaptcha',
		'intercom'
    ]
];
