<?php

namespace Inc;

use Exception;
use \Hawk\Catcher;

/**
 * Error logging and monitoring class
 * Classe de monitoramento e registro de erros
 * 
 * This class implements error logging functionality using Hawk.so
 * for monitoring and tracking application errors.
 * 
 * Esta classe implementa funcionalidades de registro de erros usando Hawk.so
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
     * Private constructor to prevent direct instantiation
     * Construtor privado para prevenir instanciação direta
     * 
     * Initializes Hawk monitoring
     * Inicializa o monitoramento Hawk
     */
    private function __construct()
    {
        // Initialize Hawk
        // Inicializa o Hawk
        Catcher::init([
            'integrationToken' => HAWK_TOKEN,
        ]);
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
     * Logs an error with context
     * Registra um erro com contexto
     * 
     * @param string $message Error message / Mensagem de erro
     * @param array $context Additional context data / Dados adicionais de contexto
     * @param string $type Error type/category / Tipo/categoria do erro
     */
    public function error(string $message, array $context = [], string $type = 'WARNING'): void
    {
        // Log to Hawk
        // Registra no Hawk
        try {
            Catcher::get()->sendException(new Exception($message), [
                'type' => $type,
                'context' => $context
            ]);
        } catch (Exception $e) {
            // If Hawk fails, we already have file logging as backup
            // Se o Hawk falhar, já temos o log em arquivo como backup
            error_log("Failed to send error to Hawk / Falha ao enviar erro para o Hawk: " . $e->getMessage());
        }
    }

    /**
     * Logs a URL-specific error
     * Registra um erro específico de URL
     * 
     * @param string $url The URL that generated the error / A URL que gerou o erro
     * @param string $error_group Error group/category / Grupo/categoria do erro
     * @param string $message_error Additional error details / Detalhes adicionais do erro
     * @param string $type Error type/category / Tipo/categoria do erro
     */
    public function log(string $url, string $error_group, string $message_error = '', string $type = 'WARNING'): void
    {
        // If no Hawk token is configured, don't generate log
        // Se não houver token do Hawk configurado, não gera log
        if (empty(HAWK_TOKEN)) {
            return;
        }
        
        $this->error($error_group, [
            'url' => $url,
            'timestamp' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'message_error' => $message_error
        ], $type);
    }
}