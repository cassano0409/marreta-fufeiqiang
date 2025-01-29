<?php

/**
 * Application entry point
 * 
 * Initializes the routing system and dispatches requests
 * to appropriate handlers using FastRoute.
 */

require_once __DIR__ . '/vendor/autoload.php';

$router = new App\Router();
$router->dispatch();
