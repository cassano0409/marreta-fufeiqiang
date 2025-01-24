<?php

/**
 * Class responsible for URL analysis and processing
 * Classe responsável pela análise e processamento de URLs
 * 
 * This class implements functionalities for:
 * Esta classe implementa funcionalidades para:
 * 
 * - URL analysis and cleaning / Análise e limpeza de URLs
 * - Content caching / Cache de conteúdo
 * - DNS resolution / Resolução DNS
 * - HTTP requests with multiple attempts / Requisições HTTP com múltiplas tentativas
 * - Content processing based on domain-specific rules / Processamento de conteúdo baseado em regras específicas por domínio
 * - Wayback Machine support as fallback / Suporte a Wayback Machine como fallback
 * - Selenium extraction support when enabled by domain / Suporte a extração via Selenium quando habilitado por domínio
 */

require_once __DIR__ . '/Rules.php';
require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Language.php';

use Curl\Curl;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Inc\Logger;

/**
 * Custom exception class for URL analysis errors
 * Classe de exceção personalizada para erros de análise de URL
 */
class URLAnalyzerException extends Exception
{
    private $errorType;
    private $additionalInfo;

    public function __construct($message, $code, $errorType, $additionalInfo = '')
    {
        parent::__construct($message, $code);
        $this->errorType = $errorType;
        $this->additionalInfo = $additionalInfo;
    }

    public function getErrorType()
    {
        return $this->errorType;
    }

    public function getAdditionalInfo()
    {
        return $this->additionalInfo;
    }
}

class URLAnalyzer
{
    // Error type constants
    const ERROR_INVALID_URL = 'INVALID_URL';
    const ERROR_BLOCKED_DOMAIN = 'BLOCKED_DOMAIN';
    const ERROR_NOT_FOUND = 'NOT_FOUND';
    const ERROR_HTTP_ERROR = 'HTTP_ERROR';
    const ERROR_CONNECTION_ERROR = 'CONNECTION_ERROR';
    const ERROR_DNS_FAILURE = 'DNS_FAILURE';
    const ERROR_CONTENT_ERROR = 'CONTENT_ERROR';
    const ERROR_GENERIC_ERROR = 'GENERIC_ERROR';

    // Error mapping
    private $errorMap = [
        self::ERROR_INVALID_URL => ['code' => 400, 'message_key' => 'INVALID_URL'],
        self::ERROR_BLOCKED_DOMAIN => ['code' => 403, 'message_key' => 'BLOCKED_DOMAIN'],
        self::ERROR_NOT_FOUND => ['code' => 404, 'message_key' => 'NOT_FOUND'],
        self::ERROR_HTTP_ERROR => ['code' => 502, 'message_key' => 'HTTP_ERROR'],
        self::ERROR_CONNECTION_ERROR => ['code' => 503, 'message_key' => 'CONNECTION_ERROR'],
        self::ERROR_DNS_FAILURE => ['code' => 504, 'message_key' => 'DNS_FAILURE'],
        self::ERROR_CONTENT_ERROR => ['code' => 502, 'message_key' => 'CONTENT_ERROR'],
        self::ERROR_GENERIC_ERROR => ['code' => 500, 'message_key' => 'GENERIC_ERROR']
    ];

    /**
     * Helper method to throw standardized errors
     * Método auxiliar para lançar erros padronizados
     */
    private function throwError($errorType, $additionalInfo = '')
    {
        $errorConfig = $this->errorMap[$errorType];
        $message = Language::getMessage($errorConfig['message_key'])['message'];
        if ($additionalInfo) {
            $message;
        }
        throw new URLAnalyzerException($message, $errorConfig['code'], $errorType, $additionalInfo);
    }

    /**
     * @var array List of available User Agents for requests
     * @var array Lista de User Agents disponíveis para requisições
     */
    private $userAgents = [
        // Google News bot
        // Bot do Google News
        'Googlebot-News',
        // Mobile Googlebot
        // Googlebot para dispositivos móveis
        'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/W.X.Y.Z Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        // Desktop Googlebot
        // Googlebot para desktop
        'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Chrome/W.X.Y.Z Safari/537.36'
    ];

