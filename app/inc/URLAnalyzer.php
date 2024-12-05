<?php

/**
 * Classe responsável pela análise e processamento de URLs
 * 
 * Esta classe implementa funcionalidades para:
 * - Análise e limpeza de URLs
 * - Cache de conteúdo
 * - Resolução DNS
 * - Requisições HTTP com múltiplas tentativas
 * - Processamento de conteúdo baseado em regras específicas por domínio
 * - Suporte a Wayback Machine como fallback
 */

require_once 'Rules.php';
require_once 'Cache.php';

use Curl\Curl;

class URLAnalyzer
{
    /**
     * @var array Lista de User Agents disponíveis para requisições
     */
    private $userAgents;

    /**
     * @var array Lista de servidores DNS para resolução
     */
    private $dnsServers;

    /**
     * @var Rules Instância da classe de regras
     */
    private $rules;

    /**
     * @var Cache Instância da classe de cache
     */
    private $cache;

    /**
     * Construtor da classe
     * Inicializa as dependências necessárias
     */
    public function __construct()
    {
        $this->userAgents = USER_AGENTS;
        $this->dnsServers = explode(',', DNS_SERVERS);
        $this->rules = new Rules();
        $this->cache = new Cache();
    }

    /**
     * Verifica se uma URL tem redirecionamentos e retorna a URL final
     * 
     * @param string $url URL para verificar redirecionamentos
     * @return array Array com a URL final e se houve redirecionamento
     */
    public function checkRedirects($url)
    {
        $curl = new Curl();
        $curl->setFollowLocation();
        $curl->setOpt(CURLOPT_TIMEOUT, 5);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_NOBODY, true);
        $curl->setUserAgent($this->userAgents[array_rand($this->userAgents)]);
        $curl->get($url);

        if ($curl->error) {
            return [
                'finalUrl' => $url,
                'hasRedirect' => false,
                'httpCode' => $curl->httpStatusCode
            ];
        }

