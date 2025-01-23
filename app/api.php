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

require_once 'config.php';
require_once 'inc/URLAnalyzer.php';
require_once 'inc/Language.php';

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

// Get request URL from path
// Obtém a URL da requisição a partir do path
$path = $_SERVER['REQUEST_URI'];
$prefix = '/api/';

if (strpos($path, $prefix) === 0) {
    $url = urldecode(substr($path, strlen($prefix)));

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

    // Basic URL validation
    // Validação básica da URL
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        $errorMessage = Language::getMessage('INVALID_URL');
        sendResponse([
            'error' => [
                'code' => 'INVALID_URL',
                'message' => $errorMessage['message']
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
    } catch (Exception $e) {
        // Get error code from exception or default to 400
        // Obtém o código de erro da exceção ou usa 400 como padrão
        $statusCode = $e->getCode() ?: 400;
        $message = $e->getMessage();
        
        // Map error codes to error types
        // Mapeia códigos de erro para tipos de erro
        switch ($statusCode) {
            case 400:
                $errorCode = 'INVALID_URL';
                break;
            case 403:
                $errorCode = 'BLOCKED_DOMAIN';
                break;
            case 404:
                $errorCode = 'NOT_FOUND';
                break;
            case 502:
                $errorCode = 'HTTP_ERROR';
                break;
            case 503:
                $errorCode = 'CONNECTION_ERROR';
                break;
            case 504:
                $errorCode = 'DNS_FAILURE';
                break;
            default:
                $errorCode = 'GENERIC_ERROR';
                break;
        }
        
        $errorMessage = Language::getMessage($errorCode);

        // Add error header for better client-side handling
        // Adiciona header de erro para melhor tratamento no cliente
        header('X-Error-Message: ' . $message);

        sendResponse([
            'error' => [
                'code' => $errorCode,
                'message' => $errorMessage['message']
            ]
        ], $statusCode);
    }
} else {
    // Return 404 error for endpoints not found
    // Retorna erro 404 para endpoints não encontrados
    $errorMessage = Language::getMessage('NOT_FOUND');
    sendResponse([
        'error' => [
            'code' => 'NOT_FOUND',
            'message' => $errorMessage['message']
        ]
    ], 404);
}
