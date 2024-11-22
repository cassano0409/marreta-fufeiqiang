<?php
/**
 * API para análise de URLs
 * 
 * Este arquivo implementa um endpoint REST que recebe URLs via GET
 * e retorna resultados processados em formato JSON.
 * 
 * Funcionalidades:
 * - Validação de URLs
 * - Análise de conteúdo
 * - Tratamento de erros
 * - Suporte a CORS
 */

require_once 'config.php';
require_once 'inc/URLAnalyzer.php';

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Habilita CORS (Cross-Origin Resource Sharing)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Obtém a URL da requisição a partir do path
$path = $_SERVER['REQUEST_URI'];
$prefix = '/api/';

if (strpos($path, $prefix) === 0) {
    $url = urldecode(substr($path, strlen($prefix)));
    
    /**
     * Função para enviar resposta JSON padronizada
     * 
     * @param array $data Dados a serem enviados na resposta
     * @param int $statusCode Código de status HTTP
     */
    function sendResponse($data, $statusCode = 200) {
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

    // Validação básica da URL
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        sendResponse([
            'error' => [
                'code' => 'INVALID_URL',
                'message' => MESSAGES['INVALID_URL']['message']
            ]
        ], 400);
    }

    try {
        // Instancia o analisador de URLs
        $analyzer = new URLAnalyzer();
        
        // Tenta analisar a URL fornecida
        $analyzer->analyze($url);
        
        // Se a análise for bem-sucedida, retorna a URL processada
        sendResponse([
            'url' => SITE_URL . '/p/' . $url
        ], 200);

    } catch (Exception $e) {
        // Tratamento de erros com mapeamento para códigos HTTP apropriados
        $message = $e->getMessage();
        $statusCode = 400;
        $errorCode = 'GENERIC_ERROR';
        
        // Mapeia a mensagem de erro para o código e status apropriados
        foreach (MESSAGES as $key => $value) {
            if (strpos($message, $value['message']) !== false) {
                $statusCode = ($value['type'] === 'error') ? 400 : 503;
                $errorCode = $key;
                break;
            }
        }
        
        // Adiciona header de erro para melhor tratamento no cliente
        header('X-Error-Message: ' . $message);
        
        sendResponse([
            'error' => [
                'code' => $errorCode,
                'message' => $message
            ]
        ], $statusCode);
    }
} else {
    // Retorna erro 404 para endpoints não encontrados
    sendResponse([
        'error' => [
            'code' => 'NOT_FOUND',
            'message' => MESSAGES['NOT_FOUND']['message']
        ]
    ], 404);
}
