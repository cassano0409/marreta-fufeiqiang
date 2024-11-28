<?php

namespace Inc\Cache;

interface CacheStorageInterface
{
    /**
     * Verifica se existe cache para um determinado ID
     * 
     * @param string $id ID do cache
     * @return bool
     */
    public function exists(string $id): bool;

    /**
     * Recupera o conteúdo em cache
     * 
     * @param string $id ID do cache
     * @return string|null
     */
    public function get(string $id): ?string;

    /**
     * Armazena conteúdo em cache
     * 
     * @param string $id ID do cache
     * @param string $content Conteúdo a ser armazenado
     * @return bool
     */
    public function set(string $id, string $content): bool;
}
