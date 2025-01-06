<?php

/**
 * Página principal do sistema / Main system page
 * 
 * Este arquivo implementa a interface web principal, incluindo:
 * - Formulário para análise de URLs
 * - Tratamento de mensagens de erro/sucesso
 * - Documentação da API
 * - Interface para bookmarklet
 * - Links para serviços alternativos
 *
 * This file implements the main web interface, including:
 * - URL analysis form
 * - Error/success message handling
 * - API documentation
 * - Bookmarklet interface
 * - Links to alternative services
 */

require_once 'config.php';
require_once 'inc/Cache.php';
require_once 'inc/Language.php';

// Inicialização das traduções / Initialize translations
Language::init(LANGUAGE);

// Inicialização de variáveis / Variable initialization
$message = '';
$message_type = '';
$url = '';

// Processa mensagens de erro/alerta da query string / Process error/warning messages from query string
if (isset($_GET['message'])) {
    $message_key = $_GET['message'];
    $messageData = Language::getMessage($message_key);
    $message = $messageData['message'];
    $message_type = $messageData['type'];
}

// Processa submissão do formulário / Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        header('Location: ' . SITE_URL . '/p/' . urlencode($url));
        exit;
    } else {
        $messageData = Language::getMessage('INVALID_URL');
        $message = $messageData['message'];
        $message_type = $messageData['type'];
    }
}

