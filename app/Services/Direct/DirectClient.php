<?php

namespace App\Services\Direct;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class DirectClient
{
    protected $token;
    protected $baseUrl;
    protected $clientLogin;
    protected $client;

    public function __construct()
    {
        $this->token = Config::get('direct.api_token') ?: Config::get('integrations.yandex.oauth_token');
        $this->baseUrl = Config::get('direct.base_url', 'https://api.direct.yandex.com');
        $this->clientLogin = Config::get('direct.client_login');
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept-Language' => 'ru',
            ],
            'timeout' => 60,
            'connect_timeout' => 10,
        ]);
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept-Language' => 'ru',
            ],
            'timeout' => 60,
            'connect_timeout' => 10,
        ]);
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getClientLogin(): ?string
    {
        return $this->clientLogin;
    }

    public function setClientLogin(string $clientLogin): self
    {
        $this->clientLogin = $clientLogin;
        return $this;
    }

    /**
     * Базовый метод для выполнения API запросов к Яндекс.Директу
     */
    public function makeRequest(string $service, string $method, array $params = []): array
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
                'Accept-Language' => 'ru',
            ];

            // Добавляем Client-Login заголовок, если указан
            if ($this->clientLogin) {
                $headers['Client-Login'] = $this->clientLogin;
            }

            $client = new Client([
                'base_uri' => $this->baseUrl,
                'headers' => $headers,
                'timeout' => 60,
                'connect_timeout' => 10,
            ]);

            $response = $client->post("json/v5/{$service}", [
                'json' => [
                    'method' => $method,
                    'params' => $params,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['error'])) {
                Log::error('Yandex Direct API error', [
                    'service' => $service,
                    'method' => $method,
                    'error' => $body['error'],
                ]);
                throw new \Exception('Yandex Direct API error: ' . ($body['error']['error_string'] ?? 'Unknown error'));
            }

            return $body['result'] ?? [];
        } catch (RequestException $e) {
            Log::error('Yandex Direct API request error', [
                'service' => $service,
                'method' => $method,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            throw new \Exception('Yandex Direct API request error: ' . $e->getMessage());
        }
    }

    /**
     * Получить список кампаний
     */
    public function getCampaigns(): array
    {
        $result = $this->makeRequest('campaigns', 'get', [
            'SelectionCriteria' => new \stdClass(),
            'FieldNames' => ['Id', 'Name', 'Status', 'Type', 'Currency'],
        ]);

        return $result['Campaigns'] ?? [];
    }

    /**
     * Получить статистику по кампаниям за период
     * Используем Reports API v5
     */
    public function getCampaignStats(array $campaignIds, string $startDate, string $endDate): array
    {
        if (empty($campaignIds)) {
            return [];
        }

        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept-Language' => 'ru',
                'Content-Type' => 'application/json',
            ];

            if ($this->clientLogin) {
                $headers['Client-Login'] = $this->clientLogin;
            }

            $client = new Client([
                'base_uri' => $this->baseUrl,
                'headers' => $headers,
                'timeout' => 120,
                'connect_timeout' => 10,
            ]);

            // Используем Reports API
            $reportParams = [
                'SelectionCriteria' => [
                    'Filter' => [
                        [
                            'Field' => 'CampaignId',
                            'Operator' => 'IN',
                            'Values' => $campaignIds,
                        ],
                    ],
                ],
                'FieldNames' => [
                    'CampaignId',
                    'CampaignName',
                    'Impressions',
                    'Clicks',
                    'Cost',
                    'Ctr',
                    'AvgCpc',
                    'Conversions',
                    'CostPerConversion',
                ],
                'ReportName' => 'Campaign Performance Report',
                'ReportType' => 'CAMPAIGN_PERFORMANCE_REPORT',
                'DateRangeType' => 'CUSTOM_DATE',
                'Format' => 'TSV',
                'IncludeVAT' => 'NO',
                'IncludeDiscount' => 'NO',
                'DateFrom' => $startDate,
                'DateTo' => $endDate,
            ];

            // Создаем отчет
            $createResponse = $client->post('json/v5/reports', [
                'json' => [
                    'method' => 'get',
                    'params' => $reportParams,
                ],
            ]);

            $createBody = json_decode($createResponse->getBody()->getContents(), true);

            if (isset($createBody['error'])) {
                throw new \Exception('Failed to create report: ' . ($createBody['error']['error_string'] ?? 'Unknown error'));
            }

            $reportId = $createBody['result']['ReportId'] ?? null;
            if (!$reportId) {
                throw new \Exception('Report ID not received');
            }

            // Ждем готовности отчета и получаем данные
            $reportData = '';
            $maxAttempts = 10;
            $attempt = 0;

            while ($attempt < $maxAttempts) {
                sleep(2);

                $getResponse = $client->get('json/v5/reports', [
                    'query' => [
                        'reportId' => $reportId,
                    ],
                ]);

                $getBody = $getResponse->getBody()->getContents();

                if (strpos($getBody, 'CampaignId') !== false || strpos($getBody, 'ReportId') === false) {
                    $reportData = $getBody;
                    break;
                }

                $attempt++;
            }

            if (empty($reportData)) {
                throw new \Exception('Failed to retrieve report data after ' . $maxAttempts . ' attempts');
            }

            // Парсим TSV данные
            return $this->parseTsvReport($reportData);
        } catch (RequestException $e) {
            Log::error('Yandex Direct report error', [
                'campaign_ids' => $campaignIds,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            throw new \Exception('Yandex Direct report error: ' . $e->getMessage());
        }
    }

    /**
     * Парсинг TSV отчета
     */
    protected function parseTsvReport(string $tsvData): array
    {
        $lines = explode("\n", trim($tsvData));
        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv($lines[0], "\t");
        $data = [];

        for ($i = 1; $i < count($lines); $i++) {
            if (empty(trim($lines[$i]))) {
                continue;
            }

            $values = str_getcsv($lines[$i], "\t");
            if (count($values) !== count($headers)) {
                continue;
            }

            $row = array_combine($headers, $values);
            $data[] = [
                'campaign_id' => (int) ($row['CampaignId'] ?? 0),
                'campaign_name' => $row['CampaignName'] ?? '',
                'impressions' => (int) ($row['Impressions'] ?? 0),
                'clicks' => (int) ($row['Clicks'] ?? 0),
                'cost' => (float) ($row['Cost'] ?? 0),
                'ctr' => (float) ($row['Ctr'] ?? 0),
                'avg_cpc' => (float) ($row['AvgCpc'] ?? 0),
                'conversions' => (int) ($row['Conversions'] ?? 0),
                'cpa' => (float) ($row['CostPerConversion'] ?? 0),
            ];
        }

        return $data;
    }

    /**
     * Получить статистику по всем кампаниям (итоги)
     */
    public function getTotalStats(string $startDate, string $endDate): array
    {
        $campaigns = $this->getCampaigns();
        $campaignIds = array_column($campaigns, 'Id');

        if (empty($campaignIds)) {
            return [
                'impressions' => 0,
                'clicks' => 0,
                'cost' => 0,
                'ctr' => 0,
                'avg_cpc' => 0,
                'conversions' => 0,
                'cpa' => 0,
            ];
        }

        $stats = $this->getCampaignStats($campaignIds, $startDate, $endDate);

        $totals = [
            'impressions' => 0,
            'clicks' => 0,
            'cost' => 0,
            'conversions' => 0,
        ];

        foreach ($stats as $stat) {
            $totals['impressions'] += $stat['impressions'];
            $totals['clicks'] += $stat['clicks'];
            $totals['cost'] += $stat['cost'];
            $totals['conversions'] += $stat['conversions'];
        }

        $totals['ctr'] = $totals['impressions'] > 0 
            ? ($totals['clicks'] / $totals['impressions']) * 100 
            : 0;
        $totals['avg_cpc'] = $totals['clicks'] > 0 
            ? $totals['cost'] / $totals['clicks'] 
            : 0;
        $totals['cpa'] = $totals['conversions'] > 0 
            ? $totals['cost'] / $totals['conversions'] 
            : 0;

        return $totals;
    }
}
