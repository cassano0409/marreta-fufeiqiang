<?php

namespace Inc\Cache;

use Redis;

/**
 * Redis-based cache storage implementation
 * Implementação de armazenamento de cache baseado em Redis
 * 
 * This class provides cache storage and file counting functionality using Redis
 * Esta classe fornece armazenamento de cache e funcionalidade de contagem de arquivos usando Redis
 * 
 * @property \Redis|null $redis Redis client instance
 */
class RedisStorage implements CacheStorageInterface
{
    /**
     * @var \Redis|null Redis client instance
     * @var \Redis|null Instância do cliente Redis
     */
    private $redis;

    /**
     * @var string Cache directory for file counting
     * @var string Diretório de cache para contagem de arquivos
     */
    private $cacheDir;

    /**
     * Class constructor
     * Construtor da classe
     * 
     * @param string $cacheDir Base directory for cache storage / Diretório base para armazenamento do cache
     */
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;

        // Try to initialize Redis connection
        // Tenta inicializar conexão Redis
        try {
            /** @var \Redis $redis */
            $this->redis = new \Redis();
            $this->redis->connect(REDIS_HOST, REDIS_PORT, 2.5);
            $this->redis->setOption(\Redis::OPT_PREFIX, REDIS_PREFIX);
        } catch (\Exception $e) {
            // If it fails, set redis to null
            // Se falhar, define redis como null
            $this->redis = null;
        }
    }

    /**
     * Counts the number of files in the cache directory
     * Conta o número de arquivos no diretório de cache
     * 
     * @return int Number of files in the cache directory / Número de arquivos no diretório de cache
     */
    public function countCacheFiles(): int
    {
        // Key to store file count in Redis
        // Chave para armazenar a contagem de arquivos no Redis
        $cacheCountKey = 'cache_file_count';

        // If Redis is available
        // Se Redis estiver disponível
        if ($this->redis !== null) {
            // Check if the key exists and has a value
            // Verifica se a chave existe e tem valor
            /** @var string|false $cachedCount */
            $cachedCount = $this->redis->get($cacheCountKey);
            if ($cachedCount !== false) {
                return (int)$cachedCount;
            }
        }

        // If Redis is not available or key is empty, count .gz files
        // Se Redis não estiver disponível ou chave vazia, conta arquivos .gz
        $fileCount = 0;
        $iterator = new \FilesystemIterator($this->cacheDir);
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'gz') {
                $fileCount++;
            }
        }

        // If Redis is available, save the count
        // Se Redis estiver disponível, salva a contagem
        if ($this->redis !== null) {
            $this->redis->set($cacheCountKey, $fileCount);
        }

        return $fileCount;
    }

    /**
     * Updates the file count in Redis
     * Atualiza a contagem de arquivos no Redis
     * 
     * @param int $count Number of files / Número de arquivos
     */
    public function updateCacheFileCount(int $count): void
    {
        if ($this->redis !== null) {
            $this->redis->set('cache_file_count', $count);
        }
    }

    /**
     * Checks if cache exists for a given ID
     * Verifica se existe cache para um determinado ID
     * 
     * @param string $id Cache ID / ID do cache
     * @return bool True if cache exists, false otherwise / True se o cache existir, false caso contrário
     */
    public function exists(string $id): bool
    {
        return $this->redis !== null ? $this->redis->exists($id) : false;
    }

    /**
     * Retrieves cached content
     * Recupera o conteúdo em cache
     * 
     * @param string $id Cache ID / ID do cache
     * @return string|null Cached content or null if not found / Conteúdo em cache ou null se não encontrado
     */
    public function get(string $id): ?string
    {
        if ($this->redis === null) {
            return null;
        }
        
        /** @var string|false $content */
        $content = $this->redis->get($id);
        return $content === false ? null : $content;
    }

    /**
     * Stores content in cache
     * Armazena conteúdo em cache
     * 
     * @param string $id Cache ID / ID do cache
     * @param string $content Content to be stored / Conteúdo a ser armazenado
     * @return bool True if successful, false otherwise / True se bem sucedido, false caso contrário
     */
    public function set(string $id, string $content): bool
    {
        // If Redis is not available, return false
        // Se Redis não estiver disponível, retorna false
        if ($this->redis === null) {
            return false;
        }

        // When saving a new file, update the count
        // Ao salvar um novo arquivo, atualiza a contagem
        /** @var bool $result */
        $result = $this->redis->set($id, $content);
        
        if ($result) {
            // Increment file count in Redis
            // Incrementa a contagem de arquivos no Redis
            /** @var string|false $currentCount */
            $currentCount = $this->redis->get('cache_file_count') ?: 0;
            $this->redis->set('cache_file_count', $currentCount + 1);
        }

        return $result;
    }
}
