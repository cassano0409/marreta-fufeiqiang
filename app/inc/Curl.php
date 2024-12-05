<?php

/**
 * Classe para gerenciar requisições HTTP usando cURL
 * Esta classe fornece uma interface simplificada para realizar requisições HTTP
 */
class Curl
{
    /**
     * @var array Configurações padrão do cURL
     * Armazena as opções básicas que serão utilizadas em todas as requisições
     */
    protected $defaultOptions = [];

    /**
     * @var array Cabeçalhos HTTP personalizados
     * Armazena os headers que serão enviados com as requisições
     */
    protected $headers = [];

    /**
     * @var array Cookies para a requisição
     * Armazena os cookies que serão enviados com as requisições
     */
    protected $cookies = [];

    /**
     * @var string Agente do usuário atual
     * Identifica o cliente que está fazendo a requisição
     */
    protected $userAgent;

    /**
     * @var array Configurações de proxy
     * Armazena as configurações para uso de proxy nas requisições
     */
    protected $proxy = [];

    /**
     * @var int Número máximo de tentativas
     * Define quantas vezes a requisição será tentada em caso de falha
     */
    protected $maxRetries = 3;

    /**
     * @var int Intervalo entre tentativas (em microssegundos)
     * Define o tempo de espera entre tentativas consecutivas
     */
    protected $retryDelay = 500000; // 0.5 segundos

    /**
     * Inicializa uma nova instância da classe Curl
     * 
     * @param array $options Opções iniciais do cURL para personalização
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
     * Define os cabeçalhos HTTP padrão
     * Configura os headers básicos que serão utilizados em todas as requisições
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
     * Define o agente do usuário para as requisições
     * 
     * @param string $userAgent String que identifica o cliente
     * @return $this Retorna a instância atual para encadeamento de métodos
     */
    public function setUserAgent($userAgent)
    {
        if (is_string($userAgent)) {
            $this->userAgent = $userAgent;
        }
        return $this;
    }

    /**
     * Define cabeçalhos HTTP personalizados
     * 
     * @param array $headers Array associativo com os cabeçalhos
     * @return $this Retorna a instância atual para encadeamento de métodos
     */
    public function setHeaders(array $headers)
    {
        // Redefine os headers para o padrão primeiro
        $this->setDefaultHeaders();
        
        // Adiciona os novos headers
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
     * 
     * @param array $cookies Array associativo com os cookies
     * @return $this Retorna a instância atual para encadeamento de métodos
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
     * Configura um proxy para as requisições
     * 
     * @param string $host Endereço do servidor proxy
     * @param int $port Porta do servidor proxy
     * @param string|null $username Nome de usuário para autenticação no proxy
     * @param string|null $password Senha para autenticação no proxy
     * @return $this Retorna a instância atual para encadeamento de métodos
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
     * Define o número máximo de tentativas para uma requisição
     * 
     * @param int $maxRetries Número máximo de tentativas
     * @return $this Retorna a instância atual para encadeamento de métodos
     */
    public function setMaxRetries($maxRetries)
    {
        $this->maxRetries = max(1, (int)$maxRetries);
        return $this;
    }

    /**
     * Define o intervalo entre tentativas de requisição
     * 
     * @param int $microseconds Tempo em microssegundos
     * @return $this Retorna a instância atual para encadeamento de métodos
     */
    public function setRetryDelay($microseconds)
    {
        $this->retryDelay = max(0, (int)$microseconds);
        return $this;
    }

    /**
     * Prepara as opções do cURL para a requisição
     * 
     * @param string $url URL da requisição
     * @param array $additionalOptions Opções adicionais do cURL
     * @return array Retorna array com todas as opções configuradas
     * @throws InvalidArgumentException Se a URL não for uma string válida
     */
    protected function prepareOptions($url, array $additionalOptions = [])
    {
        if (!is_string($url)) {
            throw new InvalidArgumentException('A URL deve ser uma string');
        }

        $options = [];

        // Adiciona opções padrão
        foreach ($this->defaultOptions as $key => $value) {
            if (is_int($key)) {
                $options[$key] = $value;
            }
        }

        // Define a URL
        $options[CURLOPT_URL] = $url;

        // Define o User Agent
        if ($this->userAgent) {
            $options[CURLOPT_USERAGENT] = $this->userAgent;
        }

        // Converte array de headers para o formato do cURL
        $headerLines = [];
        foreach ($this->headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }
        if (!empty($headerLines)) {
            $options[CURLOPT_HTTPHEADER] = $headerLines;
        }

        // Define os Cookies
        if (!empty($this->cookies)) {
            $cookieStr = implode('; ', array_filter($this->cookies, 'is_string'));
            if (!empty($cookieStr)) {
                $options[CURLOPT_COOKIE] = $cookieStr;
            }
        }

        // Define o Proxy
        if (!empty($this->proxy)) {
            $options[CURLOPT_PROXY] = $this->proxy['host'] . ':' . $this->proxy['port'];
            if (!empty($this->proxy['username']) && !empty($this->proxy['password'])) {
                $options[CURLOPT_PROXYUSERPWD] = $this->proxy['username'] . ':' . $this->proxy['password'];
            }
        }

        // Adiciona opções adicionais
        foreach ($additionalOptions as $key => $value) {
            if (is_int($key)) {
                $options[$key] = $value;
            }
        }

        return $options;
    }

    /**
     * Executa uma requisição HTTP
     * 
     * @param string $url URL da requisição
     * @param array $options Opções adicionais do cURL
     * @return array Retorna array com o conteúdo e informações da requisição
     * @throws Exception Se a requisição falhar após todas as tentativas
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
                throw new Exception("Falha ao definir opções do cURL: " . $error);
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
     * 
     * @param string $url URL da requisição
     * @param array $options Opções adicionais do cURL
     * @return array Retorna array com o conteúdo e informações da requisição
     */
    public function get($url, array $options = [])
    {
        return $this->execute($url, $options);
    }

    /**
     * Executa uma requisição HEAD
     * 
     * @param string $url URL da requisição
     * @param array $options Opções adicionais do cURL
     * @return array Retorna array com o conteúdo e informações da requisição
     */
    public function head($url, array $options = [])
    {
        $options[CURLOPT_NOBODY] = true;
        return $this->execute($url, $options);
    }

    /**
     * Executa uma requisição POST
     * 
     * @param string $url URL da requisição
     * @param mixed $data Dados a serem enviados no corpo da requisição
     * @param array $options Opções adicionais do cURL
     * @return array Retorna array com o conteúdo e informações da requisição
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
     * 
     * @param string $url URL da requisição
     * @param mixed $data Dados a serem enviados no corpo da requisição
     * @param array $options Opções adicionais do cURL
     * @return array Retorna array com o conteúdo e informações da requisição
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
     * 
     * @param string $url URL da requisição
     * @param array $options Opções adicionais do cURL
     * @return array Retorna array com o conteúdo e informações da requisição
     */
    public function delete($url, array $options = [])
    {
        $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        return $this->execute($url, $options);
    }
}
