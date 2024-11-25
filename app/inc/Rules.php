<?php
/**
 * Classe responsável pelo gerenciamento de regras de manipulação de conteúdo
 * 
 * Esta classe implementa um sistema de regras para diferentes domínios web,
 * permitindo a personalização do comportamento do sistema para cada site.
 * Inclui funcionalidades para remoção de paywalls, elementos específicos,
 * manipulação de cookies e execução de códigos customizados.
 */
class Rules {
    /**
     * Array associativo contendo regras específicas para cada domínio
     * 
     * Configurações possíveis para cada domínio:
     * @var array
     * 
     * - idElementRemove: IDs de elementos HTML que devem ser removidos
     * - classElementRemove: Classes de elementos HTML que devem ser removidos
     * - scriptTagRemove: Scripts que devem ser removidos
     * - cookies: Cookies que devem ser definidos ou removidos
     * - classAttrRemove: Classes que devem ser removidas de elementos
     * - clearStorage: Se deve limpar o storage do navegador
     * - customCode: Código JavaScript personalizado para execução
     * - excludeGlobalRules: Array de regras globais a serem excluídas
     * - userAgent: User Agent personalizado
     * - headers: Headers HTTP personalizados
     * - fixRelativeUrls: Habilita correção de URLs relativas
     */
    private $domainRules = [
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

    // Regras globais expandidas
    private $globalRules = [
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

    /**
     * Obtém o domínio base removendo o prefixo www
     * 
     * @param string $domain Domínio completo
     * @return string Domínio base sem www
     */
    private function getBaseDomain($domain) {
        return preg_replace('/^www\./', '', $domain);
    }

    /**
     * Divide um domínio em suas partes constituintes
     * 
     * @param string $domain Domínio a ser dividido
     * @return array Array com todas as combinações possíveis do domínio
     */
    private function getDomainParts($domain) {
        $domain = $this->getBaseDomain($domain);
        $parts = explode('.', $domain);
        
        $combinations = [];
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $combinations[] = implode('.', array_slice($parts, $i));
        }
        
        usort($combinations, function($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        return $combinations;
    }

    /**
     * Obtém as regras específicas para um domínio
     * 
     * @param string $domain Domínio para buscar regras
     * @return array|null Array com regras mescladas ou null se não encontrar
     */
    public function getDomainRules($domain) {
        $domainParts = $this->getDomainParts($domain);
        
        foreach ($this->domainRules as $pattern => $rules) {
            if ($this->getBaseDomain($domain) === $this->getBaseDomain($pattern)) {
                return $this->mergeWithGlobalRules($rules);
            }
        }
        
        foreach ($domainParts as $part) {
            foreach ($this->domainRules as $pattern => $rules) {
                if ($part === $this->getBaseDomain($pattern)) {
                    return $this->mergeWithGlobalRules($rules);
                }
            }
        }
        
        return null;
    }

    /**
     * Mescla regras específicas do domínio com regras globais
     * 
     * @param array $rules Regras específicas do domínio
     * @return array Regras mescladas
     */
    private function mergeWithGlobalRules($rules) {
        $globalRules = $this->getGlobalRules();

        if (isset($rules['excludeGlobalRules']) && is_array($rules['excludeGlobalRules'])) {
            foreach ($rules['excludeGlobalRules'] as $ruleType => $categories) {
                if (isset($globalRules[$ruleType])) {
                    foreach ($categories as $category => $itemsToExclude) {
                        if (isset($globalRules[$ruleType][$category])) {
                            $globalRules[$ruleType][$category] = array_diff(
                                $globalRules[$ruleType][$category],
                                $itemsToExclude
                            );
                        }
                    }
                }
            }
        }

        foreach ($globalRules as $ruleType => $categories) {
            if (!isset($rules[$ruleType])) {
                $rules[$ruleType] = [];
            }
            foreach ($categories as $category => $items) {
                $rules[$ruleType] = array_merge($rules[$ruleType], $items);
            }
        }

        return $rules;
    }

    /**
     * Retorna todas as regras globais
     * 
     * @return array Array com todas as regras globais
     */
    public function getGlobalRules() {
        return $this->globalRules;
    }
}
