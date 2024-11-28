<?php

use Inc\Cache\CacheStorageInterface;
use Inc\Cache\DiskStorage;
use Inc\Cache\S3Storage;

/**
 * Classe responsável pelo gerenciamento de cache do sistema
 * 
 * Esta classe implementa funcionalidades para armazenar e recuperar
 * conteúdo em cache, suportando múltiplos backends de armazenamento (disco ou S3).
 * O cache é organizado por URLs convertidas em IDs únicos usando SHA-256.
 * O conteúdo é comprimido usando gzip para economizar espaço.
 */
class Cache
{
    /**
     * @var CacheStorageInterface Implementação de storage para o cache
     */
    private $storage;

    /**
     * Construtor da classe
     * 
     * Inicializa o storage apropriado baseado na configuração
     */
    public function __construct()
    {
        // Se S3 está configurado e ativo, usa S3Storage
        if (defined('S3_CACHE_ENABLED') && S3_CACHE_ENABLED === true) {
            $this->storage = new S3Storage([
                'key'      => S3_ACCESS_KEY,
                'secret'   => S3_SECRET_KEY,
                'bucket'   => S3_BUCKET,
                'region'   => S3_REGION ?? 'us-east-1',
                'prefix'   => S3_PREFIX ?? 'cache/',
                'acl'      => S3_ACL ?? 'private',
                'endpoint' => defined('S3_ENDPOINT') ? S3_ENDPOINT : null
            ]);
        } else {
            // Caso contrário, usa o storage em disco
            $this->storage = new DiskStorage(CACHE_DIR);
        }
    }

    /**
     * Gera um ID único para uma URL
     * 
     * @param string $url URL para qual será gerado o ID
     * @return string Hash SHA-256 da URL normalizada
     */
    public function generateId($url)
    {
        // Remove protocolo e www
        $url = preg_replace('#^https?://(www\.)?#', '', $url);
        // Gera ID único usando SHA-256
        return hash('sha256', $url);
    }

    /**
     * Verifica se existe cache para uma determinada URL
     * 
     * @param string $url URL a ser verificada
     * @return bool True se existir cache, False caso contrário
     */
    public function exists($url)
    {
        // Se DEBUG está ativo, sempre retorna false
        if (DEBUG) {
            return false;
        }

        return $this->storage->exists($this->generateId($url));
    }

    /**
     * Recupera o conteúdo em cache de uma URL
     * 
     * @param string $url URL do conteúdo a ser recuperado
     * @return string|null Conteúdo em cache ou null se não existir
     */
    public function get($url)
    {
        // Se DEBUG está ativo, sempre retorna null
        if (DEBUG) {
            return null;
        }

        return $this->storage->get($this->generateId($url));
    }

    /**
     * Armazena conteúdo em cache para uma URL
     * 
     * @param string $url URL associada ao conteúdo
     * @param string $content Conteúdo a ser armazenado em cache
     * @return bool True se o cache foi salvo com sucesso, False caso contrário
     */
    public function set($url, $content)
    {
        // Se DEBUG está ativo, não gera cache
        if (DEBUG) {
            return true;
        }

        return $this->storage->set($this->generateId($url), $content);
    }
}
