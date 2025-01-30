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
        $message = Language::getMessage($errorConfig['message_key'])['message'];
        if ($additionalInfo) {
            $message .= ': ' . $additionalInfo;
        }
        throw new URLAnalyzerException($message, $errorConfig['code'], $errorType, $additionalInfo);
    }
}
