<?php

namespace Inc\Cache;

class RedisStorage implements CacheStorageInterface
{
    /**
     * @var \Redis|null Redis client instance
     */
    private $redis;

    /**
     * @var string Diretório de cache para contagem de arquivos
     */
    private $cacheDir;

    /**
     * Construtor da classe
     * 
     * @param string $cacheDir Diretório base para armazenamento do cache
     */
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;

        // Tenta inicializar conexão Redis
        try {
            $this->redis = new \Redis();
            $this->redis->connect(REDIS_HOST, REDIS_PORT, 2.5);
            $this->redis->setOption(\Redis::OPT_PREFIX, REDIS_PREFIX);
        } catch (\Exception $e) {
            // Se falhar, define redis como null
            $this->redis = null;
        }
    }

    /**
     * Conta o número de arquivos no diretório de cache
     * 
     * @return int Número de arquivos no diretório de cache
     */
    public function countCacheFiles(): int
    {
        // Chave para armazenar a contagem de arquivos no Redis
        $cacheCountKey = 'cache_file_count';

        // Se Redis estiver disponível
        if ($this->redis !== null) {
            // Verifica se a chave existe e tem valor
            $cachedCount = $this->redis->get($cacheCountKey);
            if ($cachedCount !== false) {
                return (int)$cachedCount;
            }
        }

        // Se Redis não estiver disponível ou chave vazia, conta os arquivos
        $fileCount = iterator_count(new \FilesystemIterator($this->cacheDir));

        // Se Redis estiver disponível, salva a contagem
        if ($this->redis !== null) {
            $this->redis->set($cacheCountKey, $fileCount);
        }

        return $fileCount;
    }

    /**
     * Atualiza a contagem de arquivos no Redis
     * 
     * @param int $count Número de arquivos
     */
    public function updateCacheFileCount(int $count): void
    {
        if ($this->redis !== null) {
            $this->redis->set('cache_file_count', $count);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $id): bool
    {
        return $this->redis !== null ? $this->redis->exists($id) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): ?string
    {
        if ($this->redis === null) {
            return null;
        }
        
        $content = $this->redis->get($id);
        return $content === false ? null : $content;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $id, string $content): bool
    {
        // Se Redis não estiver disponível, retorna false
        if ($this->redis === null) {
            return false;
        }

        // Ao salvar um novo arquivo, atualiza a contagem
        $result = $this->redis->set($id, $content);
        
        if ($result) {
            // Incrementa a contagem de arquivos no Redis
            $currentCount = $this->redis->get('cache_file_count') ?: 0;
            $this->redis->set('cache_file_count', $currentCount + 1);
        }

        return $result;
    }
}