        return [
            'finalUrl' => $curl->effectiveUrl,
            'hasRedirect' => ($curl->effectiveUrl !== $url),
            'httpCode' => $curl->httpStatusCode
        ];
    }

    /**
     * Registra erros no arquivo de log
     * 
     * @param string $url URL que gerou o erro
     * @param string $error Mensagem de erro
     */
    private function logError($url, $error)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] URL: {$url} - Error: {$error}" . PHP_EOL;
        file_put_contents(__DIR__ . '/../logs/error.log', $logEntry, FILE_APPEND);
    }

    /**
     * Método principal para análise de URLs
     * 
     * @param string $url URL a ser analisada
     * @return string Conteúdo processado da URL
     * @throws Exception Em caso de erros durante o processamento
     */
    public function analyze($url)
    {
        $cleanUrl = $this->cleanUrl($url);

        if ($this->cache->exists($cleanUrl)) {
            return $this->cache->get($cleanUrl);
        }

        $parsedUrl = parse_url($cleanUrl);
        $domain = $parsedUrl['host'];

        // Verificação de domínios bloqueados
        foreach (BLOCKED_DOMAINS as $blockedDomain) {
            // Verifica apenas correspondência exata do domínio
            if ($domain === $blockedDomain) {
                $error = 'Este domínio está bloqueado para extração.';
                $this->logError($cleanUrl, $error);
                throw new Exception($error);
            }
        }

        $content = null;

        // Primeiro, tenta buscar o conteúdo diretamente
        try {
            $content = $this->fetchContent($url);
        } catch (Exception $e) {
            // Se falhar, registra o erro de busca direta
            $this->logError($url, "Direct fetch error: " . $e->getMessage());
        }

        // Se a busca direta falhar, tenta o Wayback Machine
        if (empty($content)) {
            try {
                $content = $this->fetchFromWaybackMachine($url);
            } catch (Exception $e) {
                // Se o Wayback Machine também falhar, lança uma exceção
                throw new Exception("Wayback Machine: " . $e->getMessage());
            }
        }

        if (!empty($content)) {
            $content = $this->processContent($content, $domain, $cleanUrl);
            $this->cache->set($cleanUrl, $content);
            return $content;
        }
        
        return null;
    }

    /**
     * Tenta obter o conteúdo da URL do Internet Archive's Wayback Machine
     * 
     * @param string $url URL original
     * @return string|null Conteúdo do arquivo ou null se falhar
     */
    private function fetchFromWaybackMachine($url)
    {
        // Remove o protocolo (http/https) da URL
        $cleanUrl = preg_replace('#^https?://#', '', $url);
        
        // Primeiro, verifica a disponibilidade de snapshots
        $availabilityUrl = "https://archive.org/wayback/available?url=" . urlencode($cleanUrl);
        
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);

        $curl->get($availabilityUrl);

        if ($curl->error) {
            return null;
        }

        $data = $curl->response;
        if (!isset($data->archived_snapshots->closest->url)) {
            return null;
        }

        // Obtém o snapshot mais recente
        $archiveUrl = $data->archived_snapshots->closest->url;
        
        // Busca o conteúdo do snapshot
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);

        $curl->get($archiveUrl);

        if ($curl->error || $curl->httpStatusCode >= 400 || empty($curl->response)) {
            return null;
        }

        $content = $curl->response;

        // Remove o toolbar do Wayback Machine
        $content = preg_replace('/<!-- BEGIN WAYBACK TOOLBAR INSERT -->.*?<!-- END WAYBACK TOOLBAR INSERT -->/s', '', $content);
        
        // Remove URLs de cache do Wayback Machine
        $content = preg_replace('/https?:\/\/web\.archive\.org\/web\/\d+im_\//', '', $content);
        
        return $content;
    }

    /**
     * Realiza requisição HTTP usando Curl Class
     * 
     * @param string $url URL para requisição
     * @return string Conteúdo obtido
     * @throws Exception Em caso de erro na requisição
     */
    private function fetchContent($url)
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];

        $domainRules = $this->getDomainRules(parse_url($url, PHP_URL_HOST));

        // Obtém a configuração do user agent
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_MAXREDIRS, 2);
        $curl->setOpt(CURLOPT_TIMEOUT, 5);
        $curl->setUserAgent($this->userAgents[array_rand($this->userAgents)]);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_DNS_SERVERS, implode(',', $this->dnsServers));

        // Prepara os headers
        $headers = [
            'Host' => $host,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache'
        ];

        // Adiciona headers específicos do domínio se existirem
        if ($domainRules !== null && isset($domainRules['userAgent'])) {
            $curl->setUserAgent($domainRules['userAgent']);
        }

        // Adiciona headers específicos do domínio se existirem
        if ($domainRules !== null && isset($domainRules['headers'])) {
            $headers = array_merge($headers, $domainRules['headers']);
        }

        $curl->setHeaders($headers);
        $curl->setOpt(CURLOPT_FRESH_CONNECT, true);

        if ($domainRules !== null && isset($domainRules['cookies'])) {
            $cookies = [];
            foreach ($domainRules['cookies'] as $name => $value) {
                if ($value !== null) {
                    $cookies[] = $name . '=' . $value;
                }
            }
            if (!empty($cookies)) {
                $curl->setHeader('Cookie', implode('; ', $cookies));
            }
        }

        $curl->get($url);

        if ($curl->error) {
            throw new Exception("Erro CURL: " . $curl->errorMessage);
        }

        if ($curl->httpStatusCode >= 400) {
            throw new Exception("Erro HTTP: " . $curl->httpStatusCode);
        }

        return $curl->response;
    }

    /**
     * Limpa e normaliza uma URL
     * 
     * @param string $url URL para limpar
     * @return string URL limpa e normalizada
     */
    private function cleanUrl($url)
    {
        $url = trim($url);

        // Verifica se a URL é válida
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Detecta e converte URLs AMP
        if (preg_match('#https://([^.]+)\.cdn\.ampproject\.org/v/s/([^/]+)(.*)#', $url, $matches)) {
            $url = 'https://' . $matches[2] . $matches[3];
        }

        // Separa a URL em suas partes componentes
        $parts = parse_url($url);
        
        // Reconstrói a URL base
        $cleanedUrl = $parts['scheme'] . '://' . $parts['host'];
        
        // Adiciona o caminho se existir
        if (isset($parts['path'])) {
            $cleanedUrl .= $parts['path'];
        }
        
        return $cleanedUrl;
    }

    /**
     * Obtém regras específicas para um domínio
     * 
     * @param string $domain Domínio para buscar regras
     * @return array|null Regras do domínio ou null se não encontrar
     */
    private function getDomainRules($domain)
    {
        return $this->rules->getDomainRules($domain);
    }

    /**
     * Remove classes específicas de um elemento
     * 
     * @param DOMElement $element Elemento DOM
     * @param array $classesToRemove Classes a serem removidas
     */
    private function removeClassNames($element, $classesToRemove)
    {
        if (!$element->hasAttribute('class')) {
            return;
        }

        $classes = explode(' ', $element->getAttribute('class'));
        $newClasses = array_filter($classes, function ($class) use ($classesToRemove) {
            return !in_array(trim($class), $classesToRemove);
        });

        if (empty($newClasses)) {
            $element->removeAttribute('class');
        } else {
            $element->setAttribute('class', implode(' ', $newClasses));
        }
    }

    /**
     * Corrige URLs relativas em um documento DOM
     * 
     * @param DOMDocument $dom Documento DOM
     * @param DOMXPath $xpath Objeto XPath
     * @param string $baseUrl URL base para correção
     */
    private function fixRelativeUrls($dom, $xpath, $baseUrl)
    {
        $parsedBase = parse_url($baseUrl);
        $baseHost = $parsedBase['scheme'] . '://' . $parsedBase['host'];

        $elements = $xpath->query("//*[@src]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $src = $element->getAttribute('src');
                    if (strpos($src, 'base64') !== false) {
                        continue;
                    }
                    if (strpos($src, 'http') !== 0 && strpos($src, '//') !== 0) {
                        $src = ltrim($src, '/');
                        $element->setAttribute('src', $baseHost . '/' . $src);
                    }
                }
            }
        }

        $elements = $xpath->query("//*[@href]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $href = $element->getAttribute('href');
                    if (strpos($href, 'mailto:') === 0 || 
                        strpos($href, 'tel:') === 0 || 
                        strpos($href, 'javascript:') === 0 || 
                        strpos($href, '#') === 0) {
                        continue;
                    }
                    if (strpos($href, 'http') !== 0 && strpos($href, '//') !== 0) {
                        $href = ltrim($href, '/');
                        $element->setAttribute('href', $baseHost . '/' . $href);
                    }
                }
            }
        }
    }

    /**
     * Processa o conteúdo HTML aplicando regras do domínio
     * 
     * @param string $content Conteúdo HTML
     * @param string $domain Domínio do conteúdo
     * @param string $url URL completa
     * @return string Conteúdo processado
     */
    private function processContent($content, $domain, $url)
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Processa tags canônicas
        $canonicalLinks = $xpath->query("//link[@rel='canonical']");
        if ($canonicalLinks !== false) {
            // Remove todas as tags canônicas existentes
            foreach ($canonicalLinks as $link) {
                if ($link->parentNode) {
                    $link->parentNode->removeChild($link);
                }
            }
        }
        // Adiciona nova tag canônica com a URL original
        $head = $xpath->query('//head')->item(0);
        if ($head) {
            $newCanonical = $dom->createElement('link');
            $newCanonical->setAttribute('rel', 'canonical');
            $newCanonical->setAttribute('href', $url);
            $head->appendChild($newCanonical);
        }

        // Sempre aplica a correção de URLs relativas
        $this->fixRelativeUrls($dom, $xpath, $url);

        $domainRules = $this->getDomainRules($domain);
        if ($domainRules !== null) {
            if (isset($domainRules['customStyle'])) {
                $styleElement = $dom->createElement('style');
                $styleContent = '';
                foreach ($domainRules['customStyle'] as $selector => $rules) {
                    if (is_array($rules)) {
                        $styleContent .= $selector . ' { ' . implode('; ', $rules) . ' } ';
                    } else {
                        $styleContent .= $selector . ' { ' . $rules . ' } ';
                    }
                }
                $styleElement->appendChild($dom->createTextNode($styleContent));
                $dom->getElementsByTagName('head')[0]->appendChild($styleElement);
            }

            if (isset($domainRules['customCode'])) {
                $scriptElement = $dom->createElement('script');
                $scriptElement->setAttribute('type', 'text/javascript');
                $scriptElement->appendChild($dom->createTextNode($domainRules['customCode']));
                $dom->getElementsByTagName('body')[0]->appendChild($scriptElement);
            }

            if (isset($domainRules['classAttrRemove'])) {
                foreach ($domainRules['classAttrRemove'] as $class) {
                    $elements = $xpath->query("//*[contains(@class, '$class')]");
                    if ($elements !== false) {
                        foreach ($elements as $element) {
                            $this->removeClassNames($element, [$class]);
                        }
                    }
                }
            }

            if (isset($domainRules['idElementRemove'])) {
                foreach ($domainRules['idElementRemove'] as $id) {
                    $elements = $xpath->query("//*[@id='$id']");
                    if ($elements !== false) {
                        foreach ($elements as $element) {
                            if ($element->parentNode) {
                                $element->parentNode->removeChild($element);
                            }
                        }
                    }
                }
            }

            if (isset($domainRules['classElementRemove'])) {
                foreach ($domainRules['classElementRemove'] as $class) {
                    $elements = $xpath->query("//*[contains(@class, '$class')]");
                    if ($elements !== false) {
                        foreach ($elements as $element) {
                            if ($element->parentNode) {
                                $element->parentNode->removeChild($element);
                            }
                        }
                    }
                }
            }

            if (isset($domainRules['scriptTagRemove'])) {
                foreach ($domainRules['scriptTagRemove'] as $script) {
                    // Busca por tags script com src ou conteúdo contendo o script
                    $scriptElements = $xpath->query("//script[contains(@src, '$script')] | //script[contains(text(), '$script')]");
                    if ($scriptElements !== false) {
                        foreach ($scriptElements as $element) {
                            if ($element->parentNode) {
                                $element->parentNode->removeChild($element);
                            }
                        }
                    }

                    // Busca por tags link que são scripts
                    $linkElements = $xpath->query("//link[@as='script' and contains(@href, '$script') and @type='application/javascript']");
                    if ($linkElements !== false) {
                        foreach ($linkElements as $element) {
                            if ($element->parentNode) {
                                $element->parentNode->removeChild($element);
                            }
                        }
                    }
                }
            }
        }

        $elements = $xpath->query("//*[@style]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $style = $element->getAttribute('style');
                    $style = preg_replace('/(max-height|height|overflow|position|display|visibility)\s*:\s*[^;]+;?/', '', $style);
                    $element->setAttribute('style', $style);
                }
            }
        }

        // Adiciona CTA Marreta 
        $body = $xpath->query('//body')->item(0);
        if ($body) {
            $marretaDiv = $dom->createElement('div');
            $marretaDiv->setAttribute('style', 'z-index: 99999; position: fixed; bottom: 0; right: 4px; background: rgb(37,99,235); color: #fff; font-size: 13px; line-height: 1em; padding: 6px; margin: 0px; overflow: hidden; border-top-left-radius: 3px; border-top-right-radius: 3px; font-family: Tahoma, sans-serif;');
            $marretaHtml = $dom->createDocumentFragment();
            $marretaHtml->appendXML('Chapéu de paywall é <a href="'.SITE_URL.'" style="color: #fff; text-decoration: underline; font-weight: bold;" target="_blank">Marreta</a>!');
            $marretaDiv->appendChild($marretaHtml);
            $body->appendChild($marretaDiv);
        }

        return $dom->saveHTML();
    }
}
