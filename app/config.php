<?php

/**
 * Arquivo de configuração principal
 * 
 * Este arquivo contém todas as configurações globais do sistema, incluindo:
 * - Carregamento de variáveis de ambiente
 * - Definições de constantes do sistema
 * - Configurações de segurança
 * - Mensagens do sistema
 * - Configurações de bots e user agents
 * - Lista de domínios bloqueados
 */

require_once __DIR__ . '/vendor/autoload.php';

// Carrega as variáveis de ambiente do arquivo .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Configurações básicas do sistema
 */
define('SITE_NAME', isset($_ENV['SITE_NAME']) ? $_ENV['SITE_NAME'] : 'Marreta');
define('SITE_DESCRIPTION', isset($_ENV['SITE_DESCRIPTION']) ? $_ENV['SITE_DESCRIPTION'] : 'Chapéu de paywall é marreta!');
define('SITE_URL', isset($_ENV['SITE_URL']) ? $_ENV['SITE_URL'] : 'https://' . $_SERVER['HTTP_HOST']);
define('MAX_ATTEMPTS', 3);  // Número máximo de tentativas para acessar uma URL
define('DNS_SERVERS', isset($_ENV['DNS_SERVERS']) ? $_ENV['DNS_SERVERS'] : '94.140.14.14, 94.140.15.15');
define('CACHE_DIR', __DIR__ . '/cache');
define('DEBUG', isset($_ENV['DEBUG']) ? filter_var($_ENV['DEBUG'], FILTER_VALIDATE_BOOLEAN) : false);

/**
 * Carrega as configurações do sistema
 */
define('MESSAGES', require __DIR__ . '/data/messages.php');
define('USER_AGENTS', require __DIR__ . '/data/user_agents.php');
define('BLOCKED_DOMAINS', require __DIR__ . '/data/blocked_domains.php');
define('DOMAIN_RULES', require __DIR__ . '/data/domain_rules.php');
define('GLOBAL_RULES', require __DIR__ . '/data/global_rules.php');
