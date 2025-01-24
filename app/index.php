<?php

/**
 * Arquivo de entrada da aplicação
 * Application entry point
 * 
 * Este arquivo inicializa o sistema de roteamento e despacha as requisições
 * para os manipuladores apropriados usando FastRoute.
 * 
 * This file initializes the routing system and dispatches requests
 * to appropriate handlers using FastRoute.
 */

require_once __DIR__ . '/vendor/autoload.php';

$router = new App\Router();
$router->dispatch();
