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
 * Usage examples / Exemplos de uso:
 * /p/https://exemplo.com
 * /p/?url=https://exemplo.com
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/URLAnalyzer.php';

// Get URL from either path parameter or query string
// Obtém a URL do parâmetro de path ou query string
$url = '';
if (isset($_GET['url'])) {
    $url = urldecode($_GET['url']);
}

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
