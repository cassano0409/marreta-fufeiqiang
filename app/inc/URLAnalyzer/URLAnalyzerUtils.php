<?php
/**
 * URL analysis utilities
 * Checks status and redirects
 */

namespace Inc\URLAnalyzer;

use Curl\Curl;

class URLAnalyzerUtils extends URLAnalyzerBase
{
    /** Gets URL status and redirect info */
    public function checkStatus($url)
    {
        $curl = new Curl();
        $curl->setFollowLocation();
        $curl->setOpt(CURLOPT_TIMEOUT, 5);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_NOBODY, true);
        $curl->setOpt(CURLOPT_DNS_SERVERS, '8.8.8.8,8.4.4.8');
        $curl->setHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/W.X.Y.Z Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'DNT' => '1',
            'X-Forwarded-For' => '66.249.' . rand(64, 95) . '.' . rand(1, 254),
            'From' => 'googlebot(at)googlebot.com'
        ]);
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
}
