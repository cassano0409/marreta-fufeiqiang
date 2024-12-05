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
 * - excludeGlobalRules: Array associativo de regras globais a serem excluídas para este domínio
 *   Exemplo:
 *   'excludeGlobalRules' => [
 *       'scriptTagRemove' => ['gtm.js', 'ga.js'],  // Exclui scripts específicos das regras globais
 *       'classElementRemove' => ['subscription']    // Exclui classes específicas das regras globais
 *   ]
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
        'idElementRemove' => ['paywall', 'paywall-container', 'softwall'],
        'classElementRemove' => ['paywall-content', 'signin-wall', 'pay-wall'],
        'scriptTagRemove' => ['paywall.js', 'pywll.js'],
        'cookies' => [
            'estadao_paywall' => null
        ]
    ],
    'opovo.com.br' => [
        'classElementRemove' => ['screen-loading', 'overlay-advise']
    ],
    'crusoe.com.br' => [
        'cookies' => [
            'crs_subscriber' => '1'
        ]
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
        'cookies' => [
            'nyt-gdpr' => '1',
            'nyt-purr' => 'cfh'
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
        ]
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
