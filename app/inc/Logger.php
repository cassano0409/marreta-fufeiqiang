<?php

namespace Inc;

use Exception;
use \Hawk\Catcher;

class Logger
{
    private static $instance = null;

    private function __construct()
    {
        // Inicializa o Hawk
        Catcher::init([
            'integrationToken' => HAWK_TOKEN,
        ]);
    }

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registra um erro com contexto
     * 
     * @param string $message Mensagem de erro
     * @param array $context Dados adicionais de contexto
     * @param string $type Tipo/categoria do erro
     */
    public function error(string $message, array $context = [], string $type = 'WARNING'): void
    {
        // Registra no Hawk
        try {
            Catcher::get()->sendException(new Exception($message), [
                'type' => $type,
                'context' => $context
            ]);
        } catch (Exception $e) {
            // Se o Hawk falhar, já temos o log em arquivo como backup
            error_log("Falha ao enviar erro para o Hawk: " . $e->getMessage());
        }
    }

    /**
     * Registra um erro específico de URL
     * 
     * @param string $url A URL que gerou o erro
     * @param string $error_group Grupo/categoria do erro
     * @param string $message_error Detalhes adicionais do erro
     * @param string $type Tipo/categoria do erro
     */
    public function log(string $url, string $error_group, string $message_error = '', string $type = 'WARNING'): void
    {
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