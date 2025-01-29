<?php

/**
 * Class for URL analysis and processing
 * URL analysis and cleaning
 * Content caching
 * DNS resolution
 * HTTP requests with multiple attempts
 * Content processing based on domain-specific rules
 * Wayback Machine support
 * Selenium extraction support
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
     * Helper method to throw errors
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
     * @var array List of User Agents
     */
    private $userAgents = [
        // Google News bot
        'Googlebot-News',
        // Mobile Googlebot
        'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/W.X.Y.Z Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        // Desktop Googlebot
        'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Chrome/W.X.Y.Z Safari/537.36'
    ];

    /**
     * @var array List of social media referrers
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
     * @var array List of DNS servers
     */
    private $dnsServers;

    /**
     * @var Rules Instance of rules class
     */
    private $rules;

    /**
     * @var Cache Instance of cache class
     */
    private $cache;

    /**
     * @var array List of activated rules
     */
    private $activatedRules = [];

    /**
     * Class constructor
     * Initializes dependencies
     */
    public function __construct()
    {
        $this->dnsServers = explode(',', DNS_SERVERS);
        $this->rules = new Rules();
        $this->cache = new Cache();
    }

    /**
     * Check if a URL has redirects and return the final URL
     * @param string $url URL to check redirects
     * @return array Array with final URL and if there was a redirect
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
     * @param bool $preferGoogleBot Whether to prefer Google bot user agents
     * @return string Selected user agent
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
     * @return string Selected referrer
     */
    private function getRandomSocialReferrer()
    {
        return $this->socialReferrers[array_rand($this->socialReferrers)];
    }

    /**
     * Main method for URL analysis
     * @param string $url URL to be analyzed
     * @return string Processed content
     * @throws URLAnalyzerException In case of processing errors
     */
    public function analyze($url)
    {
        // Reset activated rules for new analysis
        $this->activatedRules = [];

        // 1. Check cache
        if ($this->cache->exists($url)) {
            return $this->cache->get($url);
        }

        // 2. Check blocked domains
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
            // 4. Get domain rules and check fetch strategy
            $domainRules = $this->getDomainRules($host);
            $fetchStrategy = isset($domainRules['fetchStrategies']) ? $domainRules['fetchStrategies'] : null;

            // If a specific fetch strategy is defined, use only that
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

            // If all strategies failed
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
            // Map exceptions to error types
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
        
        // Additional anti-detection headers
        $curl->setHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'DNT' => '1'
        ]);

        // Set Google bot specific headers
        if (isset($domainRules['fromGoogleBot'])) {
            $curl->setUserAgent($this->getRandomUserAgent(true));
            $curl->setHeaders([
                'X-Forwarded-For' => '66.249.' . rand(64, 95) . '.' . rand(1, 254),
                'From' => 'googlebot(at)googlebot.com'
            ]);
        }

        // Add domain-specific headers
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
     * Try to get content from Wayback Machine
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
        
        // Remove Wayback Machine toolbar and cache URLs
        $content = preg_replace('/<!-- BEGIN WAYBACK TOOLBAR INSERT -->.*?<!-- END WAYBACK TOOLBAR INSERT -->/s', '', $content);
        $content = preg_replace('/https?:\/\/web\.archive\.org\/web\/\d+im_\//', '', $content);
        
        return $content;
    }

    /**
     * Try to get content using Selenium
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
     */
    private function getDomainRules($domain)
    {
        return $this->rules->getDomainRules($domain);
    }

    /**
     * Process HTML content applying domain rules
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

        // Process canonical tags
        $canonicalLinks = $xpath->query("//link[@rel='canonical']");
        if ($canonicalLinks !== false) {
            foreach ($canonicalLinks as $link) {
                if ($link->parentNode) {
                    $link->parentNode->removeChild($link);
                }
            }
        }

        // Add new canonical tag
        $head = $xpath->query('//head')->item(0);
        if ($head) {
            $newCanonical = $dom->createElement('link');
            $newCanonical->setAttribute('rel', 'canonical');
            $newCanonical->setAttribute('href', $url);
            $head->appendChild($newCanonical);
        }

        // Fix relative URLs
        $this->fixRelativeUrls($dom, $xpath, $url);

        $domainRules = $this->getDomainRules($host);

        // Apply domain rules
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

        // Remove unwanted elements
        $this->removeUnwantedElements($dom, $xpath, $domainRules);

        // Clean inline styles
        $this->cleanInlineStyles($xpath);

        // Add Brand bar
        $this->addBrandBar($dom, $xpath);

        // Add Debug panel
        $this->addDebugBar($dom, $xpath);

        return $dom->saveHTML();
    }

    /**
     * Remove unwanted elements based on domain rules
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
                    // For wildcard attributes (e.g. data-*)
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
                    // For non-wildcard attributes
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
     * Clean inline styles
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
     * Add Brand Bar in pages
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