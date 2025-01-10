<?php

/**
 * Main configuration file
 * Arquivo de configuração principal
 * 
 * This file contains all global system settings, including:
 * Este arquivo contém todas as configurações globais do sistema, incluindo:
 * 
 * - Environment variables loading / Carregamento de variáveis de ambiente
 * - System constants definition / Definições de constantes do sistema
 * - Security settings / Configurações de segurança
 * - Bot and user agent settings / Configurações de bots e user agents
 * - Blocked domains list / Lista de domínios bloqueados
 * - S3 cache settings / Configurações de cache S3
 */

require_once __DIR__ . '/vendor/autoload.php';

try {
    // Initialize environment variables
    // Inicializa as variáveis de ambiente
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Validate required fields
    // Valida campos obrigatórios
    $dotenv->required([
        'SITE_NAME',
        'SITE_DESCRIPTION',
        'SITE_URL'
    ])->notEmpty();

    // Custom URL validation
    // Validação personalizada de URL
    if (!filter_var($_ENV['SITE_URL'], FILTER_VALIDATE_URL)) {
        throw new Exception('SITE_URL must be a valid URL');
    }

    /**
     * Basic system settings
     * Configurações básicas do sistema
     */
    define('SITE_NAME', $_ENV['SITE_NAME']);
    define('SITE_DESCRIPTION', $_ENV['SITE_DESCRIPTION']);
    define('SITE_URL', $_ENV['SITE_URL']);
    
    // Optional settings with default values
    // Configurações opcionais com valores padrão
    define('DNS_SERVERS', $_ENV['DNS_SERVERS'] ?? '1.1.1.1, 8.8.8.8');
    define('DISABLE_CACHE', isset($_ENV['DISABLE_CACHE']) ? 
        filter_var($_ENV['DISABLE_CACHE'], FILTER_VALIDATE_BOOLEAN) : false);
    define('SELENIUM_HOST', $_ENV['SELENIUM_HOST'] ?? 'localhost:4444');
    define('CACHE_DIR', __DIR__ . '/cache');
    define('DEBUG', isset($_ENV['DEBUG']) ? 
        filter_var($_ENV['DEBUG'], FILTER_VALIDATE_BOOLEAN) : false);
    define('LANGUAGE', $_ENV['LANGUAGE'] ?? 'pt-br');

    /**
     * Redis settings
     * Configurações do Redis
     */
    define('REDIS_HOST', $_ENV['REDIS_HOST'] ?? 'localhost');
    define('REDIS_PORT', $_ENV['REDIS_PORT'] ?? 6379);
    define('REDIS_PREFIX', $_ENV['REDIS_PREFIX'] ?? 'marreta:');

    /**
     * Hawk.so settings
     * Configurações do Hawk.so
     */
    define('HAWK_TOKEN', $_ENV['HAWK_TOKEN'] ?? null);

    /**
     * S3 Cache settings
     * Configurações de Cache S3
     */
    define('S3_CACHE_ENABLED', isset($_ENV['S3_CACHE_ENABLED']) ? 
        filter_var($_ENV['S3_CACHE_ENABLED'], FILTER_VALIDATE_BOOLEAN) : false);
    
    if (S3_CACHE_ENABLED) {
        // Validate required S3 settings when S3 cache is enabled
        // Valida configurações obrigatórias do S3 quando o cache S3 está ativado
        $dotenv->required([
            'S3_ACCESS_KEY',
            'S3_SECRET_KEY',
            'S3_BUCKET'
        ])->notEmpty();

        define('S3_ACCESS_KEY', $_ENV['S3_ACCESS_KEY']);
        define('S3_SECRET_KEY', $_ENV['S3_SECRET_KEY']);
        define('S3_BUCKET', $_ENV['S3_BUCKET']);
        define('S3_REGION', $_ENV['S3_REGION'] ?? 'us-east-1');
        define('S3_FOLDER', $_ENV['S3_FOLDER'] ?? 'cache/');
        define('S3_ACL', $_ENV['S3_ACL'] ?? 'private');
        define('S3_ENDPOINT', $_ENV['S3_ENDPOINT'] ?? null);
    }

    /**
     * Load system configurations
     * Carrega as configurações do sistema
     */
    define('BLOCKED_DOMAINS', require __DIR__ . '/data/blocked_domains.php');
    define('DOMAIN_RULES', require __DIR__ . '/data/domain_rules.php');
    define('GLOBAL_RULES', require __DIR__ . '/data/global_rules.php');

} catch (Dotenv\Exception\ValidationException $e) {
    die('Environment Error: ' . $e->getMessage());
} catch (Exception $e) {
    die('Configuration Error: ' . $e->getMessage());
}
