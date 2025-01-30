<?php
/**
 * Custom exceptions for URL analysis
 * Adds error type and extra details
 */

namespace Inc\URLAnalyzer;

class URLAnalyzerException extends \Exception
{
    /** @var string Error type from ERROR_* constants */
    private $errorType;
    
    /** @var string Extra error details */
    private $additionalInfo;

    /** Creates new exception with error details */
    public function __construct($message, $code, $errorType, $additionalInfo = '')
    {
        parent::__construct($message, $code);
        $this->errorType = $errorType;
        $this->additionalInfo = $additionalInfo;
    }

    /** Gets error type */
    public function getErrorType()
    {
        return $this->errorType;
    }

    /** Gets extra error details */
    public function getAdditionalInfo()
    {
        return $this->additionalInfo;
    }
}
