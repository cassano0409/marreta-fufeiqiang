<?php

/**
 * Processador de URLs
 * 
 * Este arquivo é responsável por:
 * - Receber URLs através do path /p/
 * - Validar o formato da URL
 * - Processar o conteúdo usando o URLAnalyzer
 * - Exibir o conteúdo processado ou redirecionar em caso de erro
 * 
 * Exemplo de uso:
 * /p/https://exemplo.com
 */

require_once 'config.php';
require_once 'inc/URLAnalyzer.php';

// Extrai a URL do path da requisição
$path = $_SERVER['REQUEST_URI'];
$prefix = '/p/';

if (strpos($path, $prefix) === 0) {
    // Remove o prefixo e decodifica a URL
    $url = urldecode(substr($path, strlen($prefix)));

    // Valida o formato da URL
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $analyzer = new URLAnalyzer();
        try {
            // Tenta analisar e processar a URL
            $content = $analyzer->analyze($url);
            // Exibe o conteúdo processado
            echo $content;
            exit;
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $errorType = 'GENERIC_ERROR'; // Tipo padrão de erro

            // Mapeia a mensagem de erro para um tipo específico
            if (strpos($errorMessage, 'bloqueado') !== false) {
                $errorType = 'BLOCKED_DOMAIN';
            } elseif (strpos($errorMessage, 'DNS') !== false) {
                $errorType = 'DNS_FAILURE';
            } elseif (strpos($errorMessage, 'HTTP: 4') !== false || strpos($errorMessage, 'HTTP: 5') !== false) {
                $errorType = 'HTTP_ERROR';
            } elseif (strpos($errorMessage, 'CURL') !== false) {
                $errorType = 'CONNECTION_ERROR';
            } elseif (strpos($errorMessage, 'obter conteúdo') !== false) {
                $errorType = 'CONTENT_ERROR';
            }

            // Redireciona para a página inicial com mensagem de erro
            header('Location: /?message=' . $errorType);
            exit;
        }
    } else {
        // URL inválida
        header('Location: /?message=INVALID_URL');
        exit;
    }
} else {
    // Path inválido
    header('Location: /?message=NOT_FOUND');
    exit;
}
