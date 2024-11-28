<?php

namespace Inc\Cache;

class DiskStorage implements CacheStorageInterface
{
    /**
     * @var string Diretório onde os arquivos de cache serão armazenados
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
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $id): bool
    {
        $cachePath = $this->cacheDir . '/' . $id . '.gz';
        return file_exists($cachePath);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
