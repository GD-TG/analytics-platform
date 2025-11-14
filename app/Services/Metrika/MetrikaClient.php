<?php 

namespace App\Services\Metrika; 

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class MetrikaClient 
{ 
    private Client $client; 
    private string $token;
    private string $baseUrl;

    public function __construct() 
    { 
        $this->token = Config::get('metrika.api_token');
        $this->baseUrl = 'https://api-metrica.yandex.net/';
        
        $this->client = new Client([ 
            'base_uri' => $this->baseUrl,
            'headers' => [ 
                'Authorization' => 'OAuth ' . $this->token, 
                'Content-Type' => 'application/json', 
            ],
            'timeout' => 60,
            'connect_timeout' => 10,
        ]); 
    }

    /**
     * Основной метод для получения данных
     */
    public function getData(int $counterId, array $params): array 
    { 
        try { 
            $response = $this->client->get('stat/v1/data', [
                'query' => array_merge(['ids' => $counterId], $params)
            ]); 
            
            return json_decode($response->getBody()->getContents(), true); 
        } catch (RequestException $e) { 
            Log::error('Yandex Metrika API error', [
                'counter_id' => $counterId,
                'params' => $params,
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
        $this->client = new Client([ 
            'base_uri' => $this->baseUrl,
            'headers' => array_merge([
                'Authorization' => 'OAuth ' . $this->token, 
                'Content-Type' => 'application/json',
            ], $headers),
            'timeout' => 60,
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