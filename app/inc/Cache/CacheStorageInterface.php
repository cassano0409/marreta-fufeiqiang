<?php

namespace Inc\Cache;

/**
 * Interface for cache storage implementations
 * Interface para implementações de armazenamento de cache
 * 
 * This interface defines the required methods for any cache storage implementation.
 * Esta interface define os métodos necessários para qualquer implementação de armazenamento de cache.
 */
interface CacheStorageInterface
{
    /**
     * Checks if cache exists for a given ID
     * Verifica se existe cache para um determinado ID
     * 
     * @param string $id Cache ID / ID do cache
     * @return bool True if cache exists, false otherwise / True se o cache existir, false caso contrário
     */
    public function exists(string $id): bool;

    /**
     * Retrieves cached content
     * Recupera o conteúdo em cache
     * 
     * @param string $id Cache ID / ID do cache
     * @return string|null Cached content or null if not found / Conteúdo em cache ou null se não encontrado
     */
    public function get(string $id): ?string;

    /**
     * Stores content in cache
     * Armazena conteúdo em cache
     * 
     * @param string $id Cache ID / ID do cache
     * @param string $content Content to be stored / Conteúdo a ser armazenado
     * @return bool True if successful, false otherwise / True se bem sucedido, false caso contrário
     */
    public function set(string $id, string $content): bool;
}
