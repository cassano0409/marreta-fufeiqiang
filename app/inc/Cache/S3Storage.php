<?php

namespace Inc\Cache;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3Storage implements CacheStorageInterface
{
    /**
     * @var S3Client Cliente AWS S3
     */
    private $s3Client;

    /**
     * @var string Nome do bucket S3
     */
    private $bucket;

    /**
     * @var string Prefixo para os objetos no bucket (opcional)
     */
    private $prefix;

    /**
     * @var string ACL para os objetos no S3
     */
    private $acl;

    /**
     * Construtor da classe
     * 
     * @param array $config Configuração do AWS S3
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

        // Adiciona endpoint personalizado se fornecido
        if (!empty($config['endpoint'])) {
            $clientConfig['endpoint'] = $config['endpoint'];
            // Use path-style endpoints quando um endpoint personalizado é fornecido
            $clientConfig['use_path_style_endpoint'] = true;
        }

        $this->s3Client = new S3Client($clientConfig);
        
        $this->bucket = $config['bucket'];
        $this->prefix = $config['prefix'] ?? 'cache/';
        $this->acl = $config['acl'] ?? 'private';
    }

    /**
     * Gera a chave completa do objeto no S3
     * 
     * @param string $id ID do cache
     * @return string
     */
    private function getObjectKey(string $id): string
    {
        return $this->prefix . $id . '.gz';
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $id): bool
    {
        try {
            return $this->s3Client->doesObjectExist(
                $this->bucket,
                $this->getObjectKey($id)
            );
        } catch (AwsException $e) {
            // Log error if needed
            return false;
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
                'CacheControl'    => 'max-age=31536000' // 1 year
            ]);

            return true;
        } catch (AwsException $e) {
            // Log error if needed
            return false;
        }
    }
}
