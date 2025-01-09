<?php

return [
    'walls_destroyed' => 'Paywall überwunden',
    'url_placeholder' => 'Adresse eingegeben (z.B., https://example.com)',
    'analyze_button' => 'Analysiere',
    'direct_access' => 'Direkter Zugang:',
    'bookmarklet_title' => 'Zu Lesezeichen hinzufügen',
    'bookmarklet_description' => 'Ziehe Sie die Schaltfläche unten in Ihre Lesezeichenleiste, um schnell auf {site_name} zuzugreifen:',
    'open_in' => 'Öffne {site_name}',
    'alternative_services' => 'Alternative Services',
    'api_title' => 'REST API',
    'api_description' => '{site_name} bietet eine REST-API für die Integration mit anderen Systemen:',
    'endpoint' => 'Endpunkt:',
    'success_response' => 'Erfolgreiche Rückmeldung:',
    'error_response' => 'Fehlerhafte Rückmeldung:',
    'open_source_title' => 'Open Source Projekt',
    'open_source_description' => 'Das ist ein <a href="https://github.com/manualdousuario/marreta/" class="underline" target="_blank">Open Source</a> Projekt das mit ❤️ erstellt wurde!<br />Sie können einen Beitrag leisten, Probleme melden oder Vorschläge machen über <a href="https://github.com/manualdousuario/marreta/" class="underline" target="_blank">GitHub</a>.',
    'adblocker_warning' => 'Bei Konflikten zwischen {site_name} und Werbeblockern kann ein weißer Bildschirm angezeigt werden. Verwenden Sie den Inkognito-Modus oder deaktivieren Sie die Erweiterung.',
    
    'messages' => [
        'BLOCKED_DOMAIN' => [
            'message' => 'Diese Seite ist nicht erlaubt.',
            'type' => 'error'
        ],
        'DNS_FAILURE' => [
            'message' => 'DNS für die Domain konnte nicht aufgelöst werden. Bitte überprüfe, ob die URL korrekt ist.',
            'type' => 'warning'
        ],
        'HTTP_ERROR' => [
            'message' => 'Der Server hat beim Zugriff auf die Seite einen Fehler gemeldet. Bitte versuchen Sie es später noch einmal.',
            'type' => 'warning'
        ],
        'CONNECTION_ERROR' => [
            'message' => 'Fehler beim Verbinden mit dem Server. Überprüfen Sie Ihre Verbindung und versuchen Sie es erneut.',
            'type' => 'warning'
        ],
        'CONTENT_ERROR' => [
            'message' => 'Der Inhalt konnte nicht abgerufen werden. Versuchen Sie, Archivdienste zu verwenden.',
            'type' => 'warning'
        ],
        'INVALID_URL' => [
            'message' => 'Ungültiges URL-Format',
            'type' => 'error'
        ],
        'NOT_FOUND' => [
            'message' => 'Seite nicht gefunden',
            'type' => 'error'
        ],
        'GENERIC_ERROR' => [
            'message' => 'Bei der Bearbeitung Ihrer Anfrage ist ein Fehler aufgetreten.',
            'type' => 'warning'
        ]
    ]
];