<?php

/**
 * URL Analysis API
 * API para análise de URLs
 * 
 * This file implements a REST endpoint that receives URLs via GET
 * and returns processed results in JSON format.
 * 
 * Este arquivo implementa um endpoint REST que recebe URLs via GET
 * e retorna resultados processados em formato JSON.
 * 
 * Features / Funcionalidades:
 * - URL validation / Validação de URLs
 * - Content analysis / Análise de conteúdo
 * - Error handling / Tratamento de erros
 * - CORS support / Suporte a CORS
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/URLAnalyzer.php';
require_once __DIR__ . '/../inc/Language.php';

// Initialize language system with default language
// Inicializa o sistema de idiomas com o idioma padrão
Language::init(LANGUAGE);

// Set content type as JSON
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Enable CORS (Cross-Origin Resource Sharing)
// Habilita CORS (Cross-Origin Resource Sharing)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

/**
 * Function to send standardized JSON response
 * Função para enviar resposta JSON padronizada
 * 
 * @param array $data Data to be sent in response / Dados a serem enviados na resposta
 * @param int $statusCode HTTP status code / Código de status HTTP
 */
function sendResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    $response = [
        'status' => $statusCode
    ];

    if (isset($data['error'])) {
        $response['error'] = $data['error'];
    } else if (isset($data['url'])) {
        $response['url'] = $data['url'];
    }

    echo json_encode($response);
    exit;
}

// Get URL from Router
// Obtém a URL do Router
$url = isset($_GET['url']) ? urldecode($_GET['url']) : '';

// Basic URL validation
// Validação básica da URL
if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    sendResponse([
        'error' => [
            'type' => URLAnalyzer::ERROR_INVALID_URL,
            'message' => Language::getMessage('INVALID_URL')['message']
        ]
    ], 400);
}

try {
    // Instantiate URL analyzer
    // Instancia o analisador de URLs
    $analyzer = new URLAnalyzer();

    // Try to analyze the provided URL
    // Tenta analisar a URL fornecida
    $analyzer->analyze($url);

    // If analysis is successful, return the processed URL
    // Se a análise for bem-sucedida, retorna a URL processada
    sendResponse([
        'url' => SITE_URL . '/p/' . $url
    ], 200);
} catch (URLAnalyzerException $e) {
    // Get error details from the exception
    // Obtém detalhes do erro da exceção
    $errorType = $e->getErrorType();
    $additionalInfo = $e->getAdditionalInfo();
    
    // Add error header for better client-side handling
    // Adiciona header de erro para melhor tratamento no cliente
    header('X-Error-Type: ' . $errorType);
    if ($additionalInfo) {
        header('X-Error-Info: ' . $additionalInfo);
    }

    sendResponse([
        'error' => [
            'type' => $errorType,
            'message' => $e->getMessage(),
            'details' => $additionalInfo ?: null
        ]
    ], $e->getCode());
} catch (Exception $e) {
    // Handle any other unexpected errors
    // Trata quaisquer outros erros inesperados
    sendResponse([
        'error' => [
            'type' => URLAnalyzer::ERROR_GENERIC_ERROR,
            'message' => Language::getMessage('GENERIC_ERROR')['message']
        ]
    ], 500);
}
