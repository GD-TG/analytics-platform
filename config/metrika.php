<?php

return [
    'api_token' => env('YANDEX_OAUTH_TOKEN'),
    'base_url' => 'https://api-metrica.yandex.net/',
    'timeout' => env('METRIKA_TIMEOUT', 60),
    'connect_timeout' => env('METRIKA_CONNECT_TIMEOUT', 10),

    // Retry middleware configuration (exponential backoff + jitter)
    'max_retries' => env('METRIKA_MAX_RETRIES', 3),
    'retry_base_delay_ms' => env('METRIKA_RETRY_BASE_DELAY_MS', 100),
    'retry_max_delay_seconds' => env('METRIKA_RETRY_MAX_DELAY_SECONDS', 30),
    'retry_jitter_percent' => env('METRIKA_RETRY_JITTER_PERCENT', 25),

    // Rate limiting configuration (requests per minute)
    'rate_limit_per_minute' => env('METRIKA_RATE_LIMIT_PER_MINUTE', 60),
];
