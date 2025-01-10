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

$url = $_GET['url'] ?? '';

if (!empty($url)) {
    // Sanitize URL to prevent XSS
    $url = filter_var($url, FILTER_SANITIZE_URL);
    $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: /p/' . urlencode($url));
    exit;
}

// If no URL provided, redirect to homepage
header('Location: /');
exit;