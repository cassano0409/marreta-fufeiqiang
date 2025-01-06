<?php

namespace Inc\Cache;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * AWS S3-based cache storage implementation
 * Implementação de armazenamento de cache baseado em AWS S3
 * 
 * This class provides cache storage functionality using Amazon S3 or compatible services
 * Esta classe fornece funcionalidade de armazenamento de cache usando Amazon S3 ou serviços compatíveis
 */
class S3Storage implements CacheStorageInterface
{
    /**
     * @var S3Client AWS S3 Client
     * @var S3Client Cliente AWS S3
     */
    private $s3Client;

    /**
     * @var string S3 bucket name
     * @var string Nome do bucket S3
     */
    private $bucket;

    /**
     * @var string Prefix for objects in the bucket (optional)
     * @var string Prefixo para os objetos no bucket (opcional)
     */
    private $prefix;

    /**
     * @var string ACL for S3 objects
     * @var string ACL para os objetos no S3
     */
    private $acl;

    /**
     * Class constructor
     * Construtor da classe
     * 
     * @param array $config AWS S3 configuration / Configuração do AWS S3
     */
    public function __construct(array $config)
    {
        $clientConfig = [
            'version' => 'latest',
            'region'  => $config['region'] ?? 'us-east-1',
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ]
        ];

        // Add custom endpoint if provided
        // Adiciona endpoint personalizado se fornecido
        if (!empty($config['endpoint'])) {
            $clientConfig['endpoint'] = $config['endpoint'];
            // Use path-style endpoints when a custom endpoint is provided
            // Use endpoints estilo path quando um endpoint personalizado é fornecido
            $clientConfig['use_path_style_endpoint'] = true;
        }

        $this->s3Client = new S3Client($clientConfig);
        
        $this->bucket = $config['bucket'];
        $this->prefix = $config['prefix'] ?? 'cache/';
        $this->acl = $config['acl'] ?? 'private';
    }

    /**
     * Generates the complete object key in S3
     * Gera a chave completa do objeto no S3
     * 
     * @param string $id Cache ID / ID do cache
     * @return string Complete S3 object key / Chave completa do objeto no S3
     */
    private function getObjectKey(string $id): string
    {
        return $this->prefix . $id . '.gz';
    }

    /**
     * Checks if cache exists for a given ID
     * Verifica se existe cache para um determinado ID
     * 
     * @param string $id Cache ID / ID do cache
     * @return bool True if cache exists, false otherwise / True se o cache existir, false caso contrário
     */
    public function exists(string $id): bool
    {
        try {
            return $this->s3Client->doesObjectExist(
                $this->bucket,
                $this->getObjectKey($id)
            );
        } catch (AwsException $e) {
            // Log error if needed / Registra erro se necessário
            return false;
        }
    }

    /**
     * Retrieves cached content
     * Recupera o conteúdo em cache
     * 
     * @param string $id Cache ID / ID do cache
     * @return string|null Cached content or null if not found / Conteúdo em cache ou null se não encontrado
     */
    public function get(string $id): ?string
    {
        if (!$this->exists($id)) {
            return null;
        }

        try {
            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $this->getObjectKey($id)
            ]);

            $compressedContent = $result['Body']->getContents();
        
            if ($compressedContent === false) {
                return null;
            }
            
            return $compressedContent;
        } catch (AwsException $e) {
            return null;
        }
    }

    /**
     * Stores content in cache
     * Armazena conteúdo em cache
     * 
     * @param string $id Cache ID / ID do cache
     * @param string $content Content to be stored / Conteúdo a ser armazenado
     * @return bool True if successful, false otherwise / True se bem sucedido, false caso contrário
     */
    public function set(string $id, string $content): bool
    {
        try {
            $compressedContent = gzencode($content, 3);
            if ($compressedContent === false) {
                return false;
            }

            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $this->getObjectKey($id),
                'Body'   => $compressedContent,
                'ACL'    => $this->acl,
                'ContentEncoding' => 'gzip',
                'CacheControl'    => 'max-age=31536000' // 1 year / 1 ano
            ]);

            return true;
        } catch (AwsException $e) {
            // Log error if needed / Registra erro se necessário
            return false;
        }
    }
}
