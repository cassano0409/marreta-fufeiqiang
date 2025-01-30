<?php

namespace Inc\URLAnalyzer;

use Inc\Rules;
use Inc\Cache;
use Inc\Logger;
use Inc\Language;
use Curl\Curl;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Firefox\FirefoxOptions;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Chrome\ChromeOptions;

class URLAnalyzerBase
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
    protected $errorMap = [
        self::ERROR_INVALID_URL => ['code' => 400, 'message_key' => 'INVALID_URL'],
        self::ERROR_BLOCKED_DOMAIN => ['code' => 403, 'message_key' => 'BLOCKED_DOMAIN'],
        self::ERROR_NOT_FOUND => ['code' => 404, 'message_key' => 'NOT_FOUND'],
        self::ERROR_HTTP_ERROR => ['code' => 502, 'message_key' => 'HTTP_ERROR'],
        self::ERROR_CONNECTION_ERROR => ['code' => 503, 'message_key' => 'CONNECTION_ERROR'],
        self::ERROR_DNS_FAILURE => ['code' => 504, 'message_key' => 'DNS_FAILURE'],
        self::ERROR_CONTENT_ERROR => ['code' => 502, 'message_key' => 'CONTENT_ERROR'],
        self::ERROR_GENERIC_ERROR => ['code' => 500, 'message_key' => 'GENERIC_ERROR']
    ];

    protected $userAgents = [
        'Googlebot-News',
        'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/W.X.Y.Z Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Chrome/W.X.Y.Z Safari/537.36'
    ];

    protected $socialReferrers = [
        'https://t.co/',
        'https://www.twitter.com/',
        'https://www.facebook.com/',
        'https://www.linkedin.com/'
    ];

    protected $dnsServers;
    protected $rules;
    protected $cache;
    protected $activatedRules = [];

    public function __construct()
    {
        $this->dnsServers = explode(',', DNS_SERVERS);
        $this->rules = new Rules();
        $this->cache = new Cache();
    }

    protected function getRandomUserAgent($preferGoogleBot = false)
    {
        if ($preferGoogleBot && rand(0, 100) < 70) {
            return $this->userAgents[array_rand($this->userAgents)];
        }
        return $this->userAgents[array_rand($this->userAgents)];
    }

    protected function getRandomSocialReferrer()
    {
        return $this->socialReferrers[array_rand($this->socialReferrers)];
    }

    protected function getDomainRules($domain)
    {
        return $this->rules->getDomainRules($domain);
    }
}
