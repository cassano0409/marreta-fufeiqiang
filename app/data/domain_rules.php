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
 * - clearStorage: Boolean indicando se deve limpar o storage do navegador
 * - fixRelativeUrls: Boolean para habilitar correção de URLs relativas
 * - customCode: String contendo código JavaScript personalizado para execução
 * - excludeGlobalRules: Array de regras globais que devem ser ignoradas
 */
return [
    'nsctotal.com.br' => [
        'userAgent' => '',
        'headers' => ''
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
        'classAttrRemove' => ['wall', 'protected-content', 'cropped-block'],
        'clearStorage' => true,
    ],
    'folha.uol.com.br' => [
        'idElementRemove' => ['paywall-flutuante', 'paywall', 'paywall-signup'],
        'classElementRemove' => ['banner-assinatura', 'paywall-container'],
        'scriptTagRemove' => ['paywall.js', 'content-gate.js'],
        'cookies' => [
            'paywall_visit' => null,
            'folha_id' => null,
            'paywall_access' => 'true'
        ],
        'clearStorage' => true
    ],
    'estadao.com.br' => [
        'idElementRemove' => ['paywall', 'paywall-container', 'softwall'],
        'classElementRemove' => ['paywall-content', 'signin-wall', 'pay-wall'],
        'scriptTagRemove' => ['paywall.js', 'pywll.js'],
        'cookies' => [
            'estadao_paywall' => null
        ],
        'clearStorage' => true
    ],
    'exame.com' => [
        'fixRelativeUrls' => true,
    ],
    'diarinho.net' => [
        'fixRelativeUrls' => true,
    ],
    'em.com.br' => [
        'fixRelativeUrls' => true,
    ],
    'businessinsider.com' => [
        'fixRelativeUrls' => true,
    ],
    'opovo.com.br' => [
        'fixRelativeUrls' => true,
        'classElementRemove' => ['screen-loading', 'overlay-advise'],
    ],
    'folhadelondrina.com.br' => [
        'fixRelativeUrls' => true,
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
        'clearStorage' => true,
        'customHeaders' => [
            'Referer' => 'https://www.google.com.br/'
        ]
    ],
    'nytimes.com' => [
        'cookies' => [
            'nyt-gdpr' => '1',
            'nyt-purr' => 'cfh'
        ],
        'clearStorage' => true
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
        'classAttrRemove' => ['overlay-no-scroll', 'overlay-no-scroll'],
    ],
    'wired.com' => [
        'clearStorage' => true
    ],
    'dgabc.com.br' => [
        'customCode' => '
                var email = "colaborador@dgabc.com.br";
                $(".NoticiaExclusivaNaoLogado").hide();
                $(".NoticiaExclusivaLogadoSemPermissao").hide();
                $(".linhaSuperBanner").show();
                $(".footer").show();
                $(".NoticiaExclusivaLogado").show();
            ',
        'fixRelativeUrls' => true,
    ],
    'forbes.com' => [
        'classElementRemove' => ['zephr-backdrop', 'zephr-generic-modal'],
        'excludeGlobalRules' => [
            'classElementRemove' => [
                'paywall' => [
                    'premium-article',
                ],
            ],
        ],
    ],
    'seudinheiro.com' => [
        'idElementRemove' => ['premium-paywall'],
    ],
    'technologyreview.com' => [
        'cookies' => [
            'xbc' => null,
            '_pcid' => null,
            '_pcus' => null,
            '__tbc' => null,
            '__pvi' => null,
            '_pctx' => null
        ],
        'clearStorage' => true
    ]
];
