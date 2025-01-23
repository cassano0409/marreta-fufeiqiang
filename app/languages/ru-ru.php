<?php

return [
    'walls_destroyed' => 'стены разрушены!',
    'url_placeholder' => 'Введите URL (например, https://example.com)',
    'analyze_button' => 'Анализировать',
    'bookmarklet_title' => 'Добавить в закладки',
    'bookmarklet_description' => 'Перетащите кнопку ниже на панель закладок, чтобы быстро получить доступ к {site_name} на любой странице:',
    'open_in' => 'Открыть в {site_name}',
    'open_source_description' => 'Это <a href="https://github.com/manualdousuario/marreta/" class="underline" target="_blank">проект с открытым исходным кодом</a>, созданный с ❤️!',
    'adblocker_warning' => 'Конфликты между {site_name} и блокировщиками рекламы могут вызывать белый экран. Используйте режим инкогнито или отключите расширение.',
    'add_as_app' => 'Добавить как приложение',
    'add_as_app_description' => 'Установите {site_name} как приложение на Android с Chrome, чтобы быстро делиться ссылками:',
    'add_as_app_step1' => 'В браузере нажмите на значок меню (три точки)',
    'add_as_app_step2' => 'Выберите "Установить приложение" или "Добавить на главный экран".',
    'add_as_app_step3' => 'Нажмите "Установить" для быстрого доступа.',
    'add_as_app_step4' => 'Теперь вы можете напрямую делиться ссылками на {site_name}',
    
    'messages' => [
        'BLOCKED_DOMAIN' => [
            'message' => 'Этот домен заблокирован для извлечения.',
            'type' => 'error'
        ],
        'DNS_FAILURE' => [
            'message' => 'Не удалось разрешить DNS для домена. Проверьте правильность URL.',
            'type' => 'warning'
        ],
        'HTTP_ERROR' => [
            'message' => 'Сервер вернул ошибку при попытке доступа к странице. Повторите попытку позже.',
            'type' => 'warning'
        ],
        'CONNECTION_ERROR' => [
            'message' => 'Ошибка подключения к серверу. Проверьте подключение и попробуйте еще раз.',
            'type' => 'warning'
        ],
        'CONTENT_ERROR' => [
            'message' => 'Не удалось получить контент. Попробуйте использовать архивные сервисы.',
            'type' => 'warning'
        ],
        'INVALID_URL' => [
            'message' => 'Неверный формат URL',
            'type' => 'error'
        ],
        'NOT_FOUND' => [
            'message' => 'Страница не найдена',
            'type' => 'error'
        ],
        'GENERIC_ERROR' => [
            'message' => 'При обработке вашего запроса произошла ошибка.',
            'type' => 'warning'
        ]
    ]
];
