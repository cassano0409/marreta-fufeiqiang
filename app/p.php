<?php

/**
 * URL Processor
 * Processador de URLs
 * 
 * This file is responsible for:
 * - Receiving URLs through the /p/ path
 * - Validating URL format
 * - Processing content using URLAnalyzer
 * - Displaying processed content or redirecting in case of error
 * 
 * Este arquivo é responsável por:
 * - Receber URLs através do path /p/
 * - Validar o formato da URL
 * - Processar o conteúdo usando o URLAnalyzer
 * - Exibir o conteúdo processado ou redirecionar em caso de erro
 * 
 * Usage example / Exemplo de uso:
 * /p/https://exemplo.com
 */

require_once 'config.php';
require_once 'inc/URLAnalyzer.php';

// Extract URL from request path
// Extrai a URL do path da requisição
$path = $_SERVER['REQUEST_URI'];
$prefix = '/p/';

if (strpos($path, $prefix) === 0) {
    // Remove prefix and decode URL
    // Remove o prefixo e decodifica a URL
    $url = urldecode(substr($path, strlen($prefix)));

    // Validate URL format
    // Valida o formato da URL
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $analyzer = new URLAnalyzer();
        try {
            // Check for redirects
            // Verifica se há redirecionamentos
            $redirectInfo = $analyzer->checkRedirects($url);
            
            // If there's a redirect and the final URL is different
            // Se houver redirecionamento e a URL final for diferente
            if ($redirectInfo['hasRedirect'] && $redirectInfo['finalUrl'] !== $url) {
                // Redirect to final URL
                // Redireciona para a URL final
                header('Location: ' . SITE_URL . '/p/' . urlencode($redirectInfo['finalUrl']));
                exit;
            }

            // If there's no redirect or if already at final URL
            // Se não houver redirecionamento ou se já estiver na URL final
            // Try to analyze and process the URL
            // Tenta analisar e processar a URL
            $content = $analyzer->analyze($url);
            // Display processed content
            // Exibe o conteúdo processado
            echo $content;
            exit;
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $errorType = 'GENERIC_ERROR'; // Default error type / Tipo padrão de erro

            // Map error message to specific type
            // Mapeia a mensagem de erro para um tipo específico
            if (strpos($errorMessage, 'bloqueado') !== false || strpos($errorMessage, 'blocked') !== false) {
                $errorType = 'BLOCKED_DOMAIN';
            } elseif (strpos($errorMessage, 'DNS') !== false) {
                $errorType = 'DNS_FAILURE';
            } elseif (strpos($errorMessage, 'HTTP: 4') !== false || strpos($errorMessage, 'HTTP: 5') !== false) {
                $errorType = 'HTTP_ERROR';
            } elseif (strpos($errorMessage, 'CURL') !== false) {
                $errorType = 'CONNECTION_ERROR';
            } elseif (strpos($errorMessage, 'obter conteúdo') !== false || strpos($errorMessage, 'get content') !== false) {
                $errorType = 'CONTENT_ERROR';
            }

            // Redirect to home page with error message
            // Redireciona para a página inicial com mensagem de erro
            header('Location: /?message=' . $errorType);
            exit;
        }
    } else {
        // Invalid URL / URL inválida
        header('Location: /?message=INVALID_URL');
        exit;
    }
} else {
    // Invalid path / Path inválido
    header('Location: /?message=NOT_FOUND');
    exit;
}
