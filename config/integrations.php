<?php

return [
    'yandex' => [
        'client_id' => env('YANDEX_CLIENT_ID'),
        'client_secret' => env('YANDEX_CLIENT_SECRET'),
        'oauth_token' => env('YANDEX_OAUTH_TOKEN'),
        'default_currency' => env('YANDEX_DEFAULT_CURRENCY', 'RUB'),
        'default_timezone' => env('DEFAULT_TIMEZONE', 'Europe/Moscow'),
    ],
];