// Inicializa o cache para contagem / Initialize cache for counting
$cache = new Cache();
$cache_folder = $cache->getCacheFileCount();
?>
<!DOCTYPE html>
<html lang="<?php echo Language::getCurrentLanguage(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="icon" href="assets/svg/marreta.svg" type="image/svg+xml">
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo SITE_URL; ?>" />
    <meta property="og:title" content="<?php echo SITE_NAME; ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars(SITE_DESCRIPTION); ?>" />
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/opengraph.png" />
    <script src="https://cdn.tailwindcss.com/3.4.15"></script>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Cabeçalho da página / Page header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">
                <img src="assets/svg/marreta.svg" class="inline-block w-12 h-12 mb-2" alt="Marreta">
                <?php echo SITE_NAME; ?>
            </h1>
            <p class="text-gray-600 text-lg"><?php echo SITE_DESCRIPTION; ?></p>
            <p class="text-gray-600 text-lg">
                <span class="font-bold text-blue-600">
                    <?php echo number_format($cache_folder, 0, ',', '.'); ?>
                </span>
                <span><?php echo Language::get('walls_destroyed'); ?></span>
            </p>
        </div>

        <!-- Formulário principal de análise de URLs / Main URL analysis form -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <form id="urlForm" method="POST" onsubmit="return validateForm()" class="space-y-6">
                <div class="relative">
                    <div class="flex items-stretch">
                        <span class="inline-flex items-center px-5 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                            <img src="assets/svg/link.svg" class="w-6 h-6" alt="Link">
                        </span>
                        <input type="url"
                            name="url"
                            id="url"
                            class="flex-1 block w-full rounded-none rounded-r-lg text-lg py-4 border border-l-0 border-gray-300 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 shadow-sm bg-gray-50"
                            placeholder="<?php echo Language::get('url_placeholder'); ?>"
                            value="<?php echo htmlspecialchars($url); ?>"
                            required
                            pattern="https?://.+"
                            title="<?php echo Language::getMessage('INVALID_URL')['message']; ?>">
                    </div>
                    <button type="submit"
                        class="mt-4 w-full inline-flex justify-center items-center px-6 py-4 border border-transparent text-lg font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <img src="assets/svg/search.svg" class="w-6 h-6 mr-3" alt="Search">
                        <?php echo Language::get('analyze_button'); ?>
                    </button>
                </div>
            </form>

            <!-- Aviso sobre bloqueadores de anúncios / Ad blocker warning -->
            <div class="mt-4 text-sm text-gray-600 flex items-start">
                <p><?php echo str_replace('{site_name}', SITE_NAME, Language::get('adblocker_warning')); ?></p>
            </div>

            <!-- Área de mensagens de erro/alerta / Error/warning message area -->
            <?php if ($message): ?>
                <div class="mt-6 <?php echo $message_type === 'error' ? 'bg-red-50 border-red-400' : 'bg-yellow-50 border-yellow-400'; ?> border-l-4 p-4 rounded-r">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <?php if ($message_type === 'error'): ?>
                                <img src="assets/svg/error.svg" class="w-6 h-6" alt="Error">
                            <?php else: ?>
                                <img src="assets/svg/warning.svg" class="w-6 h-6" alt="Warning">
                            <?php endif; ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-base <?php echo $message_type === 'error' ? 'text-red-700' : 'text-yellow-700'; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Exemplo de uso direto / Direct usage example -->
        <div class="mt-8 text-center text-base text-gray-500">
            <p>
                <img src="assets/svg/code.svg" class="inline-block w-5 h-5 mr-2" alt="Acesso direito">
                <?php echo Language::get('direct_access'); ?>
            <pre class="bg-gray-100 p-3 rounded-lg text-sm overflow-x-auto"><?php echo SITE_URL; ?>/p/https://exemplo.com</pre>
            </p>
        </div>

        <!-- Seção de Bookmarklet / Bookmarklet section -->
        <div class="bg-white rounded-xl shadow-lg p-8 mt-8 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                <img src="assets/svg/bookmark.svg" class="w-6 h-6 mr-3" alt="Favoritos">
                <?php echo Language::get('bookmarklet_title'); ?>
            </h2>
            <div class="space-y-4">
                <p class="text-gray-600">
                    <?php echo str_replace('{site_name}', SITE_NAME, Language::get('bookmarklet_description')); ?>
                </p>
                <div class="flex justify-center">
                    <a href="javascript:(function(){let currentUrl=window.location.href;window.location.href='<?php echo SITE_URL; ?>/p/'+encodeURIComponent(currentUrl);})()"
                        class="inline-flex items-center px-6 py-3 border-2 border-blue-500 font-medium rounded-lg text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 cursor-move"
                        onclick="return false;">
                        <img src="assets/svg/marreta.svg" class="w-5 h-5 mr-2" alt="Marreta">
                        <?php echo str_replace('{site_name}', SITE_NAME, Language::get('open_in')); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Serviços alternativos / Alternative services -->
        <div class="bg-white rounded-xl shadow-lg p-8 mt-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">
                <img src="assets/svg/refresh.svg" class="inline-block w-6 h-6 mr-3" alt="Serviços alternativos">
                <?php echo Language::get('alternative_services'); ?>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="https://archive.today" target="_blank"
                    class="flex items-center p-4 rounded-lg border-2 border-gray-200 hover:border-blue-500 hover:bg-blue-50 transition-all duration-200">
                    <img src="assets/svg/archive.svg" class="w-8 h-8 mr-4" alt="Archive.today">
                    <div>
                        <div class="font-medium text-lg text-gray-800">archive.today</div>
                    </div>
                </a>
                <a href="https://12ft.io" target="_blank"
                    class="flex items-center p-4 rounded-lg border-2 border-gray-200 hover:border-green-500 hover:bg-green-50 transition-all duration-200">
                    <img src="assets/svg/bypass.svg" class="w-8 h-8 mr-4" alt="12ft.io">
                    <div>
                        <div class="font-medium text-lg text-gray-800">12ft.io</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Desenvolvimento / Development -->
        <div class="bg-white rounded-xl shadow-lg p-8 mt-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                <img src="assets/svg/code.svg" class="w-6 h-6 mr-3" alt="API REST">
                <?php echo Language::get('api_title'); ?>
            </h2>
            <div class="space-y-4">
                <p class="text-gray-600">
                    <?php echo str_replace('{site_name}', SITE_NAME, Language::get('api_description')); ?>
                </p>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-medium text-gray-800 mb-2"><?php echo Language::get('endpoint'); ?></h3>
                    <pre class="bg-gray-100 p-3 rounded-lg text-sm overflow-x-auto">GET <?php echo SITE_URL; ?>/api/https://exemplo.com</pre>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-medium text-gray-800 mb-2"><?php echo Language::get('success_response'); ?></h3>
                    <pre class="bg-gray-100 p-3 rounded-lg text-sm overflow-x-auto">
{
    "status": 200,
    "url": "<?php echo SITE_URL; ?>/p/https://exemplo.com"
}</pre>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-medium text-gray-800 mb-2"><?php echo Language::get('error_response'); ?></h3>
                    <pre class="bg-gray-100 p-3 rounded-lg text-sm overflow-x-auto">
{
    "status": 400,
    "error": {
        "code": "INVALID_URL",
        "message": "<?php echo Language::getMessage('INVALID_URL')['message']; ?>"
    }
}</pre>
                </div>
            </div>
            <h2 class="text-xl font-semibold text-gray-800 mt-6 mb-6 flex items-center">
                <img src="assets/svg/code.svg" class="w-6 h-6 mr-3" alt="Open Source">
                <?php echo Language::get('open_source_title'); ?>
            </h2>
            <div>
                <p class="text-gray-600">
                    <?php echo Language::get('open_source_description'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script>
    <?php
    $js_file = 'assets/js/scripts.js';
    if (file_exists($js_file)) {
        echo file_get_contents($js_file);
    }
    ?>
    </script>
</body>
</html>
