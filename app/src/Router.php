<?php

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute;

/**
 * Classe Router - Gerenciador de rotas da aplicação
 * Router Class - Application route manager
 * 
 * Esta classe implementa o sistema de roteamento usando FastRoute para:
 * - Gerenciar todas as rotas da aplicação
 * - Processar requisições HTTP
 * - Direcionar para os manipuladores apropriados
 * 
 * This class implements the routing system using FastRoute to:
 * - Manage all application routes
 * - Process HTTP requests
 * - Direct to appropriate handlers
 */
class Router
{
    /**
     * Instância do dispatcher do FastRoute
     * FastRoute dispatcher instance
     */
    private $dispatcher;

    /**
     * Construtor - Inicializa as rotas da aplicação
     * Constructor - Initializes application routes
     */
    public function __construct()
    {
        $this->dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
            // Rota principal - página inicial
            // Main route - home page
            $r->addRoute(['GET','POST'], '/', function() {
                // Inicialização das variáveis para a view principal
                // Initialize variables for the main view
                require_once __DIR__ . '/../config.php';
                require_once __DIR__ . '/../inc/Cache.php';
                require_once __DIR__ . '/../inc/Language.php';

                \Language::init(LANGUAGE);
                
                $message = '';
                $message_type = '';
                $url = '';
                
                // Sanitize and process query string messages
                if (isset($_GET['message'])) {
                    $message_key = htmlspecialchars(trim($_GET['message']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $messageData = \Language::getMessage($message_key);
                    $message = htmlspecialchars($messageData['message'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $message_type = htmlspecialchars($messageData['type'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                
                // Process form submission
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
                    $url = $this->sanitizeUrl($_POST['url']);
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        header('Location: ' . SITE_URL . '/p/' . $url);
                        exit;
                    } else {
                        $messageData = \Language::getMessage('INVALID_URL');
                        $message = $messageData['message'];
                        $message_type = $messageData['type'];
                    }
                }
                
                // Inicializa o cache para contagem
                // Initialize cache for counting
                $cache = new \Cache();
                $cache_folder = $cache->getCacheFileCount();
                
                require __DIR__ . '/views/home.php';
            });

            // Rota da API - usa URLProcessor em modo API
            // API route - uses URLProcessor in API mode
            $r->addRoute('GET', '/api/{url:.+}', function($vars) {
                $processor = new URLProcessor($this->sanitizeUrl($vars['url']), true);
                $processor->process();
            });

            // Rota da API sem parâmetros - redireciona para raiz
            // API route without parameters - redirects to root
            $r->addRoute('GET', '/api[/]', function() {
                header('Location: /');
                exit;
            });

            // Rota de processamento - usa URLProcessor em modo web
            // Processing route - uses URLProcessor in web mode
            $r->addRoute('GET', '/p/{url:.+}', function($vars) {
                $processor = new URLProcessor($this->sanitizeUrl($vars['url']), false);
                $processor->process();
            });
            
            // Processing route with query parameter or without parameters
            $r->addRoute('GET', '/p[/]', function() {
                if (isset($_GET['url']) || isset($_GET['text'])) {
                    // Sanitize input parameters
                    $url = isset($_GET['url']) ? $this->sanitizeUrl($_GET['url']) : '';
                    $text = isset($_GET['text']) ? $this->sanitizeUrl($_GET['text']) : '';
                    
                    // Check which parameter is a valid URL
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        header('Location: /p/' . $url);
                        exit;
                    } elseif (filter_var($text, FILTER_VALIDATE_URL)) {
                        header('Location: /p/' . $text);
                        exit;
                    } else {
                        header('Location: /?message=INVALID_URL');
                        exit;
                    }
                }
                header('Location: /');
                exit;
            });

            // Rota do manifesto PWA - inclui manifest.php existente
            // PWA manifest route - includes existing manifest.php
            $r->addRoute('GET', '/manifest.json', function() {
                require __DIR__ . '/views/manifest.php';
            });
        });
    }

    /**
     * Sanitizes URLs to prevent XSS and injection attacks
     * Sanitiza URLs para prevenir ataques XSS e injeções
     * 
     * @param string $url The URL to sanitize
     * @return string The sanitized URL
     */
    /**
     * Sanitizes and normalizes URLs
     * Sanitiza e normaliza URLs
     * 
     * @param string $url The URL to sanitize and normalize
     * @return string|false The cleaned URL or false if invalid
     */
    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);

        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        // Handle AMP URLs
        if (preg_match('#https://([^.]+)\.cdn\.ampproject\.org/v/s/([^/]+)(.*)#', $url, $matches)) {
            $url = 'https://' . $matches[2] . $matches[3];
        }

        // Parse and reconstruct URL to ensure proper structure
        $parts = parse_url($url);
        if (!isset($parts['scheme']) || !isset($parts['host'])) {
            return '';
        }
        
        $cleanedUrl = $parts['scheme'] . '://' . $parts['host'];
        
        if (isset($parts['path'])) {
            $cleanedUrl .= $parts['path'];
        }
        
        // Remove control characters and sanitize
        $cleanedUrl = preg_replace('/[\x00-\x1F\x7F]/', '', $cleanedUrl);
        $cleanedUrl = filter_var($cleanedUrl, FILTER_SANITIZE_URL);
        
        // Convert special characters to HTML entities
        return htmlspecialchars($cleanedUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sets security headers for all responses
     * Define cabeçalhos de segurança para todas as respostas
     */
    private function setSecurityHeaders()
    {
        // Set security headers
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }

    public function dispatch()
    {
        $this->setSecurityHeaders();
        
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Remove a query string mas mantém para processamento
        // Strip query string but keep for processing
        $queryString = '';
        if (false !== $pos = strpos($uri, '?')) {
            $queryString = substr($uri, $pos);
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        // Parse query string parameters
        if ($queryString) {
            parse_str(substr($queryString, 1), $_GET);
        }

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                require_once __DIR__ . '/../config.php';
                header('Location: ' . SITE_URL);
                exit;

            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                header("HTTP/1.0 405 Method Not Allowed");
                echo '405 Method Not Allowed';
                break;

            case FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                call_user_func($handler, $vars);
                break;
        }
    }
}
