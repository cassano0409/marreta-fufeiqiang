<?php

namespace Inc;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Exception;

/**
 * Centralized logging system using Monolog
 * Implements file rotation and log level configuration
 * Supports multiple output handlers
 */
class Logger
{
    /** @var Logger|null Singleton instance */
    private static $instance = null;

    /** @var MonologLogger Core logging instance */
    private $logger;

    /** @var array Log level mapping */
    private $logLevels = [
        'DEBUG' => Level::Debug,
        'INFO' => Level::Info,
        'WARNING' => Level::Warning,
        'ERROR' => Level::Error,
        'CRITICAL' => Level::Critical
    ];

    /** 
     * Initializes logging system with file rotation
     * Configures log format and retention policy
     * Adds stderr output in DEBUG mode
     */
    private function __construct()
    {
        $this->logger = new MonologLogger('marreta');

        $handler = new RotatingFileHandler(
            __DIR__ . '/../logs/app.log',
            LOG_DAYS_TO_KEEP,
            $this->getLogLevel()
        );

        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat);
        $handler->setFormatter($formatter);

        $this->logger->pushHandler($handler);

        if (defined('LOG_LEVEL') && LOG_LEVEL === 'DEBUG') {
            $streamHandler = new StreamHandler('php://stderr', Level::Debug);
            $streamHandler->setFormatter($formatter);
            $this->logger->pushHandler($streamHandler);
        }
    }

    /** @return Logger Singleton instance */
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Determines active log level from configuration */
    private function getLogLevel(): Level
    {
        $configLevel = defined('LOG_LEVEL') ? LOG_LEVEL : 'WARNING';
        return $this->logLevels[$configLevel] ?? Level::Warning;
    }

    /**
     * Records log entry with context
     * @param string $message Log message
     * @param array $context Additional context data
     * @param string $level Log severity level
     */
    public function log(string $message, array $context = [], string $level = 'WARNING'): void
    {
        $logLevel = $this->logLevels[$level] ?? Level::Warning;
        $this->logger->log($logLevel, $message, $context);
    }

    /** Logs error message with context */
    public function error(string $message, array $context = []): void
    {
        $this->log($message, $context, 'ERROR');
    }

    /**
     * Logs URL-specific error details
     * @param string $url Problematic URL
     * @param string $error_group Error category
     * @param string $message_error Additional error details
     * @param string $level Log severity level
     */
    public function logUrl(string $url, string $error_group, string $message_error = '', string $level = 'WARNING'): void
    {
        $context = [
            'url' => $url,
            'timestamp' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'message_error' => $message_error
        ];
        
        $this->log($error_group, $context, $level);
    }
}