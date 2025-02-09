<?php

/**
 * Specific rule configurations for individual domains
 * 
 * Domain rule structure / Estrutura das regras por domínio:
 * - userAgent: Define custom User-Agent for the domain
 * - headers: Custom HTTP headers for requests
 * - idElementRemove: Array of HTML IDs to be removed
 * - classElementRemove: Array of HTML classes to be removed
 * - scriptTagRemove: Array of scripts to be removed (partial match)
 * - cookies: Associative array of cookies to be set (null removes cookie)
 * - classAttrRemove: Array of classes to be removed from elements
 * - customCode: String containing custom JavaScript code
 * - customStyle: String containing custom CSS code
 * - excludeGlobalRules: Associative array of global rules to exclude for this domain
 *   Example:
 *   'excludeGlobalRules' => [
 *       'scriptTagRemove' => ['gtm.js', 'ga.js'],
 *       'classElementRemove' => ['subscription']
 *   ]
 * - fetchStrategies: String indicating which fetch strategy to use. Available values:
 *   - fetchContent: Use standard fetch with domain rules
 *   - fetchFromWaybackMachine: Try to fetch from Internet Archive
 *   - fetchFromSelenium: Use Selenium for extraction
 * - socialReferrers: Add random social media headers
 * - fromGoogleBot: Adds simulation of request coming from Google Bot
 * - removeElementsByTag: Remove specific elements via DOM
 * - removeCustomAttr: Remove custom attributes from elements
 */