    /**
     * @var array List of social media referrers
     * @var array Lista de referenciadores de mídia social
     */
    private $socialReferrers = [
        // Twitter
        'https://t.co/',
        'https://www.twitter.com/',
        // Facebook
        'https://www.facebook.com/',
        // Linkedin
        'https://www.linkedin.com/'
    ];

    /**
     * @var array List of DNS servers for resolution
     * @var array Lista de servidores DNS para resolução
     */
    private $dnsServers;

    /**
     * @var Rules Instance of rules class
     * @var Rules Instância da classe de regras
     */
    private $rules;

    /**
     * @var Cache Instance of cache class
     * @var Cache Instância da classe de cache
     */
    private $cache;

    /**
     * @var array List of rules activated during processing
     * @var array Lista de regras ativadas durante o processamento
     */
    private $activatedRules = [];

    /**
     * Class constructor
     * Construtor da classe
     * 
     * Initializes required dependencies
     * Inicializa as dependências necessárias
     */
    public function __construct()
    {
        $this->dnsServers = explode(',', DNS_SERVERS);
        $this->rules = new Rules();
        $this->cache = new Cache();
    }

    /**
     * Check if a URL has redirects and return the final URL
     * Verifica se uma URL tem redirecionamentos e retorna a URL final
     * 
     * @param string $url URL to check redirects / URL para verificar redirecionamentos
     * @return array Array with final URL and if there was a redirect / Array com a URL final e se houve redirecionamento
     */
    public function checkStatus($url)
    {
        $curl = new Curl();
        $curl->setFollowLocation();
        $curl->setOpt(CURLOPT_TIMEOUT, 5);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_NOBODY, true);
        $curl->setUserAgent($this->getRandomUserAgent());
        $curl->get($url);

        if ($curl->error) {
            return [
                'finalUrl' => $url,
                'hasRedirect' => false,
                'httpCode' => $curl->httpStatusCode
            ];
        }

