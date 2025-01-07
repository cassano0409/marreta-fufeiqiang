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

require_once 'Rules.php';
require_once 'Cache.php';
require_once 'Logger.php';
require_once 'Language.php';

use Curl\Curl;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Inc\Logger;

class URLAnalyzer
{
    /**
     * @var array List of available User Agents for requests
     * @var array Lista de User Agents disponíveis para requisições
     */
    private $userAgents;

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
        $this->userAgents = USER_AGENTS;
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
    public function checkRedirects($url)
    {
        $curl = new Curl();
        $curl->setFollowLocation();
        $curl->setOpt(CURLOPT_TIMEOUT, 5);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_NOBODY, true);
        $curl->setUserAgent($this->userAgents[array_rand($this->userAgents)]);
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
     * Main method for URL analysis
     * Método principal para análise de URLs
     * 
     * @param string $url URL to be analyzed / URL a ser analisada
     * @return string Processed content / Conteúdo processado
     * @throws Exception In case of processing errors / Em caso de erros durante o processamento
     */
    public function analyze($url)
    {
        // Reset activated rules for new analysis
        // Reset das regras ativadas para nova análise
        $this->activatedRules = [];

        // 1. Clean URL / Limpa a URL
        $cleanUrl = $this->cleanUrl($url);
        if (!$cleanUrl) {
            throw new Exception(Language::getMessage('INVALID_URL')['message']);
        }

        // 2. Check cache / Verifica cache
        if ($this->cache->exists($cleanUrl)) {
            return $this->cache->get($cleanUrl);
        }

        // 3. Check blocked domains / Verifica domínios bloqueados
        $host = parse_url($cleanUrl, PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host);

        if (in_array($host, BLOCKED_DOMAINS)) {
            throw new Exception(Language::getMessage('BLOCKED_DOMAIN')['message']);
        }

        // 4. Check if should use Selenium / Verifica se deve usar Selenium
        $domainRules = $this->getDomainRules($host);
        if (isset($domainRules['useSelenium']) && $domainRules['useSelenium'] === true) {
            try {
                $content = $this->fetchFromSelenium($cleanUrl, isset($domainRules['browser']) ? $domainRules['browser'] : 'firefox');
                if (!empty($content)) {
                    $processedContent = $this->processContent($content, $host, $cleanUrl);
                    $this->cache->set($cleanUrl, $processedContent);
                    return $processedContent;
                }
            } catch (Exception $e) {
                Logger::getInstance()->log($cleanUrl, 'SELENIUM_ERROR', $e->getMessage());
                throw new Exception(Language::getMessage('CONTENT_ERROR')['message']);
            }
        }

        // 5. Try to fetch content directly / Tenta buscar conteúdo diretamente
        try {
            $content = $this->fetchContent($cleanUrl);
            if (!empty($content)) {
                $processedContent = $this->processContent($content, $host, $cleanUrl);
                $this->cache->set($cleanUrl, $processedContent);
                return $processedContent;
            }
        } catch (Exception $e) {
            error_log("DIRECT_FETCH_ERROR: " . $e->getMessage());
        }

        // 6. Try to fetch from Wayback Machine as fallback / Tenta buscar do Wayback Machine como fallback
        try {
            $content = $this->fetchFromWaybackMachine($cleanUrl);
            if (!empty($content)) {
                $processedContent = $this->processContent($content, $host, $cleanUrl);
                $this->cache->set($cleanUrl, $processedContent);
                return $processedContent;
            }
        } catch (Exception $e) {
            error_log("WAYBACK_FETCH_ERROR: " . $e->getMessage());
        }

        // 7. Try to fetch with Selenium as fallback / Tenta buscar com Selenium como fallback
        try {
            $content = $this->fetchFromSelenium($cleanUrl, 'firefox');
            if (!empty($content)) {
                $processedContent = $this->processContent($content, $host, $cleanUrl);
                $this->cache->set($cleanUrl, $processedContent);
                return $processedContent;
            }
        } catch (Exception $e) {
            error_log("SELENIUM_ERROR: " . $e->getMessage());
        }

        Logger::getInstance()->log($cleanUrl, 'GENERAL_FETCH_ERROR');
        throw new Exception(Language::getMessage('CONTENT_ERROR')['message']);
    }

