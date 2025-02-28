<?php

namespace Inc;

use Inc\Cache\CacheStorageInterface;
use Inc\Cache\DiskStorage;
use Inc\Cache\S3Storage;
use Inc\Cache\SQLiteStorage;

/**
 * System cache management with multiple storage backends (disk/S3)
 * Uses SHA-256 hashed URLs as unique identifiers
 * Implements gzip compression for space efficiency
 */
class Cache
{
    /** @var CacheStorageInterface Cache storage implementation */
    private $storage;

    /** @var SQLiteStorage SQLite instance for file counting */
    private $sqliteStorage;

    /**
     * Initializes storage based on configuration
     * Uses S3Storage if configured and enabled
     * Defaults to SQLiteStorage otherwise (which delegates to DiskStorage)
     */
    public function __construct()
    {
        $this->sqliteStorage = new SQLiteStorage(CACHE_DIR);
        
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
            $this->storage = $this->sqliteStorage;
        }
    }

    /** Gets total number of cached files */
    public function getCacheFileCount(): int
    {
        return $this->sqliteStorage->countCacheFiles();
    }

    /**
     * Generates unique cache ID from URL
     * Normalizes URL by removing protocol and www
     * Returns SHA-256 hash of normalized URL
     */
    public function generateId($url)
    {
        $url = preg_replace('#^https?://(www\.)?#', '', $url);
        return hash('sha256', $url);
    }

    /** Checks if cached version exists for URL */
    public function exists($url)
    {
        if (DISABLE_CACHE) {
            return false;
        }

        return $this->storage->exists($this->generateId($url));
    }

    /** Retrieves cached content for URL */
    public function get($url)
    {
        if (DISABLE_CACHE) {
            return null;
        }

        return $this->storage->get($this->generateId($url));
    }

    /** Stores content in cache for URL */
    public function set($url, $content)
    {
        if (DISABLE_CACHE) {
            return true;
        }

        return $this->storage->set($this->generateId($url), $content);
    }
}
