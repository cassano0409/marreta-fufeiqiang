<?php
/**
 * Classe responsável pelo gerenciamento de cache do sistema
 * 
 * Esta classe implementa funcionalidades para armazenar e recuperar
 * conteúdo em cache, utilizando o sistema de arquivos como storage.
 * O cache é organizado por URLs convertidas em IDs únicos usando SHA-256.
 * O conteúdo é comprimido usando gzip para economizar espaço em disco.
 * 
 * Quando o modo DEBUG está ativo, todas as operações de cache são desativadas.
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
        // Se DEBUG está ativo, sempre retorna false
        if (DEBUG) {
            return false;
        }

        $id = $this->generateId($url);
        $cachePath = $this->cacheDir . '/' . $id . '.gz';
        return file_exists($cachePath);
    }

    /**
     * Recupera o conteúdo em cache de uma URL
     * 
     * @param string $url URL do conteúdo a ser recuperado
     * @return string|null Conteúdo em cache ou null se não existir
     */
    public function get($url) {
        // Se DEBUG está ativo, sempre retorna null
        if (DEBUG) {
            return null;
        }

        if (!$this->exists($url)) {
            return null;
        }
        $id = $this->generateId($url);
        $cachePath = $this->cacheDir . '/' . $id . '.gz';
        
        // Lê e descomprime o conteúdo
        $compressedContent = file_get_contents($cachePath);
        if ($compressedContent === false) {
            return null;
        }
        
        return gzdecode($compressedContent);
    }

    /**
     * Armazena conteúdo em cache para uma URL
     * 
     * @param string $url URL associada ao conteúdo
     * @param string $content Conteúdo a ser armazenado em cache
     * @return bool True se o cache foi salvo com sucesso, False caso contrário
     */
    public function set($url, $content) {
        // Se DEBUG está ativo, não gera cache
        if (DEBUG) {
            return true;
        }

        $id = $this->generateId($url);
        $cachePath = $this->cacheDir . '/' . $id . '.gz';
        
        // Comprime o conteúdo usando gzip
        $compressedContent = gzencode($content, 3);
        if ($compressedContent === false) {
            return false;
        }
        
        return file_put_contents($cachePath, $compressedContent) !== false;
    }
}
