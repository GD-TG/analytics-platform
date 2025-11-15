# ✅ Sprint 2.G: Rate Limiting per-Account

## Реализовано

### 1. **ApiRateLimiter** — Leaky Bucket Algorithm
**Файл:** `app/Services/RateLimiting/ApiRateLimiter.php`

Реализует классический алгоритм **Leaky Bucket** для rate limiting:

#### Как работает:
```
Bucket наполняется на каждый запрос:
- Предел: 60 запросов в минуту (configurable)
- Если текущий уровень + запрос > лимит → 429 Too Many Requests
- Bucket "протекает" со временем (exponential decay)

Пример:
- Время 0:00 — запрос #1 → bucket = 1
- Время 0:01 — запрос #2 → bucket = 2
- Время 0:10 — bucket частично опорожнился → bucket ≈ 1.8
- Если bucket + новый запрос > 60 → reject
```

#### Методы:

**`allow($accountId, $tokens = 1): bool`**
- Проверяет, может ли аккаунт сделать запрос
- `true` — запрос разрешен
- `false` — rate limit exceeded, нужно retry позже

```php
$limiter = new ApiRateLimiter(60); // 60 requests/min

if ($limiter->allow($accountId, 1)) {
    // Make API call
} else {
    // Queue for later
    Log::warning("Rate limited for account {$accountId}");
}
```

**`getRetryAfterSeconds($currentTokens, $tokens, $limit): int`**
- Вычисляет, сколько ждать до следующей попытки

```php
$retryAfter = $limiter->getRetryAfterSeconds(45, 1, 60);
// Result: 1 second (bucket имеет 45 tokens, нужен еще 1, лимит 60)
```

**`getStatus($accountId): array`**
- Получить текущий статус

```php
$status = $limiter->getStatus($accountId);
// [
//   'account_id' => 12345,
//   'tokens_used' => 45,
//   'limit' => 60,
//   'available' => 15
// ]
```

**`reset($accountId): void`**
- Сброс лимита (для администраторов)

#### Почему Leaky Bucket?
| Алгоритм | Плюсы | Минусы |
|----------|-------|--------|
| **Leaky Bucket** | Гладкий поток, предсказуемо | Требует памяти для состояния |
| Token Bucket | Burstable (всплеск разрешен) | Менее предсказуемо |
| Sliding Window | Простой | Потребляет больше памяти |
| Fixed Window | Очень простой | Проблемы на границах окна |

Мы выбрали **Leaky Bucket** потому что:
- ✅ Гарантирует равномерный поток к API Yandex
- ✅ Избегает thundering herd (все одновременно не напрыгают)
- ✅ Легко спрогнозировать время retry

### 2. **GuzzleRateLimitMiddleware** — интеграция в Guzzle
**Файл:** `app/Services/Http/GuzzleRateLimitMiddleware.php`

Middleware который проверяет rate limit **до** отправки запроса:

#### Использование:
```php
use App\Services\RateLimiting\ApiRateLimiter;
use App\Services\Http\GuzzleRateLimitMiddleware;
use GuzzleHttp\HandlerStack;

$limiter = new ApiRateLimiter(60);
$middleware = new GuzzleRateLimitMiddleware($limiter);

$handlerStack = HandlerStack::create();
$handlerStack->push($middleware, 'rate_limit');

$client = new Client(['handler' => $handlerStack]);
```

#### Как работает:
1. Middleware перехватывает запрос
2. Извлекает `account_id` из опций (`options['account_id']`)
3. Проверяет rate limit через `ApiRateLimiter::allow()`
4. Если разрешено → отправляет запрос дальше (обработка в retry middleware)
5. Если отклонено → возвращает `RejectedPromise` с ошибкой 429

#### Custom Account ID Extractor:
```php
$extractor = function (RequestInterface $request, array $options) {
    // Custom logic to get account ID from request
    return $options['custom_account_field'] ?? null;
};

new GuzzleRateLimitMiddleware($limiter, $extractor);
```

### 3. **Интеграция в MetrikaClient**
**Файл:** `app/Services/Metrika/MetrikaClient.php`

**Handler Stack Order (важно!):**
```php
$handlerStack = HandlerStack::create();
$handlerStack->push($this->rateLimitMiddleware, 'rate_limit');  // First
$handlerStack->push($this->retryMiddleware, 'retry');           // Second
```

**Почему в таком порядке?**
1. **Rate limit** — отклоняет запросы если лимит превышен (fast reject)
2. **Retry** — повторяет запросы при 429/5xx ошибках от API

Так как оба middleware'а могут вернуть 429, порядок критичен:
- Rate limit проверяет локально (Redis)
- Retry обрабатывает ошибки от API

### 4. **Конфигурация** 
**Файл:** `config/metrika.php`

```php
'rate_limit_per_minute' => env('METRIKA_RATE_LIMIT_PER_MINUTE', 60),
```

**Переменные окружения (.env):**
```env
# Rate limiting (requests per minute)
METRIKA_RATE_LIMIT_PER_MINUTE=60
```

## Логирование

### При отклонении запроса:
```
[2025-11-15 10:30:45] local.WARNING: Rate limit exceeded for account {
  "account_id": 12345,
  "current_tokens": 58,
  "requested_tokens": 1,
  "limit": 60,
  "retry_after_seconds": 1
}
```

### При отклонении в middleware:
```
[2025-11-15 10:30:45] local.WARNING: Request rejected due to rate limit {
  "account_id": 12345,
  "method": "GET",
  "uri": "https://api-metrica.yandex.net/stat/v1/data",
  "retry_after_seconds": 1
}
```

### При ошибке Redis (graceful degradation):
```
[2025-11-15 10:30:45] local.ERROR: Rate limiter error {
  "account_id": 12345,
  "error": "Redis connection failed"
}
// Request is ALLOWED (don't break service)
```

## Интеграция с Job Queue

Когда job получает 429, он должен retry позже:

```php
// app/Jobs/FetchMetrikaJob.php
try {
    $data = $client->getData($counterId, ['metrics' => '...']);
} catch (\Exception $e) {
    if ($e->getCode() === 429) {
        // Rate limited - retry after delay
        $retryAfter = $e->getMessage(); // "Rate limited: retry after 5s"
        return $this->release(delay: 5); // Laravel Job queue release
    }
    throw $e;
}
```

## Статус

✅ **COMPLETED** — Rate limiting готов к production использованию

**Особенности:**
- ✅ Per-account rate limiting (не глобальное)
- ✅ Leaky bucket алгоритм (гладкий поток)
- ✅ Redis backed (масштабируемо)
- ✅ Graceful degradation (если Redis down — request allowed)
- ✅ Custom account ID extractor
- ✅ Логирование на каждом уровне

## Комбинация с Retry Middleware

| Сценарий | Rate Limiter | Retry Middleware | Результат |
|----------|--------------|------------------|-----------|
| Нормальный запрос | ✅ Allow | ✅ Success 200 | ✅ Data returned |
| Лимит превышен | ❌ Reject 429 | - | ❌ RejectedPromise (queue it) |
| API returns 429 | ✅ Allow | ✅ Retry 3x | ✅ Eventually succeeds |
| API returns 503 | ✅ Allow | ✅ Retry 3x | ✅ Eventually succeeds |
| Network error | ✅ Allow | ✅ Retry 3x | ✅ Eventually succeeds |

## Следующие шаги

**Sprint 2.H: Scheduled Sync** — добавить Laravel Scheduler для периодической синхронизации данных

