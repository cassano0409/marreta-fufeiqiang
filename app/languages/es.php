<?php

return [
    'walls_destroyed' => '¡paredes destruidas!',
    'url_placeholder' => 'Ingrese URL (ej: https://ejemplo.com)',
    'analyze_button' => 'Analizar',
    'direct_access' => 'Acceso directo:',
    'bookmarklet_title' => 'Agregar a Favoritos',
    'bookmarklet_description' => 'Arrastra el botón a tu barra de favoritos para acceder rápidamente a {site_name} en cualquier página:',
    'open_in' => 'Abrir en {site_name}',
    'alternative_services' => 'Servicios Alternativos',
    'api_title' => 'API REST',
    'api_description' => '{site_name} proporciona una API REST para integración con otros sistemas:',
    'endpoint' => 'Endpoint:',
    'success_response' => 'Respuesta exitosa:',
    'error_response' => 'Respuesta de error:',
    'open_source_title' => 'Proyecto de Código Abierto',
    'open_source_description' => '¡Este es un proyecto de <a href="https://github.com/manualdousuario/marreta/" class="underline" target="_blank">código abierto</a> hecho con ❤️!<br />Puedes contribuir, reportar problemas o hacer sugerencias a través de <a href="https://github.com/manualdousuario/marreta/" class="underline" target="_blank">GitHub</a>.',
    'adblocker_warning' => 'Los conflictos entre {site_name} y los bloqueadores de anuncios pueden causar una pantalla en blanco. Use el modo incógnito o desactive la extensión.',
    'add_as_app' => 'Agregar como Aplicación',
    'add_as_app_description' => 'Instale {site_name} como una aplicación para compartir enlaces rápidamente:',
    'add_as_app_step1' => 'En su navegador, haga clic en el icono de menú (tres puntos)',
    'add_as_app_step2' => 'Seleccione "Instalar aplicación" o "Agregar a la pantalla de inicio"',
    'add_as_app_step3' => 'Haga clic en "Instalar" para tener acceso rápido a {site_name}',
    
    'messages' => [
        'BLOCKED_DOMAIN' => [
            'message' => 'Este dominio está bloqueado para extracción.',
            'type' => 'error'
        ],
        'DNS_FAILURE' => [
            'message' => 'Error al resolver DNS para el dominio. Verifique si la URL es correcta.',
            'type' => 'warning'
        ],
        'HTTP_ERROR' => [
            'message' => 'El servidor devolvió un error al intentar acceder a la página. Por favor, inténtelo más tarde.',
            'type' => 'warning'
        ],
        'CONNECTION_ERROR' => [
            'message' => 'Error al conectar con el servidor. Verifique su conexión e inténtelo de nuevo.',
            'type' => 'warning'
        ],
        'CONTENT_ERROR' => [
            'message' => 'No se pudo obtener el contenido. Intente usar los servicios de archivo.',
            'type' => 'warning'
        ],
        'INVALID_URL' => [
            'message' => 'Formato de URL inválido',
            'type' => 'error'
        ],
        'NOT_FOUND' => [
            'message' => 'Página no encontrada',
            'type' => 'error'
        ],
        'GENERIC_ERROR' => [
            'message' => 'Ocurrió un error al procesar su solicitud.',
            'type' => 'warning'
        ]
    ]
];
