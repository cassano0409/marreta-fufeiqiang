<?php
/**
 * URL analyzer with multiple fetch strategies and content processing
 * Handles caching, error handling, and domain-specific rules
 */

namespace Inc;

use Inc\Logger;
use Inc\URLAnalyzer\URLAnalyzerBase;
use Inc\URLAnalyzer\URLAnalyzerException;
use Inc\URLAnalyzer\URLAnalyzerFetch;
use Inc\URLAnalyzer\URLAnalyzerProcess;
use Inc\URLAnalyzer\URLAnalyzerError;
use Inc\URLAnalyzer\URLAnalyzerUtils;

class URLAnalyzer extends URLAnalyzerBase
{
    /** @var URLAnalyzerFetch Content fetcher */
    private $fetch;
    
    /** @var URLAnalyzerProcess Content processor */
    private $process;
    
    /** @var URLAnalyzerError Error handler */
    private $error;
    
    /** @var URLAnalyzerUtils URL utilities */
    private $utils;

    /** Gets URL status info */
    public function checkStatus($url)
    {
        return $this->utils->checkStatus($url);
    }

    /** Sets up analyzer components */
    public function __construct()
    {
        parent::__construct();
        $this->fetch = new URLAnalyzerFetch();
        $this->process = new URLAnalyzerProcess();
        $this->error = new URLAnalyzerError();
        $this->utils = new URLAnalyzerUtils();
    }

    /**
     * Analyzes URL and extracts content
     * Uses cache if available, otherwise fetches and processes
     */
    public function analyze($url)
    {
        // Reset activated rules for new analysis
        $this->activatedRules = [];

        // Try to get and process cached content first
        if ($this->cache->exists($url)) {
            $rawContent = $this->cache->get($url);
            // Process the raw content in real-time
            return $this->process->processContent($rawContent, parse_url($url, PHP_URL_HOST), $url);
        }

        // Extract and validate hostname
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            $this->error->throwError(self::ERROR_INVALID_URL, '');
        }
        $host = preg_replace('/^www\./', '', $host);

        // Check if domain is in blocked list
        if (in_array($host, BLOCKED_DOMAINS)) {
            Logger::getInstance()->logUrl($url, 'BLOCKED_DOMAIN');
            $this->error->throwError(self::ERROR_BLOCKED_DOMAIN, '');
        }

        // Check if domain has specific rules by looking for domain-specific configurations
        $hasCustomRules = $this->hasDomainRules($host);
        
        // Check HTTP status and handle any errors only if domain doesn't have custom rules
        if (!$hasCustomRules) {
            $redirectInfo = $this->utils->checkStatus($url);
            if ($redirectInfo['httpCode'] !== 200) {
                Logger::getInstance()->logUrl($url, 'INVALID_STATUS_CODE', "HTTP {$redirectInfo['httpCode']}");
                if ($redirectInfo['httpCode'] === 404) {
                    $this->error->throwError(self::ERROR_NOT_FOUND, '');
                } else {
                    $this->error->throwError(self::ERROR_HTTP_ERROR, (string)$redirectInfo['httpCode']);
                }
            }
        }

        try {
            // Get specific rules for this domain
            $domainRules = $this->getDomainRules($host);
            $fetchStrategy = isset($domainRules['fetchStrategies']) ? $domainRules['fetchStrategies'] : null;

            // Try domain-specific fetch strategy if available
            if ($fetchStrategy) {
                try {
                    $content = null;
                    switch ($fetchStrategy) {
                        case 'fetchContent':
                            $content = $this->fetch->fetchContent($url);
                            break;
                        case 'fetchFromWaybackMachine':
                            $content = $this->fetch->fetchFromWaybackMachine($url);
                            break;
                        case 'fetchFromSelenium':
                            $content = $this->fetch->fetchFromSelenium($url, isset($domainRules['browser']) ? $domainRules['browser'] : 'firefox');
                            break;
                    }

                    if (!empty($content)) {
                        $this->activatedRules[] = "fetchStrategy: $fetchStrategy";
                        // Cache the raw HTML content
                        $this->cache->set($url, $content);
                        // Process content in real-time
                        return $this->process->processContent($content, $host, $url);
                    }
                } catch (\Exception $e) {
                    Logger::getInstance()->logUrl($url, strtoupper($fetchStrategy) . '_ERROR', $e->getMessage());
                    throw $e;
                }
            }

            // Try all fetch strategies in order if no domain-specific strategy worked
            $fetchStrategies = [
                ['method' => 'fetchContent', 'args' => [$url]],
                ['method' => 'fetchFromWaybackMachine', 'args' => [$url]],
                ['method' => 'fetchFromSelenium', 'args' => [$url, 'firefox']]
            ];

            // Track last error for better error reporting
            $lastError = null;
            foreach ($fetchStrategies as $strategy) {
                try {
                    $content = call_user_func_array([$this->fetch, $strategy['method']], $strategy['args']);
                    if (!empty($content)) {
                        $this->activatedRules[] = "fetchStrategy: {$strategy['method']}";
                        // Cache the raw HTML content
                        $this->cache->set($url, $content);
                        // Process content in real-time
                        return $this->process->processContent($content, $host, $url);
                    }
                } catch (\Exception $e) {
                    $lastError = $e;
                    error_log("{$strategy['method']}_ERROR: " . $e->getMessage());
                    continue;
                }
            }

            Logger::getInstance()->logUrl($url, 'GENERAL_FETCH_ERROR');
            if ($lastError) {
                $message = $lastError->getMessage();
                if (strpos($message, 'DNS') !== false) {
                    $this->error->throwError(self::ERROR_DNS_FAILURE, '');
                } elseif (strpos($message, 'CURL') !== false) {
                    $this->error->throwError(self::ERROR_CONNECTION_ERROR, '');
                } elseif (strpos($message, 'HTTP') !== false) {
                    $this->error->throwError(self::ERROR_HTTP_ERROR, '');
                } elseif (strpos($message, 'not found') !== false) {
                    $this->error->throwError(self::ERROR_NOT_FOUND, '');
                }
            }
            $this->error->throwError(self::ERROR_CONTENT_ERROR, '');
        } catch (URLAnalyzerException $e) {
            throw $e;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (strpos($message, 'DNS') !== false) {
                $this->error->throwError(self::ERROR_DNS_FAILURE, '');
            } elseif (strpos($message, 'CURL') !== false) {
                $this->error->throwError(self::ERROR_CONNECTION_ERROR, '');
            } elseif (strpos($message, 'HTTP') !== false) {
                $this->error->throwError(self::ERROR_HTTP_ERROR, '');
            } elseif (strpos($message, 'not found') !== false) {
                $this->error->throwError(self::ERROR_NOT_FOUND, '');
            } else {
                $this->error->throwError(self::ERROR_GENERIC_ERROR, (string)$message);
            }
        }
    }
}
