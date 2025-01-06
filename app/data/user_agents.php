<?php

/**
 * Bot configurations
 * Configurações dos bots
 * 
 * Defines user agents that can be used to make requests
 * Define os user agents que podem ser utilizados para fazer requisições
 * 
 * These user agents are used to simulate legitimate web crawlers
 * Estes user agents são usados para simular crawlers web legítimos
 */
return [
    // Google News bot
    // Bot do Google News
    'Googlebot-News',

    // Mobile Googlebot
    // Googlebot para dispositivos móveis
    'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/W.X.Y.Z Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',

    // Desktop Googlebot
    // Googlebot para desktop
    'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Chrome/W.X.Y.Z Safari/537.36'
];
