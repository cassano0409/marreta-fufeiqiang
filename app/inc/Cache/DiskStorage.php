<?php

namespace Inc\Cache;

/**
 * Disk-based cache storage implementation
 * Implementação de armazenamento de cache em disco
 * 
 * This class implements file-based caching using gzip compression
 * Esta classe implementa cache baseado em arquivos usando compressão gzip
 */
class DiskStorage implements CacheStorageInterface
{
    /**
     * @var string Directory where cache files will be stored
     * @var string Diretório onde os arquivos de cache serão armazenados
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
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
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
        $cachePath = $this->cacheDir . '/' . $id . '.gz';
        return file_exists($cachePath);
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
        if (!$this->exists($id)) {
            return null;
        }

        $cachePath = $this->cacheDir . '/' . $id . '.gz';
        $compressedContent = file_get_contents($cachePath);
        
        if ($compressedContent === false) {
            return null;
        }

        return gzdecode($compressedContent);
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
        $cachePath = $this->cacheDir . '/' . $id . '.gz';
        $compressedContent = gzencode($content, 3);
        
        if ($compressedContent === false) {
            return false;
        }

        return file_put_contents($cachePath, $compressedContent) !== false;
    }
}
