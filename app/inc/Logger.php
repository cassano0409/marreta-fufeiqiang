<?php

namespace Inc;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Exception;

/**
 * Error logging and monitoring class
 * Classe de monitoramento e registro de erros
 * 
 * This class implements error logging functionality using Monolog
 * for monitoring and tracking application errors.
 * 
 * Esta classe implementa funcionalidades de registro de erros usando Monolog
 * para monitoramento e rastreamento de erros da aplicação.
 */
class Logger
{
    /**
     * @var Logger|null Singleton instance
     * @var Logger|null Instância singleton
     */
    private static $instance = null;

    /**
     * @var MonologLogger Monolog logger instance
     * @var MonologLogger Instância do logger Monolog
     */
    private $logger;

    /**
     * @var array Log level mapping
     * @var array Mapeamento de níveis de log
     */
    private $logLevels = [
        'DEBUG' => \Monolog\Level::Debug,
        'INFO' => \Monolog\Level::Info,
        'WARNING' => \Monolog\Level::Warning,
        'ERROR' => \Monolog\Level::Error,
        'CRITICAL' => \Monolog\Level::Critical
    ];

    /**
     * Private constructor to prevent direct instantiation
     * Construtor privado para prevenir instanciação direta
     * 
     * Initializes Monolog logger with file rotation
     * Inicializa o logger Monolog com rotação de arquivos
     */
    private function __construct()
    {
        $this->logger = new MonologLogger('marreta');

        // Setup rotating file handler with 7 days retention
        // Configura manipulador de arquivo rotativo com retenção de 7 dias
        $handler = new RotatingFileHandler(
            __DIR__ . '/../logs/app.log',
            LOG_DAYS_TO_KEEP,
            $this->getLogLevel()
        );

        // Custom line format
        // Formato de linha personalizado
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat);
        $handler->setFormatter($formatter);

        $this->logger->pushHandler($handler);

        // If LOG_LEVEL is DEBUG, also log to stderr
        // Se LOG_LEVEL for DEBUG, também loga no stderr
        if (defined('LOG_LEVEL') && LOG_LEVEL === 'DEBUG') {
            $streamHandler = new StreamHandler('php://stderr', \Monolog\Level::Debug);
            $streamHandler->setFormatter($formatter);
            $this->logger->pushHandler($streamHandler);
        }
    }

    /**
     * Gets singleton instance
     * Obtém instância singleton
     * 
     * @return Logger Instance of Logger / Instância do Logger
     */
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Gets configured log level from environment or default
     * Obtém nível de log configurado do ambiente ou padrão
     * 
     * @return Level Monolog log level / Nível de log do Monolog
     */
    private function getLogLevel(): Level
    {
        $configLevel = defined('LOG_LEVEL') ? LOG_LEVEL : 'WARNING';
        return $this->logLevels[$configLevel] ?? Level::Warning;
    }

    /**
     * Logs a message with context at specified level
     * Registra uma mensagem com contexto no nível especificado
     * 
     * @param string $message Log message / Mensagem de log
     * @param array $context Additional context data / Dados adicionais de contexto
     * @param string $level Log level / Nível de log
     */
    public function log(string $message, array $context = [], string $level = 'WARNING'): void
    {
        $logLevel = $this->logLevels[$level] ?? \Monolog\Level::Warning;
        $this->logger->log($logLevel, $message, $context);
    }

    /**
     * Logs an error with context
     * Registra um erro com contexto
     * 
     * @param string $message Error message / Mensagem de erro
     * @param array $context Additional context data / Dados adicionais de contexto
     */
    public function error(string $message, array $context = []): void
    {
        $this->log($message, $context, 'ERROR');
    }

    /**
     * Logs a URL-specific error
     * Registra um erro específico de URL
     * 
     * @param string $url The URL that generated the error / A URL que gerou o erro
     * @param string $error_group Error group/category / Grupo/categoria do erro
     * @param string $message_error Additional error details / Detalhes adicionais do erro
     * @param string $level Log level / Nível de log
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
