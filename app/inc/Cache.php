<?php

use Inc\Cache\CacheStorageInterface;
use Inc\Cache\DiskStorage;
use Inc\Cache\S3Storage;
use Inc\Cache\RedisStorage;

/**
 * Class responsible for system cache management
 * Classe responsável pelo gerenciamento de cache do sistema
 * 
 * This class implements functionalities to store and retrieve
 * cached content, supporting multiple storage backends (disk or S3).
 * The cache is organized by URLs converted to unique IDs using SHA-256.
 * Content is compressed using gzip to save space.
 * 
 * Esta classe implementa funcionalidades para armazenar e recuperar
 * conteúdo em cache, suportando múltiplos backends de armazenamento (disco ou S3).
 * O cache é organizado por URLs convertidas em IDs únicos usando SHA-256.
 * O conteúdo é comprimido usando gzip para economizar espaço.
 */
class Cache
{
    /**
     * @var CacheStorageInterface Storage implementation for cache
     * @var CacheStorageInterface Implementação de storage para o cache
     */
    private $storage;

    /**
     * @var RedisStorage Redis instance for file counting
     * @var RedisStorage Instância do Redis para contagem de arquivos
     */
    private $redisStorage;

    /**
     * Class constructor
     * Construtor da classe
     * 
     * Initializes appropriate storage based on configuration
     * Inicializa o storage apropriado baseado na configuração
     */
    public function __construct()
    {
        // Initialize RedisStorage for file counting
        // Inicializa o RedisStorage para contagem de arquivos
        $this->redisStorage = new RedisStorage(CACHE_DIR);

        // If S3 is configured and active, use S3Storage
        // Se S3 está configurado e ativo, usa S3Storage
        if (defined('S3_CACHE_ENABLED') && S3_CACHE_ENABLED === true) {
            $this->storage = new S3Storage([
                'key'      => S3_ACCESS_KEY,
                'secret'   => S3_SECRET_KEY,
                'bucket'   => S3_BUCKET,
                'region'   => S3_REGION ?? 'us-east-1',
                'prefix'   => S3_FOLDER ?? 'cache/',
                'acl'      => S3_ACL ?? 'private',
                'endpoint' => defined('S3_ENDPOINT') ? S3_ENDPOINT : null
            ]);
        } else {
            // Otherwise, use disk storage
            // Caso contrário, usa o storage em disco
            $this->storage = new DiskStorage(CACHE_DIR);
        }
    }

    /**
     * Gets the count of cached files
     * Obtém a contagem de arquivos em cache
     * 
     * @return int Number of files in cache / Número de arquivos em cache
     */
    public function getCacheFileCount(): int
    {
        return $this->redisStorage->countCacheFiles();
    }

    /**
     * Generates a unique ID for a URL
     * Gera um ID único para uma URL
     * 
     * @param string $url URL for which the ID will be generated / URL para qual será gerado o ID
     * @return string SHA-256 hash of the normalized URL / Hash SHA-256 da URL normalizada
     */
    public function generateId($url)
    {
        // Remove protocol and www
        // Remove protocolo e www
        $url = preg_replace('#^https?://(www\.)?#', '', $url);
        // Generate unique ID using SHA-256
        // Gera ID único usando SHA-256
        return hash('sha256', $url);
    }

    /**
     * Checks if cache exists for a given URL
     * Verifica se existe cache para uma determinada URL
     * 
     * @param string $url URL to check / URL a ser verificada
     * @return bool True if cache exists, False otherwise / True se existir cache, False caso contrário
     */
    public function exists($url)
    {
        // If DISABLE_CACHE is active, always return false
        // Se DISABLE_CACHE está ativo, sempre retorna false
        if (DISABLE_CACHE) {
            return false;
        }

        return $this->storage->exists($this->generateId($url));
    }

    /**
     * Retrieves cached content for a URL
     * Recupera o conteúdo em cache de uma URL
     * 
     * @param string $url URL of the content to retrieve / URL do conteúdo a ser recuperado
     * @return string|null Cached content or null if it doesn't exist / Conteúdo em cache ou null se não existir
     */
    public function get($url)
    {
        // If DISABLE_CACHE is active, always return null
        // Se DISABLE_CACHE está ativo, sempre retorna null
        if (DISABLE_CACHE) {
            return null;
        }

        return $this->storage->get($this->generateId($url));
    }

    /**
     * Stores content in cache for a URL
     * Armazena conteúdo em cache para uma URL
     * 
     * @param string $url URL associated with the content / URL associada ao conteúdo
     * @param string $content Content to be cached / Conteúdo a ser armazenado em cache
     * @return bool True if cache was saved successfully, False otherwise / True se o cache foi salvo com sucesso, False caso contrário
     */
    public function set($url, $content)
    {
        // If DISABLE_CACHE is active, don't generate cache
        // Se DISABLE_CACHE está ativo, não gera cache
        if (DISABLE_CACHE) {
            return true;
        }

        return $this->storage->set($this->generateId($url), $content);
    }
}
