<?php

/**
 * Classe para gerenciar requisições HTTP usando cURL
 */
class Curl
{
    /**
     * @var array Configurações padrão do cURL
     */
    protected $defaultOptions = [];

    /**
     * @var array Headers HTTP customizados
     */
    protected $headers = [];

    /**
     * @var array Cookies para a requisição
     */
    protected $cookies = [];

    /**
     * @var string User agent atual
     */
    protected $userAgent;

    /**
     * @var array Configurações de proxy
     */
    protected $proxy = [];

    /**
     * @var int Número máximo de tentativas
     */
    protected $maxRetries = 3;

    /**
     * @var int Delay entre tentativas (microssegundos)
     */
    protected $retryDelay = 500000; // 0.5 segundos

    /**
     * Construtor
     * 
     * @param array $options Opções iniciais do cURL
     */
    public function __construct(array $options = [])
    {
        $this->defaultOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIESESSION => true,
            CURLOPT_FRESH_CONNECT => true,
        ];

        $this->setDefaultHeaders();
    }

    /**
     * Define headers padrão
     */
    protected function setDefaultHeaders()
    {
        $this->headers = [
            'Accept' => 'text/html, application/xhtml+xml, application/xml;q=0.9, image/webp, */*;q=0.8',
            'Accept-Language' => 'pt-BR, pt;q=0.9, en-US;q=0.8, en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache'
        ];
    }

    /**
     * Define o user agent
     */
    public function setUserAgent($userAgent)
    {
        if (is_string($userAgent)) {
            $this->userAgent = $userAgent;
        }
        return $this;
    }

    /**
     * Adiciona headers customizados
     */
    public function setHeaders(array $headers)
    {
        // Reset headers to default first
        $this->setDefaultHeaders();
        
        // Add new headers
        foreach ($headers as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                continue;
            }
            $this->headers[trim($name)] = trim($value);
        }
        return $this;
    }

    /**
     * Define cookies para a requisição
     */
    public function setCookies(array $cookies)
    {
        $this->cookies = [];
        foreach ($cookies as $name => $value) {
            if (is_string($name) && $value !== null && is_string($value)) {
                $this->cookies[] = trim($name) . '=' . trim($value);
            }
        }
        return $this;
    }

    /**
     * Configura proxy para a requisição
     */
    public function setProxy($host, $port, $username = null, $password = null)
    {
        if (is_string($host) && is_numeric($port)) {
            $this->proxy = [
                'host' => $host,
                'port' => (int)$port,
                'username' => is_string($username) ? $username : null,
                'password' => is_string($password) ? $password : null
            ];
        }
        return $this;
    }

    /**
     * Define o número máximo de tentativas
     */
    public function setMaxRetries($maxRetries)
    {
        $this->maxRetries = max(1, (int)$maxRetries);
        return $this;
    }

    /**
     * Define o delay entre tentativas
     */
    public function setRetryDelay($microseconds)
    {
        $this->retryDelay = max(0, (int)$microseconds);
        return $this;
    }

    /**
     * Prepara as opções do cURL para a requisição
     */
    protected function prepareOptions($url, array $additionalOptions = [])
    {
        if (!is_string($url)) {
            throw new InvalidArgumentException('URL must be a string');
        }

        $options = [];

        // Add default options
        foreach ($this->defaultOptions as $key => $value) {
            if (is_int($key)) {
                $options[$key] = $value;
            }
        }

        // Set URL
        $options[CURLOPT_URL] = $url;

        // Set User Agent
        if ($this->userAgent) {
            $options[CURLOPT_USERAGENT] = $this->userAgent;
        }

        // Convert headers array to cURL format
        $headerLines = [];
        foreach ($this->headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }
        if (!empty($headerLines)) {
            $options[CURLOPT_HTTPHEADER] = $headerLines;
        }

        // Set Cookies
        if (!empty($this->cookies)) {
            $cookieStr = implode('; ', array_filter($this->cookies, 'is_string'));
            if (!empty($cookieStr)) {
                $options[CURLOPT_COOKIE] = $cookieStr;
            }
        }

        // Set Proxy
        if (!empty($this->proxy)) {
            $options[CURLOPT_PROXY] = $this->proxy['host'] . ':' . $this->proxy['port'];
            if (!empty($this->proxy['username']) && !empty($this->proxy['password'])) {
                $options[CURLOPT_PROXYUSERPWD] = $this->proxy['username'] . ':' . $this->proxy['password'];
            }
        }

        // Add additional options
        foreach ($additionalOptions as $key => $value) {
            if (is_int($key)) {
                $options[$key] = $value;
            }
        }

        return $options;
    }

    /**
     * Executa uma requisição HTTP
     */
    protected function execute($url, array $options = [])
    {
        $attempts = 0;
        $lastError = null;

        while ($attempts < $this->maxRetries) {
            $ch = curl_init();
            $curlOptions = $this->prepareOptions($url, $options);
            
            if (!curl_setopt_array($ch, $curlOptions)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new Exception("Failed to set cURL options: " . $error);
            }

            $content = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            
            curl_close($ch);

            if ($content !== false && empty($error)) {
                return [
                    'content' => $content,
                    'info' => $info
                ];
            }

            $lastError = $error ?: 'HTTP ' . $info['http_code'];
            $attempts++;

            if ($attempts < $this->maxRetries) {
                usleep($this->retryDelay);
            }
        }

        throw new Exception("Falha após {$this->maxRetries} tentativas. Último erro: " . $lastError);
    }

    /**
     * Executa uma requisição GET
     */
    public function get($url, array $options = [])
    {
        return $this->execute($url, $options);
    }

    /**
     * Executa uma requisição HEAD
     */
    public function head($url, array $options = [])
    {
        $options[CURLOPT_NOBODY] = true;
        return $this->execute($url, $options);
    }

    /**
     * Executa uma requisição POST
     */
    public function post($url, $data = null, array $options = [])
    {
        $options[CURLOPT_POST] = true;
        
        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = is_array($data) ? http_build_query($data) : $data;
        }

        return $this->execute($url, $options);
    }

    /**
     * Executa uma requisição PUT
     */
    public function put($url, $data = null, array $options = [])
    {
        $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
        
        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = is_array($data) ? http_build_query($data) : $data;
        }

        return $this->execute($url, $options);
    }

    /**
     * Executa uma requisição DELETE
     */
    public function delete($url, array $options = [])
    {
        $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        return $this->execute($url, $options);
    }
}
