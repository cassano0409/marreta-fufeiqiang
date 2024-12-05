<?php

/**
 * Configurações dos bots
 * 
 * Define os user agents e headers específicos para diferentes bots
 * que podem ser utilizados para fazer requisições
 */
return [
    'Googlebot' => [
        'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +https://www.google.com/bot.html)',
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
];
