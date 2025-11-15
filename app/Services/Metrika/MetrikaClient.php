<?php 

namespace App\Services\Metrika; 

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Services\Http\GuzzleRetryMiddleware;
use App\Services\Http\GuzzleRateLimitMiddleware;
use App\Services\RateLimiting\ApiRateLimiter;

class MetrikaClient 
{ 
    private Client $client; 
    private string $token;
    private string $baseUrl;
    private GuzzleRetryMiddleware $retryMiddleware;
    private GuzzleRateLimitMiddleware $rateLimitMiddleware;

    public function __construct() 
    { 
        $this->token = Config::get('metrika.api_token');
        $this->baseUrl = 'https://api-metrica.yandex.net/';
        
        if (empty($this->token)) {
            Log::warning('Yandex Metrika token is empty. Set YANDEX_OAUTH_TOKEN in .env or config/metrika.php');
        }

        // Setup retry middleware
        $this->retryMiddleware = new GuzzleRetryMiddleware(
            maxRetries: Config::get('metrika.max_retries', 3),
            baseDelayMs: Config::get('metrika.retry_base_delay_ms', 100),
            maxDelaySeconds: Config::get('metrika.retry_max_delay_seconds', 30),
            jitterPercent: Config::get('metrika.retry_jitter_percent', 25)
        );

        // Setup rate limit middleware
        $rateLimiter = new ApiRateLimiter(
            requestsPerMinute: Config::get('metrika.rate_limit_per_minute', 60)
        );
        $this->rateLimitMiddleware = new GuzzleRateLimitMiddleware($rateLimiter);

        // Create handler stack with both middlewares
        // Order matters: rate limit first (reject early), then retry
        $handlerStack = HandlerStack::create();
        $handlerStack->push($this->rateLimitMiddleware, 'rate_limit');
        $handlerStack->push($this->retryMiddleware, 'retry');

        // Базовый клиент Guzzle с базовыми заголовками и middleware'ами
        $defaultHeaders = [
            'Authorization' => 'OAuth ' . $this->token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'analytics-platform/1.0 (+https://example.com)',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        ];

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => $defaultHeaders,
            'timeout' => Config::get('metrika.timeout', 60),
            'connect_timeout' => Config::get('metrika.connect_timeout', 10),
            'handler' => $handlerStack,
        ]);
    }

    /**
     * Основной метод для получения данных
     * 
     * Retry logic handled by GuzzleRetryMiddleware:
     * - Retries on 429, 503, 504 status codes
     * - Exponential backoff with jitter
     * - Connection errors automatically retried
     */
    public function getData(int $counterId, array $params): array 
    { 
        // Приводим ids к строке и объединяем параметры
        $query = array_merge(['ids' => (string) $counterId], $params);

        try {
            $response = $this->client->get('stat/v1/data', [
                'query' => $query
            ]);

            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Yandex Metrika response JSON', ['body' => $body]);
                throw new \Exception('Invalid JSON from Yandex Metrika');
            }

            return $decoded;
        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;

            // Логируем детально
            Log::error('Yandex Metrika API error (after retries)', [
                'counter_id' => $counterId,
                'params' => $query,
                'status' => $status,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            throw new \Exception('Yandex Metrika API error: ' . $e->getMessage());
        }
    }

    /**
     * Получить данные по визитам и сессиям
     */
    public function getVisitsData(int $counterId, string $startDate, string $endDate): array
    {
        return $this->getData($counterId, [
            'metrics' => 'ym:s:visits,ym:s:users,ym:s:pageviews,ym:s:bounceRate,ym:s:avgVisitDurationSeconds',
            'dimensions' => 'ym:s:date',
            'date1' => $startDate,
            'date2' => $endDate,
            'limit' => 10000
        ]);
    }

    /**
     * Получить данные по возрастным группам
     */
    public function getAgeData(int $counterId, string $startDate, string $endDate): array
    {
        return $this->getData($counterId, [
            'metrics' => 'ym:s:visits,ym:s:users,ym:s:bounceRate,ym:s:avgVisitDurationSeconds',
            'dimensions' => 'ym:s:ageInterval,ym:s:date',
            'date1' => $startDate,
            'date2' => $endDate,
            'filters' => "ym:s:ageInterval!=''",
            'limit' => 10000
        ]);
    }

    /**
     * Получить данные по целям
     */
    public function getGoalsData(int $counterId, string $startDate, string $endDate): array
    {
        return $this->getData($counterId, [
            'metrics' => 'ym:s:goalReaches',
            'dimensions' => 'ym:s:goalID,ym:s:date',
            'date1' => $startDate,
            'date2' => $endDate,
            'filters' => "ym:s:goalID!=''",
            'limit' => 10000
        ]);
    }

    /**
     * Получить информацию о счетчике
     */
    public function getCounterInfo(int $counterId): array
    {
        try {
            $response = $this->client->get("management/v1/counter/{$counterId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Failed to get counter info', [
                'counter_id' => $counterId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получить список целей счетчика
     */
    public function getCounterGoals(int $counterId): array
    {
        try {
            $response = $this->client->get("management/v1/counter/{$counterId}/goals");
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Failed to get counter goals', [
                'counter_id' => $counterId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Проверить доступность API
     */
    public function checkAvailability(): bool
    {
        try {
            $response = $this->client->get('management/v1/counters');
            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            return false;
        }
    }

    /**
     * Установить кастомные заголовки
     */
    public function setHeaders(array $headers): void
    {
        // Merge headers but don't overwrite base_uri/timeouts
        $merged = array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'analytics-platform/1.0',
        ], $headers);

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => $merged,
            'timeout' => Config::get('metrika.timeout', 60),
            'connect_timeout' => Config::get('metrika.connect_timeout', 10),
        ]);
    }

    /**
     * Получить информацию о лимитах API
     */
    public function getRateLimitInfo(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'timeout' => 60,
            'connect_timeout' => 10,
        ];
    }
}