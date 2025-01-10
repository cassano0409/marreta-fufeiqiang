<?php

return [
    'walls_destroyed' => 'walls destroyed!',
    'url_placeholder' => 'Enter URL (e.g., https://example.com)',
    'analyze_button' => 'Analyze',
    'direct_access' => 'Direct access:',
    'bookmarklet_title' => 'Add to Bookmarks',
    'bookmarklet_description' => 'Drag the button below to your bookmarks bar to quickly access {site_name} on any page:',
    'open_in' => 'Open in {site_name}',
    'alternative_services' => 'Alternative Services',
    'api_title' => 'REST API',
    'api_description' => '{site_name} provides a REST API for integration with other systems:',
    'endpoint' => 'Endpoint:',
    'success_response' => 'Success response:',
    'error_response' => 'Error response:',
    'open_source_title' => 'Open Source Project',
    'open_source_description' => 'This is an <a href="https://github.com/manualdousuario/marreta/" class="underline" target="_blank">open source</a> project made with ❤️!<br />You can contribute, report issues, or make suggestions through <a href="https://github.com/manualdousuario/marreta/" class="underline" target="_blank">GitHub</a>.',
    'adblocker_warning' => 'Conflicts between {site_name} and ad blockers may cause a white screen. Use incognito mode or disable the extension.',
    'add_as_app' => 'Add as App',
    'add_as_app_description' => 'Install {site_name} as an app for quick link sharing:',
    'add_as_app_step1' => 'In your browser, click the menu icon (three dots)',
    'add_as_app_step2' => 'Select "Install app" or "Add to home screen"',
    'add_as_app_step3' => 'Click "Install" for quick access',
    'add_as_app_step4' => 'Now you can directly share links to {site_name}',
    
    'messages' => [
        'BLOCKED_DOMAIN' => [
            'message' => 'This domain is blocked for extraction.',
            'type' => 'error'
        ],
        'DNS_FAILURE' => [
            'message' => 'Failed to resolve DNS for the domain. Please verify if the URL is correct.',
            'type' => 'warning'
        ],
        'HTTP_ERROR' => [
            'message' => 'The server returned an error while trying to access the page. Please try again later.',
            'type' => 'warning'
        ],
        'CONNECTION_ERROR' => [
            'message' => 'Error connecting to the server. Check your connection and try again.',
            'type' => 'warning'
        ],
        'CONTENT_ERROR' => [
            'message' => 'Could not get content. Try using archive services.',
            'type' => 'warning'
        ],
        'INVALID_URL' => [
            'message' => 'Invalid URL format',
            'type' => 'error'
        ],
        'NOT_FOUND' => [
            'message' => 'Page not found',
            'type' => 'error'
        ],
        'GENERIC_ERROR' => [
            'message' => 'An error occurred while processing your request.',
            'type' => 'warning'
        ]
    ]
];
