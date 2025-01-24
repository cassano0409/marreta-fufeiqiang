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
            $r->addRoute('GET', '/', function() {
                // Inicialização das variáveis para a view principal
                // Initialize variables for the main view
                require_once __DIR__ . '/../config.php';
                require_once __DIR__ . '/../inc/Cache.php';
                require_once __DIR__ . '/../inc/Language.php';

                \Language::init(LANGUAGE);
                
                $message = '';
                $message_type = '';
                $url = '';
                
                // Processa mensagens da query string
                // Process query string messages
                if (isset($_GET['message'])) {
                    $message_key = $_GET['message'];
                    $messageData = \Language::getMessage($message_key);
                    $message = $messageData['message'];
                    $message_type = $messageData['type'];
                }
                
                // Processa submissão do formulário
                // Process form submission
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
                    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        header('Location: ' . SITE_URL . '/p/' . urlencode($url));
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

            // Rota da API - inclui api.php existente
            // API route - includes existing api.php
            $r->addRoute('GET', '/api/{url:.+}', function($vars) {
                $_GET['url'] = $vars['url'];
                require __DIR__ . '/api.php';
            });

            // Rota da API sem parâmetros - redireciona para raiz
            // API route without parameters - redirects to root
            $r->addRoute('GET', '/api[/]', function() {
                header('Location: /');
                exit;
            });

            // Rota de processamento - inclui p.php existente
            // Processing route - includes existing p.php
            $r->addRoute('GET', '/p/{url:.+}', function($vars) {
                $_GET['url'] = $vars['url'];
                require __DIR__ . '/p.php';
            });
            
            // Rota de processamento com query parameter ou sem parâmetros
            // Processing route with query parameter or without parameters
            $r->addRoute('GET', '/p[/]', function() {
                if (isset($_GET['url']) || isset($_GET['text'])) {
                    $url = isset($_GET['url']) ? $_GET['url'] : '';
                    $text = isset($_GET['text']) ? $_GET['text'] : '';
                    
                    // Check which parameter is a valid URL
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        header('Location: /p/' . urlencode($url));
                        exit;
                    } elseif (filter_var($text, FILTER_VALIDATE_URL)) {
                        header('Location: /p/' . urlencode($text));
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
     * Despacha a requisição para a rota apropriada
     * Dispatches the request to the appropriate route
     */
    public function dispatch()
    {
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
