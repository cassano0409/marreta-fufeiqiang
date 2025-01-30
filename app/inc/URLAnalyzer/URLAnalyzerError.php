<?php

namespace Inc\URLAnalyzer;

use Inc\Language;

class URLAnalyzerError extends URLAnalyzerBase
{
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
