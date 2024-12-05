<?php

/**
 * Classe responsável por impressão de debug
 */
class DebugPrinter {
    /**
     * Imprime uma variável de forma formatada apenas quando o DEBUG está ativo
     * 
     * @param mixed $var Variável a ser impressa
     * @return void
     */
    public static function prettyPrint($var) {
        if (!defined('DEBUG') || !DEBUG) {
            return;
        }

        $output = print_r($var, true);
        
        echo "<pre style=\"
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
        \">" . $output . "</pre>";
    }
}
