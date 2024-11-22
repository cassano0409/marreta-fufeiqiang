<?php
/**
 * Arquivo de configuração principal
 * 
 * Este arquivo contém todas as configurações globais do sistema, incluindo:
 * - Carregamento de variáveis de ambiente
 * - Definições de constantes do sistema
 * - Configurações de segurança
 * - Mensagens do sistema
 * - Configurações de bots e user agents
 * - Lista de domínios bloqueados
 */

require_once __DIR__ . '/vendor/autoload.php';

// Carrega as variáveis de ambiente do arquivo .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Configurações básicas do sistema
 */
define('SITE_NAME', isset($_ENV['SITE_NAME']) ? $_ENV['SITE_NAME'] : 'Marreta');
define('SITE_DESCRIPTION', isset($_ENV['SITE_DESCRIPTION']) ? $_ENV['SITE_DESCRIPTION'] : 'Sua arma secreta contra sites sovinas!');
define('SITE_URL', isset($_ENV['SITE_URL']) ? $_ENV['SITE_URL'] : 'https://' . $_SERVER['HTTP_HOST']);
define('MAX_ATTEMPTS', 3);  // Número máximo de tentativas para acessar uma URL
define('DNS_SERVERS', isset($_ENV['DNS_SERVERS']) ? $_ENV['DNS_SERVERS'] : '94.140.14.14, 94.140.15.15');
define('CACHE_DIR', __DIR__ . '/cache');

/**
 * Mensagens do sistema
 * 
 * Array associativo contendo todas as mensagens de erro e avisos
 * que podem ser exibidas ao usuário durante a execução do sistema
 */
define('MESSAGES', [
    'BLOCKED_DOMAIN' => [
        'message' => 'Este domínio está bloqueado para extração.',
        'type' => 'error'
    ],
    'DNS_FAILURE' => [
        'message' => 'Falha ao resolver DNS para o domínio. Verifique se a URL está correta.',
        'type' => 'warning'
    ],
    'HTTP_ERROR' => [
        'message' => 'O servidor retornou um erro ao tentar acessar a página. Tente novamente mais tarde.',
        'type' => 'warning'
    ],
    'CONNECTION_ERROR' => [
        'message' => 'Erro ao conectar com o servidor. Verifique sua conexão e tente novamente.',
        'type' => 'warning'
    ],
    'CONTENT_ERROR' => [
        'message' => 'Não foi possível obter o conteúdo. Tente usar os serviços de arquivo.',
        'type' => 'warning'
    ],
    'INVALID_URL' => [
        'message' => 'Formato de URL inválido',
        'type' => 'error'
    ],
    'NOT_FOUND' => [
        'message' => 'Página não encontrada',
        'type' => 'error'
    ],
    'GENERIC_ERROR' => [
        'message' => 'Ocorreu um erro ao processar sua solicitação.',
        'type' => 'warning'
    ]
]);

/**
 * Configurações dos bots
 * 
 * Define os user agents e headers específicos para diferentes bots
 * que podem ser utilizados para fazer requisições
 */
define('BOT_CONFIGS', [
    'Googlebot' => [
        'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'headers' => [
            'From' => 'googlebot(at)googlebot.com',
            'X-Robots-Tag' => 'noindex'
        ]
    ],
    'Bingbot' => [
        'user_agent' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'headers' => [
            'From' => 'bingbot(at)microsoft.com',
            'X-Robots-Tag' => 'noindex',
            'X-MSEdge-Bot' => 'true'
        ]
    ],
    'GPTBot' => [
        'user_agent' => 'Mozilla/5.0 (compatible; GPTBot/1.0; +https://openai.com/gptbot)',
        'headers' => [
            'From' => 'gptbot(at)openai.com',
            'X-Robots-Tag' => 'noindex',
            'X-OpenAI-Bot' => 'true'
        ]
    ]
]);

/**
 * Lista de User Agents
 * 
 * Extrai os user agents da configuração dos bots
 * Mantido para compatibilidade com código legado
 */
define('USER_AGENTS', array_column(BOT_CONFIGS, 'user_agent'));

/**
 * Lista de domínios bloqueados
 * 
 * Define os domínios que não podem ser acessados pelo sistema
 * por questões de política de uso ou restrições técnicas
 */
define('BLOCKED_DOMAINS', [
    // Sites de notícias
    'wsj.com',
    'bloomberg.com',
    'piaui.folha.uol.com.br',
    'jota.info',
    'haaretz.com',
    'haaretz.co.il',
    'washingtonpost.com',
    'gauchazh.clicrbs.com.br',
    'economist.com',
    // Tracking
    'metaffiliation.com',
    'google-analytics.com',
    'googletagmanager.com',
    'doubleclick.net',
    'analytics.google.com',
    'mixpanel.com',
    'segment.com',
    'amplitude.com',
    'hotjar.com',
    'kissmetrics.com',
    'crazyegg.com',
    'optimizely.com',
    'newrelic.com',
    'pingdom.com',
    'statcounter.com',
    'chartbeat.com',
    'mouseflow.com',
    'fullstory.com',
    'heap.io',
    'clearbrain.com',
    // Redes sociais
    'facebook.com',
    'instagram.com',
    'twitter.com',
    'x.com',
    'linkedin.com',
    'tiktok.com',
    'pinterest.com',
    'snapchat.com',
    'reddit.com',
    'bsky.app',
    'threads.net',    
    // Streaming
    'netflix.com',
    'hulu.com',
    'disneyplus.com',
    'primevideo.com',
    'spotify.com',
    'youtube.com',
    'twitch.tv',
    // E-commerce
    'amazon.com',
    'ebay.com',
    'aliexpress.com',
    'mercadolivre.com.br',
    'shopify.com',
    // Compartilhamento de arquivos
    'mega.nz',
    'mediafire.com',
    'wetransfer.com',
    'dropbox.com',
    'torrent9.pe',
    'thepiratebay.org',
    // Sites adultos
    'pornhub.com',
    'xvideos.com',
    'xnxx.com',
    'onlyfans.com',
    // Apostas e jogos
    'bet365.com',
    'betfair.com',
    'pokerstars.com',
    'casino.com',
    // Outros sites populares
    'github.com',
    'stackoverflow.com',
    'wikipedia.org'
]);
