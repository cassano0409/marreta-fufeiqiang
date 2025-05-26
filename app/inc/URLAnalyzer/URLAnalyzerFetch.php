<?php
/**
 * Fetches content using multiple strategies
 * Uses cURL, Wayback Machine, and Selenium
 */

namespace Inc\URLAnalyzer;

use Curl\Curl;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Chrome\ChromeOptions;

class URLAnalyzerFetch extends URLAnalyzerBase
{
    /** @var URLAnalyzerError Handler for throwing formatted errors */
    private $error;

    /**
     * Sets up the fetch handler with error handling capability
     */
    public function __construct()
    {
        parent::__construct();
        $this->error = new URLAnalyzerError();
    }

    /** 
     * Fetches content using cURL
     * Handles redirects and custom headers
     */
    /**
     * Modifies URL based on urlMods rules
     * @param string $url Original URL
     * @param array $domainRules Domain rules containing urlMods
     * @return string Modified URL
     */
    private function applyUrlModifications($url, $domainRules)
    {
        if (!isset($domainRules['urlMods'])) {
            return $url;
        }

        $urlParts = parse_url($url);
        
        // Handle query modifications
        if (isset($domainRules['urlMods']['query']) && is_array($domainRules['urlMods']['query'])) {
            $queryParams = [];
            
            // Parse existing query parameters if any
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $queryParams);
            }
            
            // Apply query modifications
            foreach ($domainRules['urlMods']['query'] as $queryMod) {
                if (isset($queryMod['key']) && isset($queryMod['value'])) {
                    $queryParams[$queryMod['key']] = $queryMod['value'];
                }
            }
            
            // Rebuild query string
            $urlParts['query'] = http_build_query($queryParams);
        }
        
        // Rebuild URL
        $modifiedUrl = '';
        
        if (isset($urlParts['scheme'])) {
            $modifiedUrl .= $urlParts['scheme'] . '://';
        }
        
        if (isset($urlParts['user'])) {
            $modifiedUrl .= $urlParts['user'];
            if (isset($urlParts['pass'])) {
                $modifiedUrl .= ':' . $urlParts['pass'];
            }
            $modifiedUrl .= '@';
        }
        
        if (isset($urlParts['host'])) {
            $modifiedUrl .= $urlParts['host'];
        }
        
        if (isset($urlParts['port'])) {
            $modifiedUrl .= ':' . $urlParts['port'];
        }
        
        if (isset($urlParts['path'])) {
            $modifiedUrl .= $urlParts['path'];
        }
        
        if (isset($urlParts['query'])) {
            $modifiedUrl .= '?' . $urlParts['query'];
        }
        
        if (isset($urlParts['fragment'])) {
            $modifiedUrl .= '#' . $urlParts['fragment'];
        }
        
        return $modifiedUrl;
    }

    public function fetchContent($url)
    {
        $curl = new Curl();

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            $this->error->throwError(self::ERROR_INVALID_URL);
        }
        $host = preg_replace('/^www\./', '', $host);
        $domainRules = $this->getDomainRules($host);
        
        // Apply URL modifications if any
        $url = $this->applyUrlModifications($url, $domainRules);

        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_MAXREDIRS, 2);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_DNS_SERVERS, implode(',', $this->dnsServers));
        $curl->setOpt(CURLOPT_ENCODING, '');

        $curl->setHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'DNT' => '1'
        ]);

        if (isset($domainRules['fromGoogleBot'])) {
            $curl->setUserAgent($this->getRandomUserAgent(true));
            $curl->setHeaders([
                'X-Forwarded-For' => '66.249.' . rand(64, 95) . '.' . rand(1, 254),
                'From' => 'googlebot(at)googlebot.com'
            ]);
        }

        if (isset($domainRules['headers'])) {
            $curl->setHeaders($domainRules['headers']);
        }

        $curl->get($url);

        if ($curl->error) {
            $errorMessage = $curl->errorMessage;
            if (strpos($errorMessage, 'DNS') !== false) {
                $this->error->throwError(self::ERROR_DNS_FAILURE);
            } elseif (strpos($errorMessage, 'CURL') !== false) {
                $this->error->throwError(self::ERROR_CONNECTION_ERROR);
            } elseif ($curl->httpStatusCode === 404) {
                $this->error->throwError(self::ERROR_NOT_FOUND);
            } else {
                $this->error->throwError(self::ERROR_HTTP_ERROR);
            }
        }

        if ($curl->httpStatusCode !== 200 || empty($curl->response)) {
            $this->error->throwError(self::ERROR_HTTP_ERROR);
        }

        return $curl->response;
    }

    /** 
     * Fetches from Wayback Machine archive
     * Used when direct access fails
     */
    public function fetchFromWaybackMachine($url)
    {
        // Apply URL modifications if any
        $domainHost = parse_url($url, PHP_URL_HOST);
        if ($domainHost) {
            $domainHost = preg_replace('/^www\./', '', $domainHost);
            $domainRules = $this->getDomainRules($domainHost);
            $url = $this->applyUrlModifications($url, $domainRules);
        }
        
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
                $this->error->throwError(self::ERROR_DNS_FAILURE);
            } elseif (strpos($curl->errorMessage, 'CURL') !== false) {
                $this->error->throwError(self::ERROR_CONNECTION_ERROR);
            } else {
                $this->error->throwError(self::ERROR_HTTP_ERROR);
            }
        }

        $data = $curl->response;
        if (!isset($data->archived_snapshots->closest->url)) {
            $this->error->throwError(self::ERROR_NOT_FOUND);
        }

        $archiveUrl = $data->archived_snapshots->closest->url;
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setUserAgent($this->getRandomUserAgent());

        $curl->get($archiveUrl);

        if ($curl->error || $curl->httpStatusCode !== 200 || empty($curl->response)) {
            $this->error->throwError(self::ERROR_HTTP_ERROR);
        }

        $content = $curl->response;

        $content = preg_replace('/<!-- BEGIN WAYBACK TOOLBAR INSERT -->.*?<!-- END WAYBACK TOOLBAR INSERT -->/s', '', $content);
        $content = preg_replace('/https?:\/\/web\.archive\.org\/web\/\d+im_\//', '', $content);

        return $content;
    }

    /** 
     * Fetches using Selenium for JS-heavy sites
     * Supports Firefox and Chrome
     */
    public function fetchFromSelenium($url, $browser = 'firefox')
    {
        $host = 'http://'.SELENIUM_HOST.'/wd/hub';
        
        // Apply URL modifications if any
        $domainHost = parse_url($url, PHP_URL_HOST);
        if ($domainHost) {
            $domainHost = preg_replace('/^www\./', '', $domainHost);
            $domainRules = $this->getDomainRules($domainHost);
            $url = $this->applyUrlModifications($url, $domainRules);
        }

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
                $this->error->throwError(self::ERROR_CONTENT_ERROR);
            }

            return $htmlSource;
        } catch (\Exception $e) {
            if (isset($driver)) {
                $driver->quit();
            }

            $message = $e->getMessage();
            if (strpos($message, 'DNS') !== false) {
                $this->error->throwError(self::ERROR_DNS_FAILURE);
            } elseif (strpos($message, 'timeout') !== false) {
                $this->error->throwError(self::ERROR_CONNECTION_ERROR);
            } elseif (strpos($message, 'not found') !== false) {
                $this->error->throwError(self::ERROR_NOT_FOUND);
            } else {
                $this->error->throwError(self::ERROR_HTTP_ERROR);
            }
        }
    }
}
