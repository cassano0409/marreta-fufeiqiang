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
        // Error handling with mapping to appropriate HTTP codes
        // Tratamento de erros com mapeamento para códigos HTTP apropriados
        $message = $e->getMessage();
        $statusCode = 400;
        $errorCode = 'GENERIC_ERROR';
        $errorMessage = Language::getMessage('GENERIC_ERROR');

        // Try to match the error message with known error types
        // Tenta corresponder a mensagem de erro com tipos de erro conhecidos
        $errorTypes = ['BLOCKED_DOMAIN', 'DNS_FAILURE', 'HTTP_ERROR', 'CONNECTION_ERROR', 'CONTENT_ERROR'];
        foreach ($errorTypes as $type) {
            $typeMessage = Language::getMessage($type);
            if (strpos($message, $typeMessage['message']) !== false) {
                $statusCode = ($typeMessage['type'] === 'error') ? 400 : 503;
                $errorCode = $type;
                $errorMessage = $typeMessage;
                break;
            }
        }

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
