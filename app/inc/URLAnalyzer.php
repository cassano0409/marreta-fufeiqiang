<?php

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
    private $fetch;
    private $process;
    private $error;
    private $utils;

    public function checkStatus($url)
    {
        return $this->utils->checkStatus($url);
    }

    public function __construct()
    {
        parent::__construct();
        $this->fetch = new URLAnalyzerFetch();
        $this->process = new URLAnalyzerProcess();
        $this->error = new URLAnalyzerError();
        $this->utils = new URLAnalyzerUtils();
    }

    public function analyze($url)
    {
        $this->activatedRules = [];

        // Get and process cached content if it exists
        if ($this->cache->exists($url)) {
            $rawContent = $this->cache->get($url);
            // Process the raw content in real-time
            return $this->process->processContent($rawContent, parse_url($url, PHP_URL_HOST), $url);
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            $this->error->throwError(self::ERROR_INVALID_URL, '');
        }
        $host = preg_replace('/^www\./', '', $host);

        if (in_array($host, BLOCKED_DOMAINS)) {
            Logger::getInstance()->logUrl($url, 'BLOCKED_DOMAIN');
            $this->error->throwError(self::ERROR_BLOCKED_DOMAIN, '');
        }

        $redirectInfo = $this->utils->checkStatus($url);
        if ($redirectInfo['httpCode'] !== 200) {
            Logger::getInstance()->logUrl($url, 'INVALID_STATUS_CODE', "HTTP {$redirectInfo['httpCode']}");
            if ($redirectInfo['httpCode'] === 404) {
                $this->error->throwError(self::ERROR_NOT_FOUND, '');
            } else {
                $this->error->throwError(self::ERROR_HTTP_ERROR, (string)$redirectInfo['httpCode']);
            }
        }

        try {
            $domainRules = $this->getDomainRules($host);
            $fetchStrategy = isset($domainRules['fetchStrategies']) ? $domainRules['fetchStrategies'] : null;

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

            $fetchStrategies = [
                ['method' => 'fetchContent', 'args' => [$url]],
                ['method' => 'fetchFromWaybackMachine', 'args' => [$url]],
                ['method' => 'fetchFromSelenium', 'args' => [$url, 'firefox']]
            ];

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
