<?php

namespace Inc\Cache;

use Redis;

/**
 * Redis-based cache storage implementation
 * Provides cache storage and file counting functionality using Redis
 */
class RedisStorage implements CacheStorageInterface
{
    /**
     * @var \Redis|null Redis client instance
     */
    private $redis;

    /**
     * @var string Cache directory for file counting
     */
    private $cacheDir;

    /**
     * Class constructor
     * @param string $cacheDir Base directory for cache storage
     */
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;

        // Try to initialize Redis connection
        try {
            $this->redis = new \Redis();
            $this->redis->connect(REDIS_HOST, REDIS_PORT, 2.5);
            $this->redis->setOption(\Redis::OPT_PREFIX, REDIS_PREFIX);
        } catch (\Exception $e) {
            $this->redis = null;
        }
    }

    /**
     * Counts the number of files in the cache directory
     * @return int Number of files in the cache directory
     */
    public function countCacheFiles(): int
    {
        $cacheCountKey = 'cache_file_count';

        if ($this->redis !== null) {
            $cachedCount = $this->redis->get($cacheCountKey);
            if ($cachedCount !== false) {
                return (int)$cachedCount;
            }
        }

        $fileCount = 0;
        $iterator = new \FilesystemIterator($this->cacheDir);
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'gz') {
                $fileCount++;
            }
        }

        if ($this->redis !== null) {
            $this->redis->set($cacheCountKey, $fileCount);
        }

        return $fileCount;
    }

    /**
     * Updates the file count in Redis
     * @param int $count Number of files
     */
    public function updateCacheFileCount(int $count): void
    {
        if ($this->redis !== null) {
            $this->redis->set('cache_file_count', $count);
        }
    }

    /**
     * Checks if cache exists for a given ID
     * @param string $id Cache ID
     * @return bool True if cache exists, false otherwise
     */
    public function exists(string $id): bool
    {
        return $this->redis !== null ? $this->redis->exists($id) : false;
    }

    /**
     * Retrieves cached content
     * @param string $id Cache ID
     * @return string|null Cached content or null if not found
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
     * Stores content in cache
     * @param string $id Cache ID
     * @param string $content Content to be stored
     * @return bool True if successful, false otherwise
     */
    public function set(string $id, string $content): bool
    {
        if ($this->redis === null) {
            return false;
        }

        $result = $this->redis->set($id, $content);
        
        if ($result) {
            $currentCount = $this->redis->get('cache_file_count') ?: 0;
            $this->redis->set('cache_file_count', $currentCount + 1);
        }

        return $result;
    }
}