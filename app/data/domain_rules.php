<?php

/**
 * Configurações específicas de regras para domínios individuais
 * 
 * Este arquivo contém regras personalizadas para sites específicos, permitindo
 * ajustar o comportamento do sistema para cada domínio individualmente.
 * 
 * Estrutura das regras por domínio:
 * - userAgent: Define um User-Agent personalizado para o domínio
 * - headers: Headers HTTP personalizados para requisições
 * - idElementRemove: Array de IDs HTML que devem ser removidos da página
 * - classElementRemove: Array de classes HTML que devem ser removidas
 * - scriptTagRemove: Array de scripts que devem ser removidos (partial match)
 * - cookies: Array associativo de cookies a serem definidos (null remove o cookie)
 * - classAttrRemove: Array de classes a serem removidas de elementos
 * - customCode: String contendo código JavaScript personalizado para execução
 * - customStyle: String contendo código CSS personalizado para execução
 * - excludeGlobalRules: Array associativo de regras globais a serem excluídas para este domínio
 *   Exemplo:
 *   'excludeGlobalRules' => [
 *       'scriptTagRemove' => ['gtm.js', 'ga.js'],  // Exclui scripts específicos das regras globais
 *       'classElementRemove' => ['subscription']    // Exclui classes específicas das regras globais
 *   ]
 * - useSelenium: Boolean indicando se deve usar Selenium para extração
 */
return [
    'nsctotal.com.br' => [
        'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
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
        'useSelenium' => true
    ],
    'reuters.com' => [
        'classElementRemove' => ['leaderboard__container'],
        'useSelenium' => true
    ],
    'diplomatique.org.br' => [
        'idElementRemove' => ['cboxOverlay'],
        'useSelenium' => true
    ],
    'washingtonpost.com' => [
        'classElementRemove' => ['paywall-overlay'],
        'useSelenium' => true
    ],
    'oantagonista.com.br' => [
        'useSelenium' => true
    ],
    'jornaldebrasilia.com.br' => [
        'useSelenium' => true
    ],
    'npr.org' => [
        'classElementRemove' => ['onetrust-pc-dark-filter'],
        'useSelenium' => true
    ],
    'opopular.com.br' => [
        'useSelenium' => true
    ],
    'businessinsider.com' => [
        'useSelenium' => true
    ],
    'leparisien.fr' => [
        'idElementRemove' => ['didomi-popup'],
        'classAttrRemove' => ['paywall-article-section'],        
        'useSelenium' => true
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
    'estadao.com.br' => [
        'useSelenium' => true,
        'browser' => 'chrome'
    ],
    'stcatharinesstandard.ca' => [
        'useSelenium' => true
    ],
    'cartacapital.com.br' => [
        'useSelenium' => true
    ],
    'nzherald.co.nz' => [
        'useSelenium' => true
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
        'useSelenium' => true
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
        'headers' => [
            'Referer' => 'https://www.google.com.br/'
        ]
    ],
    'nytimes.com' => [
        'useSelenium' => true,
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
        'useSelenium' => true
    ],
    'cmjornal.pt' => [
        'classAttrRemove' => ['bloco_bloqueio_premium'],
        'useSelenium' => true
    ],
    'sabado.pt' => [
        'classElementRemove' => ['bloco_bloqueio'],
        'useSelenium' => true
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
    ]
];