return [
    'nsctotal.com.br' => [
        'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
    ],
    'elcorreo.com' => [
        'idElementRemove' => ['didomi-popup','engagement-top'],
        'classElementRemove' => ['content-exclusive-bg'],
        'classAttrRemove' => ['didomi-popup-open','paywall'],
        'fromGoogleBot' => true,
        'removeElementsByTag' => ['style'],
        'removeCustomAttr' => ['hidden','data-*']
    ],
    'globo.com' => [
        'idElementRemove' => ['cookie-banner-lgpd', 'paywall-cpt', 'mc-read-more-wrapper', 'paywall-cookie-content', 'paywall-cpt'],
        'classElementRemove' => ['banner-lgpd', 'article-related-link__title', 'article-related-link__picture', 'paywall-denied', 'banner-subscription'],
        'scriptTagRemove' => ['tiny.js', 'signup.js', 'paywall.js'],
        'cookies' => [
            'piano_d' => null,
            'piano_if' => null,
            'piano_user_id' => null
        ],
        'classAttrRemove' => ['wall', 'protected-content', 'cropped-block']
    ],
    'gauchazh.clicrbs.com.br' => [
        'idElementRemove' => ['paywallTemplate'],
        'classAttrRemove' => ['m-paid-content', 'paid-content-apply'],
        'scriptTagRemove' => ['vendors-8'],
        'excludeGlobalRules' => [
            'classElementRemove' => ['paid-content']
        ],
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'reuters.com' => [
        'classElementRemove' => ['leaderboard__container'],
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'lepoint.fr' => [
        'classElementRemove' => ['paywall'],
    ],
    'gamestar.de' => [
        'classElementRemove' => ['plus-teaser'],
        'classAttrRemove' => ['plus-'],
        'idElementRemove' => ['commentReload']
    ],
    'heise.de' => [
        'classAttrRemove' => ['curtain__purchase-container'],
        'removeElementsByTag' => ['a-gift']
    ],
    'fortune.com' => [
        'classElementRemove' => ['latest-popular-module','own','drawer-menu'],
        'fetchStrategies' => 'fetchFromSelenium',
        'browser' => 'chrome',
        'scriptTagRemove' => ['queryly.com'],
    ],
    'diplomatique.org.br' => [
        'idElementRemove' => ['cboxOverlay'],
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'washingtonpost.com' => [
        'classElementRemove' => ['paywall-overlay'],
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'oantagonista.com.br' => [
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'jornaldebrasilia.com.br' => [
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'npr.org' => [
        'classElementRemove' => ['onetrust-pc-dark-filter'],
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'opopular.com.br' => [
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'businessinsider.com' => [
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'leparisien.fr' => [
        'idElementRemove' => ['didomi-popup'],
        'classAttrRemove' => ['paywall-article-section'],        
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'foreignaffairs.com' => [
        'customCode' => 'document.addEventListener(\'DOMContentLoaded\', function() {
            const dropcapDiv = document.querySelector(\'.article-dropcap\');
            if (dropcapDiv) {
                dropcapDiv.style.height = \'auto\';
            }
        });'
    ],
    'latercera.com' => [
        'classElementRemove' => ['pw-frontier'],
        'customStyle' => '.pw-frontier {
            display: none !important;
        }
        .container-all {
            position: inherit !important;
            top: inherit;
        }
        .main-header .top-menu, .main-header .alert-news, .main-header .alert-news.sticky {
            position:inherit !important;
        }'
    ],
    'folha.uol.com.br' => [
        'idElementRemove' => ['paywall-flutuante', 'paywall', 'paywall-signup'],
        'classElementRemove' => ['banner-assinatura', 'paywall-container'],
        'scriptTagRemove' => ['paywall.js', 'content-gate.js'],
        'cookies' => [
            'paywall_visit' => null,
            'folha_id' => null,
            'paywall_access' => 'true'
        ]
    ],
    'uol.com.br' => [
        'scriptTagRemove' => ['me.jsuol.com.br', 'c.jsuol.com.br'],
        'classElementRemove' => ['header-top-wrapper'],
    ],
    'stcatharinesstandard.ca' => [
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'cartacapital.com.br' => [
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'nzherald.co.nz' => [
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'opovo.com.br' => [
        'classElementRemove' => ['screen-loading', 'overlay-advise']
    ],
    'crusoe.com.br' => [
        'cookies' => [
            'crs_subscriber' => '1'
        ]
    ],
    'theverge.com' => [
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'economist.com' => [
        'cookies' => [
            'ec_limit' => 'allow'
        ],
        'scriptTagRemove' => ['wrapperMessagingWithoutDetection.js'],
        'customCode' => '
            var artBodyContainer = document.querySelector("article.article");
            var artBody = artBodyContainer.innerHTML;
            checkPaywall();
            function checkPaywall() {
                let paywallBox = document.querySelector(".layout-article-regwall");
                if (paywallBox) {
                    artBodyContainer.innerHTML = artBody;
                }
            }
        '
    ],
    'ft.com' => [
        'cookies' => [
            'next-flags' => null,
            'next:ads' => null
        ],
        'fromGoogleBot' => true
    ],
    'nytimes.com' => [
        'idElementRemove' => ['gateway-content','site-index','complianceOverlay'],
        'customCode' => '
            setTimeout(function() {
                const walk = document.createTreeWalker(
                    document.body,
                    NodeFilter.SHOW_TEXT,
                    null,
                    false
                );
                let node;
                while (node = walk.nextNode()) {
                    node.textContent = node.textContent
                        .replace(/&rsquo;/g, "\\u2019")    /* right single quotation */
                        .replace(/&lsquo;/g, "\\u2018")    /* left single quotation */
                        .replace(/&rdquo;/g, "\\u201D")    /* right double quotation */
                        .replace(/&ldquo;/g, "\\u201C")    /* left double quotation */
                        .replace(/&mdash;/g, "\\u2014")    /* em dash */
                        .replace(/&ndash;/g, "\\u2013")    /* en dash */
                        .replace(/&hellip;/g, "\\u2026")   /* horizontal ellipsis */
                        .replace(/&bull;/g, "\\u2022")     /* bullet */
                        .replace(/&amp;/g, "&")            /* ampersand */
                        .replace(/&nbsp;/g, " ")           /* non-breaking space */
                        .replace(/&quot;/g, "\\"")         /* quotation mark */
                        .replace(/&apos;/g, "\'")          /* apostrophe */
                        .replace(/&lt;/g, "<")             /* less than */
                        .replace(/&gt;/g, ">")             /* greater than */
                        .replace(/&agrave;/g, "\\u00E0")   /* lowercase a with grave accent */
                        .replace(/&ntilde;/g, "\\u00F1");  /* lowercase n with tilde */
                }
            }, 3000);
        ',
        'customStyle' => '
            .vi-gateway-container {
                position: inherit !important;
                overflow: inherit !important;
                height: inherit !important;
            }
            #gateway-content {
                display: none !important;
                width: 1px !important;
                height: 1px !important;
                overflow: hidden !important;
                visibility: hidden !important;
            }
            #site-index {
                height: 100% !important;
                position: relative !important;
            }
        ',
        'fetchStrategies' => 'fetchFromSelenium',
        'excludeGlobalRules' => [
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
        ]
    ],
    'correio24horas.com.br' => [
        'idElementRemove' => ['paywall'],
        'classElementRemove' => ['paywall'],
        'classAttrRemove' => ['hide', 'is-active'],
        'cookies' => [
            'premium_access' => '1'
        ]
    ],
    'abril.com.br' => [
        'cookies' => [
            'paywall_access' => 'true'
        ],
        'classElementRemove' => ['piano-offer-overlay'],
        'classAttrRemove' => ['disabledByPaywall'],
        'idElementRemove' => ['piano_offer']
    ],
    'foreignpolicy.com' => [
        'idElementRemove' => ['paywall_bg'],
        'classAttrRemove' => ['overlay-no-scroll', 'overlay-no-scroll']
    ],
    'dgabc.com.br' => [
        'customCode' => '
                var email = "colaborador@dgabc.com.br";
                $(".NoticiaExclusivaNaoLogado").hide();
                $(".NoticiaExclusivaLogadoSemPermissao").hide();
                $(".linhaSuperBanner").show();
                $(".footer").show();
                $(".NoticiaExclusivaLogado").show();
            '
    ],
    'forbes.com' => [
        'classElementRemove' => ['zephr-backdrop', 'zephr-generic-modal'],
        'excludeGlobalRules' => [
            'classElementRemove' => ['premium-article'],
        ],
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'cmjornal.pt' => [
        'classAttrRemove' => ['bloco_bloqueio_premium'],
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'sabado.pt' => [
        'classElementRemove' => ['bloco_bloqueio'],
        'fetchStrategies' => 'fetchFromSelenium',
    ],
    'seudinheiro.com' => [
        'idElementRemove' => ['premium-paywall']
    ],
    'technologyreview.com' => [
        'cookies' => [
            'xbc' => null,
            '_pcid' => null,
            '_pcus' => null,
            '__tbc' => null,
            '__pvi' => null,
            '_pctx' => null
        ]
    ],

    // Domain test
    'altendorfme.github.io' => [
        'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'headers' => [
            'Accept-Language' => 'en-US,en;q=0.9',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache'
        ],
        'idElementRemove' => ['test-id-1', 'paywall'],
        'classElementRemove' => ['test-class-1'],
        'scriptTagRemove' => ['analytics.js', 'test-script.js', 'paywall.js'],
        'cookies' => [
            'visited' => 'true',
            'consent' => 'accepted',
            'session_id' => null
        ],
        'classAttrRemove' => ['test-attr-1','paywall'],
        'customCode' => '
            console.log("worked");
        ',
        'customStyle' => '
            .test-style {
                background: red;
            }
        ',
        'excludeGlobalRules' => [
            'scriptTagRemove' => ['excluded-script.js'],
            'classElementRemove' => ['excluded-class']
        ],
        'fetchStrategies' => 'fetchContent',
        'socialReferrers' => true,
        'fromGoogleBot' => true,
        'removeElementsByTag' => ['iframe'],
        'removeCustomAttr' => ['data-*']
    ]
];
