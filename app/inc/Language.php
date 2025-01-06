<?php

/**
 * Language Management Class
 * Classe de Gerenciamento de Idiomas
 * 
 * This class handles the loading and retrieval of language-specific strings
 * Esta classe lida com o carregamento e recuperação de strings específicas do idioma
 * 
 * Features / Funcionalidades:
 * - Language initialization / Inicialização de idioma
 * - Translation retrieval / Recuperação de traduções
 * - Message handling / Manipulação de mensagens
 * - Fallback language support / Suporte a idioma de fallback
 */
class Language {
    private static $translations = [];
    private static $currentLanguage = 'pt-br';

    /**
     * Initialize the language system
     * Inicializa o sistema de idiomas
     * 
     * @param string $language Language code (e.g., 'en', 'pt-br') / Código do idioma (ex: 'en', 'pt-br')
     */
    public static function init($language = 'pt-br') {
        self::$currentLanguage = strtolower($language);
        $langFile = __DIR__ . '/../languages/' . self::$currentLanguage . '.php';
        
        if (file_exists($langFile)) {
            self::$translations = require $langFile;
        } else {
            // Fallback to pt-br if language file doesn't exist
            // Volta para pt-br se o arquivo de idioma não existir
            self::$currentLanguage = 'pt-br';
            self::$translations = require __DIR__ . '/../languages/pt-br.php';
        }
    }

    /**
     * Get a translation by key
     * Obtém uma tradução por chave
     * 
     * @param string $key Translation key / Chave da tradução
     * @param string $default Default value if key not found / Valor padrão se a chave não for encontrada
     * @return string Translation text / Texto traduzido
     */
    public static function get($key, $default = '') {
        return self::$translations[$key] ?? $default;
    }

    /**
     * Get a message by key
     * Obtém uma mensagem por chave
     * 
     * @param string $key Message key / Chave da mensagem
     * @return array Message data (message and type) / Dados da mensagem (mensagem e tipo)
     */
    public static function getMessage($key) {
        return self::$translations['messages'][$key] ?? [
            'message' => 'Unknown message',
            'type' => 'error'
        ];
    }

    /**
     * Get current language code
     * Obtém o código do idioma atual
     * 
     * @return string Current language code / Código do idioma atual
     */
    public static function getCurrentLanguage() {
        return self::$currentLanguage;
    }
}