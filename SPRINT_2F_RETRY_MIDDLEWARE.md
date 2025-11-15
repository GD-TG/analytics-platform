# ✅ Sprint 2.F: Retry Middleware с Exponential Backoff

## Что было реализовано

### 1. **GuzzleRetryMiddleware** 
**Файл:** `app/Services/Http/GuzzleRetryMiddleware.php`

Профессиональная реализация retry логики для Guzzle HTTP клиента:

#### Особенности:
- ✅ **Exponential backoff** — каждая попытка ждёт в 2x дольше (100ms, 200ms, 400ms, etc)
- ✅ **Jitter** — добавляет случайную задержку (±25% по умолчанию) чтобы избежать thundering herd
- ✅ **Максимальная задержка** — 30 секунд (configurable) чтобы не ждать вечно
- ✅ **Автоматические повторы на:**
  - `429 Too Many Requests` — когда API throttles
  - `503 Service Unavailable` — временные проблемы сервера
  - `504 Gateway Timeout` — проблемы балансировщика нагрузки
  - Connection errors — потеря соединения, timeout'ы и т.д.

#### Математика backoff:
```
delay = base_delay * (2 ^ attempt) + jitter
attempt=0: 100ms + random(0-25ms) = ~100-125ms
attempt=1: 200ms + random(0-50ms) = ~200-250ms
attempt=2: 400ms + random(0-100ms) = ~400-500ms
```

#### Логирование:
```php
// Каждая попытка логируется в storage/logs/laravel.log
Log::warning('Retrying request due to status code', [
    'method' => 'GET',
    'uri' => 'https://api-metrica.yandex.net/stat/v1/data',
    'status' => 429,
    'attempt' => 1,
    'max_attempts' => 3,
    'delay_ms' => 125,
]);
```

### 2. **Обновлён MetrikaClient**
**Файл:** `app/Services/Metrika/MetrikaClient.php`

#### Что изменилось:
- ✅ Добавлен import `GuzzleRetryMiddleware`
- ✅ Создан `HandlerStack` с middleware
- ✅ Middleware подключена к клиенту
- ✅ Удалена старая ручная логика ретраев (was inside while loop)
- ✅ Упрощен `getData()` — теперь просто делает запрос, retry handling в middleware

#### Было:
```php
while ($attempts < $maxAttempts) {
    try {
        // request
        if ($status === 429 || $status >= 500) {
            sleep($waitSeconds[$attempts]);
            $attempts++;
            continue; // manual retry
        }
    } catch (RequestException $e) { ... }
}
```

#### Стало:
```php
$handlerStack = HandlerStack::create();
$handlerStack->push($this->retryMiddleware, 'retry');

$this->client = new Client([
    'handler' => $handlerStack,
    // ... rest of config
]);

// Just make the request - retries happen automatically
$response = $this->client->get('stat/v1/data', ['query' => $query]);
```

### 3. **Конфигурация метрики**
**Файл:** `config/metrika.php`

```php
return [
    'api_token' => env('YANDEX_OAUTH_TOKEN'),
    'timeout' => env('METRIKA_TIMEOUT', 60),
    
    // Retry middleware parameters
    'max_retries' => env('METRIKA_MAX_RETRIES', 3),
    'retry_base_delay_ms' => env('METRIKA_RETRY_BASE_DELAY_MS', 100),
    'retry_max_delay_seconds' => env('METRIKA_RETRY_MAX_DELAY_SECONDS', 30),
    'retry_jitter_percent' => env('METRIKA_RETRY_JITTER_PERCENT', 25),
];
```

## Преимущества

| Аспект | Было | Стало |
|--------|------|-------|
| Retry logic | Ручная в `getData()` | Middleware (Guzzle best practice) |
| Backoff | Fixed delays (1s, 2s, 5s) | Exponential (100ms, 200ms, 400ms, ...) |
| Jitter | ❌ Нет | ✅ ±25% random |
| Connection errors | ❌ Не ретраили | ✅ Автоматически ретраят |
| Логирование | ⚠️ При ошибках | ✅ При каждой попытке |
| Конфигурация | Hardcoded | Через `.env` и `config/metrika.php` |
| Переиспользуемость | Только в MetrikaClient | Можно использовать в других сервисах |

## Как использовать в других сервисах

```php
use App\Services\Http\GuzzleRetryMiddleware;
use GuzzleHttp\HandlerStack;

$handlerStack = HandlerStack::create();
$handlerStack->push(new GuzzleRetryMiddleware(), 'retry');

$client = new Client([
    'handler' => $handlerStack,
    // ... other config
]);
```

## Переменные окружения (.env)

```env
# Retry middleware
METRIKA_MAX_RETRIES=3
METRIKA_RETRY_BASE_DELAY_MS=100
METRIKA_RETRY_MAX_DELAY_SECONDS=30
METRIKA_RETRY_JITTER_PERCENT=25
```

## Примеры в логах

### Успешный retry после 429:
```
[2025-11-15 10:00:01] local.WARNING: Retrying request due to status code {
  "method": "GET",
  "uri": "https://api-metrica.yandex.net/stat/v1/data",
  "status": 429,
  "attempt": 1,
  "max_attempts": 3,
  "delay_ms": 118
}
[2025-11-15 10:00:01] local.INFO: Request succeeded on retry {
  "attempt": 1,
  "status": 200,
  "time_ms": 245
}
```

### Connection error с retry:
```
[2025-11-15 10:05:30] local.WARNING: Retrying request due to exception {
  "method": "GET",
  "uri": "https://api-metrica.yandex.net/stat/v1/data",
  "exception": "GuzzleHttp\\ConnectException",
  "message": "cURL error 7: Failed to connect to api-metrica.yandex.net",
  "attempt": 1,
  "max_attempts": 3,
  "delay_ms": 234
}
```

## Статус

✅ **COMPLETED** — Retry middleware готов к production использованию

**Дальше:**
- Sprint 2.G: Rate limiting per-account (Redis leaky bucket)
- Sprint 2.H: Scheduled sync (Laravel Scheduler)
- Sprint 2.I: Metrics dashboard

