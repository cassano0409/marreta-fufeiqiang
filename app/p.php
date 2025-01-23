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
            $redirectInfo = $analyzer->checkStatus($url);
            
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
            // Get error code from exception or default to 400
            // Obtém o código de erro da exceção ou usa 400 como padrão
            $statusCode = $e->getCode() ?: 400;
            
            // Map error codes to error types
            // Mapeia códigos de erro para tipos de erro
            switch ($statusCode) {
                case 400:
                    $errorType = 'INVALID_URL';
                    break;
                case 403:
                    $errorType = 'BLOCKED_DOMAIN';
                    // Extract redirect URL from error message if present
                    $parts = explode('|', $e->getMessage());
                    if (count($parts) > 1) {
                        header('Location: ' . trim($parts[1]) . '?message=' . $errorType);
                        exit;
                    }
                    break;
                case 404:
                    $errorType = 'NOT_FOUND';
                    break;
                case 502:
                    $errorType = 'HTTP_ERROR';
                    break;
                case 503:
                    $errorType = 'CONNECTION_ERROR';
                    break;
                case 504:
                    $errorType = 'DNS_FAILURE';
                    break;
                default:
                    $errorType = 'GENERIC_ERROR';
                    break;
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
