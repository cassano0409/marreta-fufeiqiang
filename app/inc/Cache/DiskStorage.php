<?php

namespace Inc\Cache;

/**
 * Disk-based cache storage
 * Implements file-based caching using gzip compression
 */
class DiskStorage implements CacheStorageInterface
{
    /**
     * @var string Directory for cache files
     */
    private $cacheDir;

    /**
     * Class constructor
     * @param string $cacheDir Base directory for cache storage
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
     * @param string $id Cache ID
     * @return bool True if cache exists, false otherwise
     */
    public function exists(string $id): bool
    {
        $cachePath = $this->cacheDir . '/' . $id . '.gz';
        return file_exists($cachePath);
    }

    /**
     * Retrieves cached content
     * @param string $id Cache ID
     * @return string|null Cached content or null if not found
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
     * @param string $id Cache ID
     * @param string $content Content to be stored
     * @return bool True if successful, false otherwise
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