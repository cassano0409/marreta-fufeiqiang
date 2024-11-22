<?php
/**
 * Classe responsável pelo gerenciamento de cache do sistema
 * 
 * Esta classe implementa funcionalidades para armazenar e recuperar
 * conteúdo em cache, utilizando o sistema de arquivos como storage.
 * O cache é organizado por URLs convertidas em IDs únicos usando SHA-256.
 */
class Cache {
    /**
     * @var string Diretório onde os arquivos de cache serão armazenados
     */
    private $cacheDir;

    /**
     * Construtor da classe
     * 
     * Inicializa o diretório de cache e cria-o se não existir
     */
    public function __construct() {
        $this->cacheDir = CACHE_DIR;
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * Gera um ID único para uma URL
     * 
     * @param string $url URL para qual será gerado o ID
     * @return string Hash SHA-256 da URL normalizada
     */
    public function generateId($url) {
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
    public function exists($url) {
        $id = $this->generateId($url);
        $cachePath = $this->cacheDir . '/' . $id . '.html';
        return file_exists($cachePath);
    }

    /**
     * Recupera o conteúdo em cache de uma URL
     * 
     * @param string $url URL do conteúdo a ser recuperado
     * @return string|null Conteúdo em cache ou null se não existir
     */
    public function get($url) {
        if (!$this->exists($url)) {
            return null;
        }
        $id = $this->generateId($url);
        $cachePath = $this->cacheDir . '/' . $id . '.html';
        return file_get_contents($cachePath);
    }

    /**
     * Armazena conteúdo em cache para uma URL
     * 
     * @param string $url URL associada ao conteúdo
     * @param string $content Conteúdo a ser armazenado em cache
     */
    public function set($url, $content) {
        $id = $this->generateId($url);
        $cachePath = $this->cacheDir . '/' . $id . '.html';
        file_put_contents($cachePath, $content);
    }
}
