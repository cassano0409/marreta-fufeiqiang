<?php
/**
 * Analisador de Logs de Erro
 * 
 * Este script analisa o arquivo de log de erros (logs/error.log) e gera um relatório
 * que mostra os erros mais comuns agrupados por domínio. É útil para identificar
 * padrões de falhas e problemas recorrentes em diferentes domínios.
 * 
 * Funcionamento:
 * 1. Lê o arquivo de log linha por linha
 * 2. Extrai a URL e a mensagem de erro de cada linha
 * 3. Agrupa os erros por domínio
 * 4. Conta a frequência de cada tipo de erro
 * 5. Exibe um relatório formatado
 * 
 * Uso:
 * php logs/index.php
 * 
 * Formato esperado do log:
 * [DATA HORA] URL: http://exemplo.com - Error: Mensagem de erro
 */

// Verifica se está sendo executado via CLI
if (php_sapi_name() !== 'cli') {
    die('Este script só pode ser executado via linha de comando.');
}

// Caminho para o arquivo de log
$logFile = __DIR__ . '/../logs/error.log';

// Verifica se o arquivo de log existe
if (!file_exists($logFile)) {
    die("Arquivo de log não encontrado: $logFile\n");
}

$errors = [];

// Lê o arquivo de log
$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    // Extrai URL e mensagem de erro usando expressão regular
    if (preg_match('/URL: (https?:\/\/[^\s]+) - Error: (.+)$/', $line, $matches)) {
        $url = $matches[1];
        $error = $matches[2];
        
        // Extrai o domínio da URL
        $domain = parse_url($url, PHP_URL_HOST);
        
        // Inicializa o array do domínio se não existir
        if (!isset($errors[$domain])) {
            $errors[$domain] = [];
        }
        
        // Conta ocorrências de erro para este domínio
        if (!isset($errors[$domain][$error])) {
            $errors[$domain][$error] = 0;
        }
        $errors[$domain][$error]++;
    }
}

// Exibe os resultados
echo "Análise de Erros por Domínio:\n";
echo "============================\n\n";

foreach ($errors as $domain => $domainErrors) {
    echo "Domínio: " . $domain . "\n";
    echo "-------------------------\n";
    
    foreach ($domainErrors as $error => $count) {
        echo "- " . $error . ": " . $count . " ocorrência(s)\n";
    }
    echo "\n";
}