    /**
     * Try to get content using Selenium
     * Tenta obter o conteúdo da URL usando Selenium
     * 
     * @param string $url URL to fetch / URL para buscar
     * @param string $browser Browser to use (chrome/firefox) / Navegador a ser usado (chrome/firefox)
     * @return string|null HTML content of the page / Conteúdo HTML da página
     * @throws Exception In case of request error / Em caso de erro na requisição
     */
    private function fetchFromSelenium($url, $browser)
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
            $profile->setPreference("permissions.default.image", 2); // Don't load images / Não carrega imagens
            $profile->setPreference("javascript.enabled", true); // Keep JavaScript enabled / Mantém JavaScript habilitado
            $profile->setPreference("network.http.referer.defaultPolicy", 0); // Always send referer / Sempre envia referer
            $profile->setPreference("network.http.referer.defaultReferer", "https://www.google.com.br"); // Set default referer / Define referer padrão
            $profile->setPreference("network.http.referer.spoofSource", true); // Allow referer spoofing / Permite spoofing do referer
            $profile->setPreference("network.http.referer.trimmingPolicy", 0); // Don't trim referer / Não corta o referer

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
                throw new Exception("Selenium returned empty content / Selenium retornou conteúdo vazio");
            }

            return $htmlSource;
        } catch (Exception $e) {
            if (isset($driver)) {
                $driver->quit();
            }
            throw $e;
        }
    }

    /**
     * Try to get content from Internet Archive's Wayback Machine
     * Tenta obter o conteúdo da URL do Internet Archive's Wayback Machine
     * 
     * @param string $url Original URL / URL original
     * @return string|null Archive content / Conteúdo do arquivo
     * @throws Exception In case of request error / Em caso de erro na requisição
     */
    private function fetchFromWaybackMachine($url)
    {
        $cleanUrl = preg_replace('#^https?://#', '', $url);
        $availabilityUrl = "https://archive.org/wayback/available?url=" . urlencode($cleanUrl);
        
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setUserAgent($this->userAgents[array_rand($this->userAgents)]);

        $curl->get($availabilityUrl);

        if ($curl->error || $curl->httpStatusCode !== 200) {
            throw new Exception(Language::getMessage('HTTP_ERROR')['message']);
        }

        $data = $curl->response;
        if (!isset($data->archived_snapshots->closest->url)) {
            throw new Exception(Language::getMessage('CONTENT_ERROR')['message']);
        }

        $archiveUrl = $data->archived_snapshots->closest->url;
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setUserAgent($this->userAgents[array_rand($this->userAgents)]);

        $curl->get($archiveUrl);

        if ($curl->error || $curl->httpStatusCode !== 200 || empty($curl->response)) {
            throw new Exception(Language::getMessage('HTTP_ERROR')['message']);
        }

        $content = $curl->response;
        
        // Remove Wayback Machine toolbar and cache URLs
        // Remove o toolbar do Wayback Machine e URLs de cache
        $content = preg_replace('/<!-- BEGIN WAYBACK TOOLBAR INSERT -->.*?<!-- END WAYBACK TOOLBAR INSERT -->/s', '', $content);
        $content = preg_replace('/https?:\/\/web\.archive\.org\/web\/\d+im_\//', '', $content);
        
        return $content;
    }

    /**
     * Perform HTTP request using Curl Class
     * Realiza requisição HTTP usando Curl Class
     * 
     * @param string $url URL for request / URL para requisição
     * @return string Page content / Conteúdo da página
     * @throws Exception In case of request error / Em caso de erro na requisição
     */
    private function fetchContent($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host);
        $domainRules = $this->getDomainRules($host);

        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_MAXREDIRS, 2);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_DNS_SERVERS, implode(',', $this->dnsServers));
        
        // Set User Agent / Define User Agent
        $userAgent = isset($domainRules['userAgent']) 
            ? $domainRules['userAgent'] 
            : $this->userAgents[array_rand($this->userAgents)];
        $curl->setUserAgent($userAgent);

        // Default headers / Headers padrão
        $headers = [
            'Host' => $host,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache'
        ];

        // Add domain-specific headers / Adiciona headers específicos do domínio
        if (isset($domainRules['headers'])) {
            $headers = array_merge($headers, $domainRules['headers']);
        }
        $curl->setHeaders($headers);

        // Add domain-specific cookies / Adiciona cookies específicos do domínio
        if (isset($domainRules['cookies'])) {
            $cookies = [];
            foreach ($domainRules['cookies'] as $name => $value) {
                if ($value !== null) {
                    $cookies[] = $name . '=' . $value;
                }
            }
            if (!empty($cookies)) {
                $curl->setHeader('Cookie', implode('; ', $cookies));
            }
        }

        // Add referer if specified / Adiciona referer se especificado
        if (isset($domainRules['referer'])) {
            $curl->setHeader('Referer', $domainRules['referer']);
        }

        $curl->get($url);

        if ($curl->error || $curl->httpStatusCode !== 200) {
            throw new Exception(Language::getMessage('HTTP_ERROR')['message']);
        }

        if (empty($curl->response)) {
            throw new Exception(Language::getMessage('CONTENT_ERROR')['message']);
        }

        return $curl->response;
    }

    /**
     * Clean and normalize a URL
     * Limpa e normaliza uma URL
     * 
     * @param string $url URL to clean / URL para limpar
     * @return string|false Cleaned and normalized URL or false if invalid / URL limpa e normalizada ou false se inválida
     */
    private function cleanUrl($url)
    {
        $url = trim($url);

        // Check if URL is valid / Verifica se a URL é válida
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Detect and convert AMP URLs / Detecta e converte URLs AMP
        if (preg_match('#https://([^.]+)\.cdn\.ampproject\.org/v/s/([^/]+)(.*)#', $url, $matches)) {
            $url = 'https://' . $matches[2] . $matches[3];
        }

        // Split URL into component parts / Separa a URL em suas partes componentes
        $parts = parse_url($url);
        
        // Rebuild base URL / Reconstrói a URL base
        $cleanedUrl = $parts['scheme'] . '://' . $parts['host'];
        
        // Add path if exists / Adiciona o caminho se existir
        if (isset($parts['path'])) {
            $cleanedUrl .= $parts['path'];
        }
        
        return $cleanedUrl;
    }

    /**
     * Get specific rules for a domain
     * Obtém regras específicas para um domínio
     * 
     * @param string $domain Domain to search rules / Domínio para buscar regras
     * @return array|null Domain rules or null if not found / Regras do domínio ou null se não encontrar
     */
    private function getDomainRules($domain)
    {
        return $this->rules->getDomainRules($domain);
    }

    /**
     * Remove specific classes from an element
     * Remove classes específicas de um elemento
     * 
     * @param DOMElement $element DOM Element / Elemento DOM
     * @param array $classesToRemove Classes to remove / Classes a serem removidas
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
     * 
     * @param DOMDocument $dom DOM Document / Documento DOM
     * @param DOMXPath $xpath XPath Object / Objeto XPath
     * @param string $baseUrl Base URL for correction / URL base para correção
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

    /**
     * Process HTML content applying domain rules
     * Processa o conteúdo HTML aplicando regras do domínio
     * 
     * @param string $content HTML content / Conteúdo HTML
     * @param string $host Host name / Nome do host
     * @param string $url Complete URL / URL completa
     * @return string Processed content / Conteúdo processado
     */
    private function processContent($content, $host, $url)
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Process canonical tags / Processa tags canônicas
        $canonicalLinks = $xpath->query("//link[@rel='canonical']");
        if ($canonicalLinks !== false) {
            foreach ($canonicalLinks as $link) {
                if ($link->parentNode) {
                    $link->parentNode->removeChild($link);
                }
            }
        }

        // Add new canonical tag with original URL / Adiciona nova tag canônica com a URL original
        $head = $xpath->query('//head')->item(0);
        if ($head) {
            $newCanonical = $dom->createElement('link');
            $newCanonical->setAttribute('rel', 'canonical');
            $newCanonical->setAttribute('href', $url);
            $head->appendChild($newCanonical);
        }

        // Always fix relative URLs / Sempre corrige URLs relativas
        $this->fixRelativeUrls($dom, $xpath, $url);

        $domainRules = $this->getDomainRules($host);
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
                // Search for script tags with src or content containing the script
                // Busca por tags script com src ou conteúdo contendo o script
                $scriptElements = $xpath->query("//script[contains(@src, '$script')] | //script[contains(text(), '$script')]");
                if ($scriptElements !== false && $scriptElements->length > 0) {
                    foreach ($scriptElements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "scriptTagRemove: $script";
                }

                // Search for link tags that are scripts
                // Busca por tags link que são scripts
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

        // Add Marreta CTA / Adiciona CTA Marreta
        $body = $xpath->query('//body')->item(0);
        if ($body) {
            $marretaDiv = $dom->createElement('div');
            $marretaDiv->setAttribute('style', 'z-index: 99999; position: fixed; top: 0; right: 4px; background: rgb(37,99,235); color: #fff; font-size: 13px; line-height: 1em; padding: 6px; margin: 0px; overflow: hidden; border-bottom-left-radius: 3px; border-bottom-right-radius: 3px; font-family: Tahoma, sans-serif;');
            $marretaHtml = $dom->createDocumentFragment();
            $marretaHtml->appendXML('Chapéu de paywall é <a href="'.SITE_URL.'" style="color: #fff; text-decoration: underline; font-weight: bold;" target="_blank">Marreta</a>!');
            $marretaDiv->appendChild($marretaHtml);
            $body->appendChild($marretaDiv);

            // Add debug panel if DEBUG is true / Adiciona painel de debug se DEBUG for true
            if (DEBUG) {
                $debugDiv = $dom->createElement('div');
                $debugDiv->setAttribute('style', 'z-index: 99999; position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: #fff; font-size: 13px; line-height: 1.4; padding: 10px; border-radius: 3px; font-family: monospace; max-height: 200px; overflow-y: auto;');
                
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

        return $dom->saveHTML();
    }
}
