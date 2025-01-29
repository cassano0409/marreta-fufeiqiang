<?php

namespace Inc\Cache;

/**
 * Defines the contract for cache storage implementations
 */
interface CacheStorageInterface
{
    /**
     * Checks if cached content exists for given ID
     * @param string $id Unique cache identifier
     */
    public function exists(string $id): bool;

    /**
     * Retrieves cached content by ID
     * @return string|null Cached content or null if missing
     */
    public function get(string $id): ?string;

    /**
     * Stores content in cache with specified ID
     * @param string $id Cache entry identifier
     * @param string $content Content to store
     * @return bool Storage success status
     */
    public function set(string $id, string $content): bool;
}