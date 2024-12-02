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

            $resolvedIp = $this->resolveDns($cleanUrl);
            if (!$resolvedIp) {
                $error = 'Falha ao resolver DNS para o domínio';
                $this->logError($cleanUrl, $error);
                throw new Exception($error);
            }

            $content = $this->fetchWithMultipleAttempts($cleanUrl, $resolvedIp);

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
    private function fetchWithMultipleAttempts($url, $resolvedIp)
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
                $content = $this->fetchWithCurl($url, $resolvedIp, $currentUserAgentKey);
                if (!empty($content)) {
                    return $content;
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }

            $attempts++;
            usleep(500000); // 0.5 segundo de espera entre tentativas
        }

        throw new Exception("Falha ao obter conteúdo após {$this->maxAttempts} tentativas. Erros: " . implode(', ', $errors));
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
    private function fetchWithCurl($url, $resolvedIp, $userAgentKey)
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
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RESOLVE => ["{$host}:80:{$resolvedIp}", "{$host}:443:{$resolvedIp}"],
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
        $url = strtolower($url);
        $url = trim($url);

        // Detecta e converte URLs AMP
        if (preg_match('#https://([^.]+)\.cdn\.ampproject\.org/v/s/([^/]+)(.*)#', $url, $matches)) {
            $url = 'https://' . $matches[2] . $matches[3];
        }

        $parsedUrl = parse_url($url);

        if (!isset($parsedUrl['scheme'])) {
            $url = 'https://' . $url;
            $parsedUrl = parse_url($url);
        }

        $cleanUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        if (isset($parsedUrl['path'])) {
            $path = preg_replace('#/+#', '/', $parsedUrl['path']);
            $cleanUrl .= $path;
        }

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $params);
            $params = $this->filterUrlParams($params);

            if (!empty($params)) {
                ksort($params);
                $cleanUrl .= '?' . http_build_query($params);
            }
        }

        return rtrim($cleanUrl, '/');
    }

    /**
     * Filtra parâmetros da URL removendo tracking e sessão
     * 
     * @param array $params Parâmetros da URL
     * @return array Parâmetros filtrados
     */
    private function filterUrlParams($params)
    {
        $filteredParams = [];

        foreach ($params as $key => $value) {
            if (empty($value) && $value !== '0') {
                continue;
            }

            if ($this->isTrackingParam($key)) {
                continue;
            }

            if ($this->isSessionParam($key)) {
                continue;
            }

            if ($this->isCacheParam($key)) {
                continue;
            }

            if ($this->isContentParam($key)) {
                $filteredParams[$key] = $value;
            }
        }

        return $filteredParams;
    }

    /**
     * Verifica se um parâmetro é de tracking
     * 
     * @param string $param Nome do parâmetro
     * @return bool True se for parâmetro de tracking
     */
    private function isTrackingParam($param)
    {
        $trackingPatterns = [
            // Google Analytics e AMP
            '/^utm_/',      // Universal Analytics
            '/^_ga/',       // Google Analytics
            '/^_gl/',       // Google Analytics linker
            '/^gclid$/',    // Google Ads Click ID
            '/^dclid$/',    // DoubleClick Click ID
            '/^amp_/',      // AMP parameters
            '/^usqp$/',     // Google AMP Cache
            '/^__amp_source_origin$/', // AMP source origin
            '/^amp_latest_update_time$/', // AMP update time
            '/^amp_cb$/',   // AMP callback
            '/^amp_gsa$/',  // AMP Google Search App
            '/^amp_js_v$/', // AMP JavaScript version
            '/^amp_r$/',    // AMP referrer
            '/^aoh$/',      // AMP origin header

            // Social Media
            '/^fbclid$/',   // Facebook Click ID
            '/^msclkid$/',  // Microsoft Click ID
            '/^igshid$/',   // Instagram
            '/^yclid$/',    // Yandex Click ID

            // Email Marketing
            '/^mc_/',       // Mailchimp
            '/^_hs/',       // HubSpot
            '/^_hsenc$/',   // HubSpot encoded
            '/^_hsmi$/',    // HubSpot message ID
            '/^mkt_tok$/',  // Marketo

            // Analytics e Tracking
            '/^pk_/',       // Piwik/Matomo
            '/^n_/',        // Navegg
            '/^_openstat$/', // OpenStat

            // Outros
            '/^ref$/',      // Referrer
            '/^source$/',   // Source tracking
            '/^medium$/',   // Medium tracking
            '/^campaign$/', // Campaign tracking
            '/^affiliate$/', // Affiliate tracking
            '/^partner$/',  // Partner tracking
        ];

        foreach ($trackingPatterns as $pattern) {
            if (preg_match($pattern, $param)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se um parâmetro é de sessão
     * 
     * @param string $param Nome do parâmetro
     * @return bool True se for parâmetro de sessão
     */
    private function isSessionParam($param)
    {
        $sessionPatterns = [
            '/sess(ion)?[_-]?id/i',
            '/^sid$/',
            '/^s$/',
            '/_?sess$/',
            '/^PHPSESSID$/',
            '/^JSESSIONID$/',
            '/^ASP\.NET_SessionId$/',
            '/^CFID$/',
            '/^CFTOKEN$/',
            '/^skey$/',
            '/^token$/',
            '/^auth[_-]?token$/',
            '/^access[_-]?token$/',
        ];

        foreach ($sessionPatterns as $pattern) {
            if (preg_match($pattern, $param)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se um parâmetro é de cache
     * 
     * @param string $param Nome do parâmetro
     * @return bool True se for parâmetro de cache
     */
    private function isCacheParam($param)
    {
        $cachePatterns = [
            '/^v$/',
            '/^ver$/',
            '/^version$/',
            '/^rev$/',
            '/^revision$/',
            '/^cache$/',
            '/^nocache$/',
            '/^_t$/',
            '/^timestamp$/',
            '/^time$/',
            '/^[0-9]+$/',
            '/^_=[0-9]+$/',
        ];

        foreach ($cachePatterns as $pattern) {
            if (preg_match($pattern, $param)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se um parâmetro é de conteúdo
     * 
     * @param string $param Nome do parâmetro
     * @return bool True se for parâmetro de conteúdo
     */
    private function isContentParam($param)
    {
        $contentPatterns = [
            '/^id$/',
            '/^page$/',
            '/^category$/',
            '/^cat$/',
            '/^tag$/',
            '/^type$/',
            '/^format$/',
            '/^view$/',
            '/^layout$/',
            '/^style$/',
            '/^lang$/',
            '/^locale$/',
            '/^currency$/',
            '/^filter$/',
            '/^sort$/',
            '/^order$/',
            '/^q$/',
            '/^search$/',
            '/^query$/',
            '/^year$/',
            '/^month$/',
            '/^day$/',
            '/^date$/',
            '/^author$/',
            '/^topic$/',
            '/^section$/',
        ];

        foreach ($contentPatterns as $pattern) {
            if (preg_match($pattern, $param)) {
                return true;
            }
        }

        if (preg_match('/^[a-z0-9]{4,}$/i', $param)) {
            return true;
        }

        return false;
    }

    /**
     * Resolve DNS para um domínio
     * 
     * @param string $url URL para resolver DNS
     * @return string|false IP resolvido ou false em caso de falha
     */
    private function resolveDns($url)
    {
        $parsedUrl = parse_url($url);
        $domain = $parsedUrl['host'];

        foreach ($this->dnsServers as $dnsServer) {
            $dnsQuery = [
                'name' => $domain,
                'type' => 'A',
                'do' => true,
                'cd' => false
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $dnsServer . '?' . http_build_query($dnsQuery),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/dns-json',
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if (!$error && $response) {
                $dnsData = json_decode($response, true);
                if (isset($dnsData['Answer'])) {
                    foreach ($dnsData['Answer'] as $record) {
                        if ($record['type'] === 1) {
                            return $record['data'];
                        }
                    }
                }
            }
        }

        $ip = gethostbyname($domain);
        return ($ip !== $domain) ? $ip : false;
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
                    $style = preg_replace('/(max-height|height|overflow|position|display)\s*:\s*[^;]+;?/', '', $style);
                    $element->setAttribute('style', $style);
                }
            }
        }

        return $dom->saveHTML();
    }
}
