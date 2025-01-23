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
        } catch (URLAnalyzerException $e) {
            // Get error type and additional info from exception
            // Obtém o tipo de erro e informações adicionais da exceção
            $errorType = $e->getErrorType();
            $additionalInfo = $e->getAdditionalInfo();
            
            // Handle blocked domain with redirect URL
            // Trata domínio bloqueado com URL de redirecionamento
            if ($errorType === URLAnalyzer::ERROR_BLOCKED_DOMAIN && $additionalInfo) {
                header('Location: ' . trim($additionalInfo) . '?message=' . $errorType);
                exit;
            }

            // Redirect to home page with error message
            // Redireciona para a página inicial com mensagem de erro
            header('Location: /?message=' . $errorType);
            exit;
        } catch (Exception $e) {
            // Handle any other unexpected errors
            // Trata quaisquer outros erros inesperados
            header('Location: /?message=' . URLAnalyzer::ERROR_GENERIC_ERROR);
            exit;
        }
    } else {
        // Invalid URL / URL inválida
        header('Location: /?message=' . URLAnalyzer::ERROR_INVALID_URL);
        exit;
    }
} else {
    // Invalid path / Path inválido
    header('Location: /?message=' . URLAnalyzer::ERROR_NOT_FOUND);
    exit;
}