        return [
            'finalUrl' => $curl->effectiveUrl,
            'hasRedirect' => ($curl->effectiveUrl !== $url),
            'httpCode' => $curl->httpStatusCode
        ];
    }

    /**
     * Get a random user agent, with possibility of using Google bot
     * Obtém um user agent aleatório, com possibilidade de usar o Google bot
     * 
     * @param bool $preferGoogleBot Whether to prefer Google bot user agents / Se deve preferir user agents do Google bot
     * @return string Selected user agent / User agent selecionado
     */
    private function getRandomUserAgent($preferGoogleBot = false)
    {
        if ($preferGoogleBot && rand(0, 100) < 70) {
            return $this->userAgents[array_rand($this->userAgents)];
        }
        return $this->userAgents[array_rand($this->userAgents)];
    }

    /**
     * Get a random social media referrer
     * Obtém um referenciador de mídia social aleatório
     * 
     * @return string Selected referrer / Referenciador selecionado
     */
    private function getRandomSocialReferrer()
    {
        return $this->socialReferrers[array_rand($this->socialReferrers)];
    }

    /**
     * Main method for URL analysis
     * Método principal para análise de URLs
     * 
     * @param string $url URL to be analyzed / URL a ser analisada
     * @return string Processed content / Conteúdo processado
     * @throws URLAnalyzerException In case of processing errors / Em caso de erros durante o processamento
     */
    public function analyze($url)
    {
        // Reset activated rules for new analysis
        // Reset das regras ativadas para nova análise
        $this->activatedRules = [];

        // 1. Check cache / Verifica cache
        if ($this->cache->exists($url)) {
            return $this->cache->get($url);
        }

        // 2. Check blocked domains / Verifica domínios bloqueados
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            $this->throwError(self::ERROR_INVALID_URL);
        }
        $host = preg_replace('/^www\./', '', $host);

        if (in_array($host, BLOCKED_DOMAINS)) {
            Logger::getInstance()->logUrl($url, 'BLOCKED_DOMAIN');
            $this->throwError(self::ERROR_BLOCKED_DOMAIN);
        }

        // 3. Check URL status code before proceeding
        $redirectInfo = $this->checkStatus($url);
        if ($redirectInfo['httpCode'] !== 200) {
            Logger::getInstance()->logUrl($url, 'INVALID_STATUS_CODE', "HTTP {$redirectInfo['httpCode']}");
            if ($redirectInfo['httpCode'] === 404) {
                $this->throwError(self::ERROR_NOT_FOUND);
            } else {
                $this->throwError(self::ERROR_HTTP_ERROR, "HTTP {$redirectInfo['httpCode']}");
            }
        }

        try {
            // 4. Get domain rules and check fetch strategy / Obtenha regras de domínio e verifique a estratégia de busca
            $domainRules = $this->getDomainRules($host);
            $fetchStrategy = isset($domainRules['fetchStrategies']) ? $domainRules['fetchStrategies'] : null;

            // If a specific fetch strategy is defined, use only that / Se uma estratégia de busca específica for definida, use somente essa
            if ($fetchStrategy) {
                try {
                    $content = null;
                    switch ($fetchStrategy) {
                        case 'fetchContent':
                            $content = $this->fetchContent($url);
                            break;
                        case 'fetchFromWaybackMachine':
                            $content = $this->fetchFromWaybackMachine($url);
                            break;
                        case 'fetchFromSelenium':
                            $content = $this->fetchFromSelenium($url, isset($domainRules['browser']) ? $domainRules['browser'] : 'firefox');
                            break;
                    }
                    
                    if (!empty($content)) {
                        $this->activatedRules[] = "fetchStrategy: $fetchStrategy";
                        $processedContent = $this->processContent($content, $host, $url);
                        $this->cache->set($url, $processedContent);
                        return $processedContent;
                    }
                } catch (Exception $e) {
                    Logger::getInstance()->logUrl($url, strtoupper($fetchStrategy) . '_ERROR', $e->getMessage());
                    throw $e;
                }
            }

            // 5. Try all strategies in sequence
            $fetchStrategies = [
                ['method' => 'fetchContent', 'args' => [$url]],
                ['method' => 'fetchFromWaybackMachine', 'args' => [$url]],
                ['method' => 'fetchFromSelenium', 'args' => [$url, 'firefox']]
            ];

            $lastError = null;
            foreach ($fetchStrategies as $strategy) {
                try {
                    $content = call_user_func_array([$this, $strategy['method']], $strategy['args']);
                    if (!empty($content)) {
                        $this->activatedRules[] = "fetchStrategy: {$strategy['method']}";
                        $processedContent = $this->processContent($content, $host, $url);
                        $this->cache->set($url, $processedContent);
                        return $processedContent;
                    }
                } catch (Exception $e) {
                    $lastError = $e;
                    error_log("{$strategy['method']}_ERROR: " . $e->getMessage());
                    continue;
                }
            }

            // If we get here, all strategies failed
            Logger::getInstance()->logUrl($url, 'GENERAL_FETCH_ERROR');
            if ($lastError) {
                $message = $lastError->getMessage();
                if (strpos($message, 'DNS') !== false) {
                    $this->throwError(self::ERROR_DNS_FAILURE);
                } elseif (strpos($message, 'CURL') !== false) {
                    $this->throwError(self::ERROR_CONNECTION_ERROR);
                } elseif (strpos($message, 'HTTP') !== false) {
                    $this->throwError(self::ERROR_HTTP_ERROR);
                } elseif (strpos($message, 'not found') !== false) {
                    $this->throwError(self::ERROR_NOT_FOUND);
                }
            }
            $this->throwError(self::ERROR_CONTENT_ERROR);
        } catch (URLAnalyzerException $e) {
            throw $e;
        } catch (Exception $e) {
            // Map generic exceptions to appropriate error types
            $message = $e->getMessage();
            if (strpos($message, 'DNS') !== false) {
                $this->throwError(self::ERROR_DNS_FAILURE);
            } elseif (strpos($message, 'CURL') !== false) {
                $this->throwError(self::ERROR_CONNECTION_ERROR);
            } elseif (strpos($message, 'HTTP') !== false) {
                $this->throwError(self::ERROR_HTTP_ERROR);
            } elseif (strpos($message, 'not found') !== false) {
                $this->throwError(self::ERROR_NOT_FOUND);
            } else {
                $this->throwError(self::ERROR_GENERIC_ERROR, $message);
            }
        }
    }

    /**
     * Fetch content from URL
     * Busca conteúdo da URL
     */
    private function fetchContent($url)
    {
        $curl = new Curl();

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            $this->throwError(self::ERROR_INVALID_URL);
        }
        $host = preg_replace('/^www\./', '', $host);
        $domainRules = $this->getDomainRules($host);

        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_MAXREDIRS, 2);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_DNS_SERVERS, implode(',', $this->dnsServers));
        $curl->setOpt(CURLOPT_ENCODING, '');
        
        // Additional anti-detection headers / Cabeçalhos anti-detecção adicionais
        $curl->setHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'DNT' => '1'
        ]);

        // Set Google bot specific headers / Definir cabeçalhos específicos do bot do Google
        if (isset($domainRules['fromGoogleBot'])) {
            $curl->setUserAgent($this->getRandomUserAgent(true));
            $curl->setHeaders([
                'X-Forwarded-For' => '66.249.' . rand(64, 95) . '.' . rand(1, 254),
                'From' => 'googlebot(at)googlebot.com'
            ]);
        }

        // Add domain-specific headers / Adicionar cabeçalhos específicos de domínio
        if (isset($domainRules['headers'])) {
            $curl->setHeaders($domainRules['headers']);
        }

        $curl->get($url);

        if ($curl->error) {
            $errorMessage = $curl->errorMessage;
            if (strpos($errorMessage, 'DNS') !== false) {
                $this->throwError(self::ERROR_DNS_FAILURE);
            } elseif (strpos($errorMessage, 'CURL') !== false) {
                $this->throwError(self::ERROR_CONNECTION_ERROR);
            } elseif ($curl->httpStatusCode === 404) {
                $this->throwError(self::ERROR_NOT_FOUND);
            } else {
                $this->throwError(self::ERROR_HTTP_ERROR);
            }
        }

        if ($curl->httpStatusCode !== 200 || empty($curl->response)) {
            $this->throwError(self::ERROR_HTTP_ERROR);
        }

        return $curl->response;
    }

    /**
     * Try to get content from Internet Archive's Wayback Machine
     * Tenta obter conteúdo do Wayback Machine do Internet Archive
     */
    private function fetchFromWaybackMachine($url)
    {
        $url = preg_replace('#^https?://#', '', $url);
        $availabilityUrl = "https://archive.org/wayback/available?url=" . urlencode($url);
        
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setUserAgent($this->getRandomUserAgent());

        $curl->get($availabilityUrl);

        if ($curl->error) {
            if (strpos($curl->errorMessage, 'DNS') !== false) {
                $this->throwError(self::ERROR_DNS_FAILURE);
            } elseif (strpos($curl->errorMessage, 'CURL') !== false) {
                $this->throwError(self::ERROR_CONNECTION_ERROR);
            } else {
                $this->throwError(self::ERROR_HTTP_ERROR);
            }
        }

        $data = $curl->response;
        if (!isset($data->archived_snapshots->closest->url)) {
            $this->throwError(self::ERROR_NOT_FOUND);
        }

        $archiveUrl = $data->archived_snapshots->closest->url;
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setUserAgent($this->getRandomUserAgent());

        $curl->get($archiveUrl);

        if ($curl->error || $curl->httpStatusCode !== 200 || empty($curl->response)) {
            $this->throwError(self::ERROR_HTTP_ERROR);
        }

        $content = $curl->response;
        
        // Remove Wayback Machine toolbar and cache URLs / Remover a barra de ferramentas do Wayback Machine e URLs de cache
        $content = preg_replace('/<!-- BEGIN WAYBACK TOOLBAR INSERT -->.*?<!-- END WAYBACK TOOLBAR INSERT -->/s', '', $content);
        $content = preg_replace('/https?:\/\/web\.archive\.org\/web\/\d+im_\//', '', $content);
        
        return $content;
    }

    /**
     * Try to get content using Selenium
     * Tenta obter conteúdo usando Selenium
     */
    private function fetchFromSelenium($url, $browser = 'firefox')
    {
        $host = 'http://'.SELENIUM_HOST.'/wd/hub';

        if ($browser === 'chrome') {
            $options = new ChromeOptions();
            $options->addArguments([
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-images',
                '--blink-settings=imagesEnabled=false'
            ]);
            
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        } else {
            $profile = new FirefoxProfile();
            $profile->setPreference("permissions.default.image", 2);
            $profile->setPreference("javascript.enabled", true);
            $profile->setPreference("network.http.referer.defaultPolicy", 0);
            $profile->setPreference("network.http.referer.defaultReferer", "https://www.google.com");
            $profile->setPreference("network.http.referer.spoofSource", true);
            $profile->setPreference("network.http.referer.trimmingPolicy", 0);

            $options = new FirefoxOptions();
            $options->setProfile($profile);

            $capabilities = DesiredCapabilities::firefox();
            $capabilities->setCapability(FirefoxOptions::CAPABILITY, $options);
        }

        try {
            $driver = RemoteWebDriver::create($host, $capabilities);
            $driver->manage()->timeouts()->pageLoadTimeout(10);
            $driver->manage()->timeouts()->setScriptTimeout(5);

            $driver->get($url);

            $htmlSource = $driver->executeScript("return document.documentElement.outerHTML;");

            $driver->quit();

            if (empty($htmlSource)) {
                $this->throwError(self::ERROR_CONTENT_ERROR);
            }

            return $htmlSource;
        } catch (Exception $e) {
            if (isset($driver)) {
                $driver->quit();
            }
            
            // Map Selenium errors to appropriate error types
            $message = $e->getMessage();
            if (strpos($message, 'DNS') !== false) {
                $this->throwError(self::ERROR_DNS_FAILURE);
            } elseif (strpos($message, 'timeout') !== false) {
                $this->throwError(self::ERROR_CONNECTION_ERROR);
            } elseif (strpos($message, 'not found') !== false) {
                $this->throwError(self::ERROR_NOT_FOUND);
            } else {
                $this->throwError(self::ERROR_HTTP_ERROR);
            }
        }
    }

    /**
     * Get specific rules for a domain
     * Obtém regras específicas para um domínio
     */
    private function getDomainRules($domain)
    {
        return $this->rules->getDomainRules($domain);
    }

    /**
     * Process HTML content applying domain rules
     * Processa conteúdo HTML aplicando regras de domínio
     */
    private function processContent($content, $host, $url)
    {
        if (strlen($content) < 5120) {
            $this->throwError(self::ERROR_CONTENT_ERROR);
        }

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Process canonical tags / Processar tags canônicas
        $canonicalLinks = $xpath->query("//link[@rel='canonical']");
        if ($canonicalLinks !== false) {
            foreach ($canonicalLinks as $link) {
                if ($link->parentNode) {
                    $link->parentNode->removeChild($link);
                }
            }
        }

        // Add new canonical tag / Adicionar nova tag canônica
        $head = $xpath->query('//head')->item(0);
        if ($head) {
            $newCanonical = $dom->createElement('link');
            $newCanonical->setAttribute('rel', 'canonical');
            $newCanonical->setAttribute('href', $url);
            $head->appendChild($newCanonical);
        }

        // Fix relative URLs / Corrigir URLs relativas
        $this->fixRelativeUrls($dom, $xpath, $url);

        $domainRules = $this->getDomainRules($host);

        // Apply domain rules / Aplicar regras de domínio
        if (isset($domainRules['customStyle'])) {
            $styleElement = $dom->createElement('style');
            $styleElement->appendChild($dom->createTextNode($domainRules['customStyle']));
            $dom->getElementsByTagName('head')[0]->appendChild($styleElement);
            $this->activatedRules[] = 'customStyle';
        }

        if (isset($domainRules['customCode'])) {
            $scriptElement = $dom->createElement('script');
            $scriptElement->setAttribute('type', 'text/javascript');
            $scriptElement->appendChild($dom->createTextNode($domainRules['customCode']));
            $dom->getElementsByTagName('body')[0]->appendChild($scriptElement);
        }

        // Remove unwanted elements / Remover elementos indesejados
        $this->removeUnwantedElements($dom, $xpath, $domainRules);

        // Clean inline styles / Limpar estilos inline
        $this->cleanInlineStyles($xpath);

        // Add Brand Bar / Adicionar barra de marca
        $this->addBrandBar($dom, $xpath);

        // Add debug panel / Adicionar painel de debug
        $this->addDebugBar($dom, $xpath);

        return $dom->saveHTML();
    }

    /**
     * Remove unwanted elements based on domain rules
     * Remove elementos indesejados com base nas regras de domínio
     */
    private function removeUnwantedElements($dom, $xpath, $domainRules)
    {
        if (isset($domainRules['classAttrRemove'])) {
            foreach ($domainRules['classAttrRemove'] as $class) {
                $elements = $xpath->query("//*[contains(@class, '$class')]");
                if ($elements !== false && $elements->length > 0) {
                    foreach ($elements as $element) {
                        $this->removeClassNames($element, [$class]);
                    }
                    $this->activatedRules[] = "classAttrRemove: $class";
                }
            }
        }

        if (isset($domainRules['removeElementsByTag'])) {
            $tagsToRemove = $domainRules['removeElementsByTag'];
            foreach ($tagsToRemove as $tag) {
                $tagElements = $xpath->query("//$tag");
                if ($tagElements !== false) {
                    foreach ($tagElements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "removeElementsByTag: $tag";
                }
            }
        }

        if (isset($domainRules['idElementRemove'])) {
            foreach ($domainRules['idElementRemove'] as $id) {
                $elements = $xpath->query("//*[@id='$id']");
                if ($elements !== false && $elements->length > 0) {
                    foreach ($elements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "idElementRemove: $id";
                }
            }
        }

        if (isset($domainRules['classElementRemove'])) {
            foreach ($domainRules['classElementRemove'] as $class) {
                $elements = $xpath->query("//*[contains(@class, '$class')]");
                if ($elements !== false && $elements->length > 0) {
                    foreach ($elements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "classElementRemove: $class";
                }
            }
        }

        if (isset($domainRules['scriptTagRemove'])) {
            foreach ($domainRules['scriptTagRemove'] as $script) {
                $scriptElements = $xpath->query("//script[contains(@src, '$script')] | //script[contains(text(), '$script')]");
                if ($scriptElements !== false && $scriptElements->length > 0) {
                    foreach ($scriptElements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "scriptTagRemove: $script";
                }

                $linkElements = $xpath->query("//link[@as='script' and contains(@href, '$script') and @type='application/javascript']");
                if ($linkElements !== false && $linkElements->length > 0) {
                    foreach ($linkElements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "scriptTagRemove: $script";
                }
            }
        }

        if (isset($domainRules['removeCustomAttr'])) {
            foreach ($domainRules['removeCustomAttr'] as $attrPattern) {
                if (strpos($attrPattern, '*') !== false) {
                    // For wildcard attributes (e.g. data-*) / Para atributos com wildcard (ex: data-*)
                    $elements = $xpath->query('//*');
                    if ($elements !== false) {
                        $pattern = '/^' . str_replace('*', '.*', $attrPattern) . '$/';
                        foreach ($elements as $element) {
                            if ($element->hasAttributes()) {
                                $attrs = [];
                                foreach ($element->attributes as $attr) {
                                    if (preg_match($pattern, $attr->name)) {
                                        $attrs[] = $attr->name;
                                    }
                                }
                                foreach ($attrs as $attr) {
                                    $element->removeAttribute($attr);
                                }
                            }
                        }
                        $this->activatedRules[] = "removeCustomAttr: $attrPattern";
                    }
            } else {
                    // For non-wildcard attributes / Para atributos sem wildcard
                    $elements = $xpath->query("//*[@$attrPattern]");
                    if ($elements !== false && $elements->length > 0) {
                        foreach ($elements as $element) {
                            $element->removeAttribute($attrPattern);
                        }
                        $this->activatedRules[] = "removeCustomAttr: $attrPattern";
                    }
                }
            }
        }
    }

    /**
     * Clean inline styles that might interfere with content visibility
     * Limpa estilos inline que podem interferir na visibilidade do conteúdo
     */
    private function cleanInlineStyles($xpath)
    {
        $elements = $xpath->query("//*[@style]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $style = $element->getAttribute('style');
                    $style = preg_replace('/(max-height|height|overflow|position|display|visibility)\s*:\s*[^;]+;?/', '', $style);
                    $element->setAttribute('style', $style);
                }
            }
        }
    }

    /**
     * Add Brand Bar CTA and debug panel
     * Adiciona CTA da marca e painel de debug
     */
    private function addBrandBar($dom, $xpath)
    {
        $body = $xpath->query('//body')->item(0);
        if ($body) {
            $brandDiv = $dom->createElement('div');
            $brandDiv->setAttribute('style', 'z-index: 99999; position: fixed; top: 0; right: 1rem; background: rgba(37,99,235, 0.9); backdrop-filter: blur(8px); color: #fff; font-size: 13px; line-height: 1em; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); padding: 8px 12px; margin: 0px; overflow: hidden; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; font-family: Tahoma, sans-serif;');
            $brandHtml = $dom->createDocumentFragment();
            $brandHtml->appendXML('<a href="'.SITE_URL.'" style="color: #fff; text-decoration: none; font-weight: bold;" target="_blank">'.htmlspecialchars(SITE_DESCRIPTION).'</a>');
            $brandDiv->appendChild($brandHtml);
            $body->appendChild($brandDiv);
        }
    }


    /**
     * Add debug panel if LOG_LEVEL is DEBUG
     * Adicionar painel de depuração se LOG_LEVEL for DEBUG
     */
    private function addDebugBar($dom, $xpath)
    {
        if (LOG_LEVEL === 'DEBUG') {
            $body = $xpath->query('//body')->item(0);
            if ($body) {
                $debugDiv = $dom->createElement('div');
                $debugDiv->setAttribute('style', 'position: fixed; bottom: 1rem; right: 1rem; max-width: 400px; padding: 1rem; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: auto; max-height: 80vh; z-index: 9999; font-family: monospace; font-size: 13px; line-height: 1.4;');
                
                if (empty($this->activatedRules)) {
                    $ruleElement = $dom->createElement('div');
                    $ruleElement->textContent = 'No rules activated / Nenhuma regra ativada';
                    $debugDiv->appendChild($ruleElement);
                } else {
                    foreach ($this->activatedRules as $rule) {
                        $ruleElement = $dom->createElement('div');
                        $ruleElement->textContent = $rule;
                        $debugDiv->appendChild($ruleElement);
                    }
                }

                $body->appendChild($debugDiv);
            }
        }
    }

    /**
     * Remove specific classes from an element
     * Remove classes específicas de um elemento
     */
    private function removeClassNames($element, $classesToRemove)
    {
        if (!$element->hasAttribute('class')) {
            return;
        }

        $classes = explode(' ', $element->getAttribute('class'));
        $newClasses = array_filter($classes, function ($class) use ($classesToRemove) {
            return !in_array(trim($class), $classesToRemove);
        });

        if (empty($newClasses)) {
            $element->removeAttribute('class');
        } else {
            $element->setAttribute('class', implode(' ', $newClasses));
        }
    }

    /**
     * Fix relative URLs in a DOM document
     * Corrige URLs relativas em um documento DOM
     */
    private function fixRelativeUrls($dom, $xpath, $baseUrl)
    {
        $parsedBase = parse_url($baseUrl);
        $baseHost = $parsedBase['scheme'] . '://' . $parsedBase['host'];

        $elements = $xpath->query("//*[@src]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $src = $element->getAttribute('src');
                    if (strpos($src, 'base64') !== false) {
                        continue;
                    }
                    if (strpos($src, 'http') !== 0 && strpos($src, '//') !== 0) {
                        $src = ltrim($src, '/');
                        $element->setAttribute('src', $baseHost . '/' . $src);
                    }
                }
            }
        }

        $elements = $xpath->query("//*[@href]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $href = $element->getAttribute('href');
                    if (strpos($href, 'mailto:') === 0 || 
                        strpos($href, 'tel:') === 0 || 
                        strpos($href, 'javascript:') === 0 || 
                        strpos($href, '#') === 0) {
                        continue;
                    }
                    if (strpos($href, 'http') !== 0 && strpos($href, '//') !== 0) {
                        $href = ltrim($href, '/');
                        $element->setAttribute('href', $baseHost . '/' . $href);
                    }
                }
            }
        }
    }
}
