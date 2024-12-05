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

class URLAnalyzer
{
    /**
     * @var array Lista de User Agents disponíveis para requisições
     */
    private $userAgents;

    /**
     * @var int Número máximo de tentativas para obter conteúdo
     */
    private $maxAttempts;

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
        $this->maxAttempts = MAX_ATTEMPTS;
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
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => $this->userAgents[array_rand($this->userAgents)]['user_agent'],
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'finalUrl' => $finalUrl,
            'hasRedirect' => ($finalUrl !== $url),
            'httpCode' => $httpCode
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
        try {
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

            $content = $this->fetchWithMultipleAttempts($cleanUrl);

            if (empty($content)) {
                $error = 'Não foi possível obter o conteúdo. Tente usar serviços de arquivo.';
                $this->logError($cleanUrl, $error);
                throw new Exception($error);
            }

            $content = $this->processContent($content, $domain, $cleanUrl);

            $this->cache->set($cleanUrl, $content);

            return $content;
        } catch (Exception $e) {
            $this->logError($url, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tenta obter o conteúdo da URL com múltiplas tentativas
     * 
     * @param string $url URL para buscar conteúdo
     * @param string $resolvedIp IP resolvido do domínio
     * @return string Conteúdo obtido
     * @throws Exception Se todas as tentativas falharem
     */
    private function fetchWithMultipleAttempts($url)
    {
        $attempts = 0;
        $errors = [];

        // Array com as chaves dos user agents para rotação
        $userAgentKeys = array_keys($this->userAgents);
        $totalUserAgents = count($userAgentKeys);

        while ($attempts < $this->maxAttempts) {
            try {
                // Seleciona um user agent de forma rotativa
                $currentUserAgentKey = $userAgentKeys[$attempts % $totalUserAgents];
                $content = $this->fetchContent($url, $currentUserAgentKey);
                if (!empty($content)) {
                    return $content;
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }

            $attempts++;
            usleep(500000); // 0.5 segundo de espera entre tentativas
        }

        // Se todas as tentativas falharem, tenta buscar do Wayback Machine
        try {
            $content = $this->fetchFromWaybackMachine($url);
            if (!empty($content)) {
                return $content;
            }
        } catch (Exception $e) {
            $errors[] = "Wayback Machine: " . $e->getMessage();
        }

        throw new Exception("Falha ao obter conteúdo após {$this->maxAttempts} tentativas e Wayback Machine. Erros: " . implode(', ', $errors));
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
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $availabilityUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || empty($response)) {
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['archived_snapshots']['closest']['url'])) {
            return null;
        }

        // Obtém o snapshot mais recente
        $archiveUrl = $data['archived_snapshots']['closest']['url'];
        
        // Busca o conteúdo do snapshot
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $archiveUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Cache-Control: no-cache',
                'Pragma: no-cache'
            ]
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400 || empty($content)) {
            return null;
        }

        // Remove o toolbar do Wayback Machine
        $content = preg_replace('/<!-- BEGIN WAYBACK TOOLBAR INSERT -->.*?<!-- END WAYBACK TOOLBAR INSERT -->/s', '', $content);
        
        return $content;
    }

    /**
     * Realiza requisição HTTP usando cURL
     * 
     * @param string $url URL para requisição
     * @param string $resolvedIp IP resolvido do domínio
     * @param string $userAgentKey Chave do user agent a ser utilizado
     * @return string Conteúdo obtido
     * @throws Exception Em caso de erro na requisição
     */
    private function fetchContent($url, $userAgentKey)
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];

        $domainRules = $this->getDomainRules(parse_url($url, PHP_URL_HOST));

        // Obtém a configuração do user agent
        $userAgentConfig = $this->userAgents[$userAgentKey];
        $userAgent = $userAgentConfig['user_agent'];

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_DNS_SERVERS => implode(',', $this->dnsServers)
        ];

        // Prepara os headers
        $headers = [
            'Host: ' . $host,
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ];

        // Adiciona os headers específicos do user agent
        if (isset($userAgentConfig['headers'])) {
            foreach ($userAgentConfig['headers'] as $headerName => $headerValue) {
                $headers[] = $headerName . ': ' . $headerValue;
            }
        }

        // Adiciona headers específicos do domínio se existirem
        if ($domainRules !== null && isset($domainRules['userAgent'])) {
            $curlOptions[CURLOPT_USERAGENT] = $domainRules['userAgent'];
        }

        // Adiciona headers específicos do domínio se existirem
        if ($domainRules !== null && isset($domainRules['customHeaders'])) {
            foreach ($domainRules['customHeaders'] as $headerName => $headerValue) {
                $headers[] = $headerName . ': ' . $headerValue;
            }
        }

        $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        $curlOptions[CURLOPT_COOKIESESSION] = true;
        $curlOptions[CURLOPT_FRESH_CONNECT] = true;

        if ($domainRules !== null && isset($domainRules['cookies'])) {
            $cookies = [];
            foreach ($domainRules['cookies'] as $name => $value) {
                if ($value !== null) {
                    $cookies[] = $name . '=' . $value;
                }
            }
            if (!empty($cookies)) {
                $curlOptions[CURLOPT_COOKIE] = implode('; ', $cookies);
            }
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception("Erro CURL: " . $error);
        }

        if ($httpCode >= 400) {
            throw new Exception("Erro HTTP: " . $httpCode);
        }

        return $content;
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
