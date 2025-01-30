<?php

namespace Inc;

/**
 * Manages language translations and localization
 * Loads language files based on system configuration
 * Provides fallback to default language on missing resources
 */
class Language {
    private static $translations = [];
    private static $currentLanguage = 'pt-br';

    /**
     * Initializes language resources
     * @param string $language ISO language code (e.g., 'en', 'pt-br')
     */
    public static function init($language = 'pt-br') {
        self::$currentLanguage = strtolower($language);
        $langFile = __DIR__ . '/../languages/' . self::$currentLanguage . '.php';
        
        if (file_exists($langFile)) {
            self::$translations = require $langFile;
        } else {
            // Fallback to default language
            self::$currentLanguage = 'pt-br';
            self::$translations = require __DIR__ . '/../languages/pt-br.php';
        }
    }

    /**
     * Retrieves translation for specified key
     * @param string $key Translation identifier
     * @param string $default Fallback value if key not found
     */
    public static function get($key, $default = '') {
        return self::$translations[$key] ?? $default;
    }

    /**
     * Gets structured message data
     * @param string $key Message identifier
     * @return array Message content and type
     */
    public static function getMessage($key) {
        return self::$translations['messages'][$key] ?? [
            'message' => 'Unknown message',
            'type' => 'error'
        ];
    }

    /** Gets active language code */
    public static function getCurrentLanguage() {
        return self::$currentLanguage;
    }
}
