<?php
/**
 * Standardized error handling for URL analysis
 * Converts errors to user-friendly messages
 */

namespace Inc\URLAnalyzer;

use Inc\Language;

class URLAnalyzerError extends URLAnalyzerBase
{
    /** Throws formatted exception with translated message */
    public function throwError($errorType, $additionalInfo = '')
    {
        $errorConfig = $this->errorMap[$errorType];
        
        // For DMCA domains, use custom message if provided, otherwise use default
        if ($errorType === self::ERROR_DMCA_DOMAIN && !empty($additionalInfo)) {
            $message = $additionalInfo;
        } else {
            $message = Language::getMessage($errorConfig['message_key'])['message'];
            if ($additionalInfo && $errorType !== self::ERROR_DMCA_DOMAIN) {
                $message .= ': ' . $additionalInfo;
            }
        }
        
        throw new URLAnalyzerException($message, $errorConfig['code'], $errorType, $additionalInfo);
    }
}
