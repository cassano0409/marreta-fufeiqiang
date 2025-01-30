<?php

namespace Inc\URLAnalyzer;

class URLAnalyzerException extends \Exception
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
