<?php

class Language {
    private static $translations = [];
    private static $currentLanguage = 'pt-br';

    public static function init($language = 'pt-br') {
        self::$currentLanguage = strtolower($language);
        $langFile = __DIR__ . '/../languages/' . self::$currentLanguage . '.php';
        
        if (file_exists($langFile)) {
            self::$translations = require $langFile;
        } else {
            // Fallback to pt-br if language file doesn't exist
            self::$currentLanguage = 'pt-br';
            self::$translations = require __DIR__ . '/../languages/pt-br.php';
        }
    }

    public static function get($key, $default = '') {
        return self::$translations[$key] ?? $default;
    }

    public static function getMessage($key) {
        return self::$translations['messages'][$key] ?? [
            'message' => 'Unknown message',
            'type' => 'error'
        ];
    }

    public static function getCurrentLanguage() {
        return self::$currentLanguage;
    }
}