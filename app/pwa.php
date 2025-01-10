<?php
/**
 * PWA Share Target Handler
 * 
 * This script handles the PWA (Progressive Web App) share target functionality.
 * It receives a URL parameter via GET request and performs a 301 permanent
 * redirect to the /p/{URL} endpoint. If no URL is provided, redirects to
 * the homepage.
 * 
 * Security measures:
 * - URL sanitization to prevent XSS attacks
 * - URL encoding to ensure proper parameter handling
 * 
 * Este script gerencia a funcionalidade de compartilhamento do PWA (Progressive Web App).
 * Ele recebe um parâmetro URL via requisição GET e realiza um redirecionamento
 * permanente 301 para o endpoint /p/{URL}. Se nenhuma URL for fornecida,
 * redireciona para a página inicial.
 * 
 * Medidas de segurança:
 * - Sanitização da URL para prevenir ataques XSS
 * - Codificação da URL para garantir o correto tratamento dos parâmetros
 */

require_once 'config.php';

// Get URL and text parameters from GET request
// Obtém os parâmetros URL e text da requisição GET
$url = $_GET['url'] ?? '';
$text = $_GET['text'] ?? '';

/**
 * Validates if a given URL is valid
 * Valida se uma URL fornecida é válida
 * 
 * @param string $url URL to validate / URL para validar
 * @return bool Returns true if URL is valid, false otherwise / Retorna true se a URL for válida, false caso contrário
 */
function isValidUrl($url) {
    // First sanitize the URL
    // Primeiro sanitiza a URL
    $sanitized_url = filter_var($url, FILTER_SANITIZE_URL);
    
    // Then validate it
    // Então valida
    return filter_var($sanitized_url, FILTER_VALIDATE_URL) !== false;
}

// Check URL parameter first
// Verifica primeiro o parâmetro URL
if (!empty($url) && isValidUrl($url)) {
    $redirect_url = $url;
} 
// If URL is not valid, check text parameter
// Se a URL não é válida, verifica o parâmetro text
elseif (!empty($text) && isValidUrl($text)) {
    $redirect_url = $text;
}
// If text is not a URL but contains content, try to extract URL from it
// Se o texto não é uma URL mas contém conteúdo, tenta extrair URL dele
elseif (!empty($text)) {
    if (preg_match('/https?:\/\/[^\s]+/', $text, $matches)) {
        $redirect_url = $matches[0];
    }
}

// If we have a valid URL, redirect to it
// Se temos uma URL válida, redireciona para ela
if (isset($redirect_url)) {
    // Sanitize URL to prevent XSS
    // Sanitiza a URL para prevenir XSS
    $redirect_url = htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8');
    
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: /p/' . urlencode($redirect_url));
    exit;
}

// If no valid URL found in either parameter, redirect to homepage
// Se nenhuma URL válida foi encontrada em nenhum dos parâmetros, redireciona para a página inicial
header('Location: /');
exit;