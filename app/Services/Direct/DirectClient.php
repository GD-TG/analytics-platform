<?php

namespace App\Services\Direct;

use Illuminate\Support\Facades\Config;

class DirectClient
{
    protected $token;
    protected $baseUrl;
    protected $clientLogin;

    public function __construct()
    {
        $this->token = Config::get('direct.api_token');
        $this->baseUrl = Config::get('direct.base_url', 'https://api.direct.yandex.com');
        $this->clientLogin = Config::get('direct.client_login');
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
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
     * Базовый метод для выполнения API запросов
     */
    public function makeRequest(string $endpoint, array $params = [], string $method = 'POST')
    {
        // Здесь будет логика запросов к API Директа
        // Пока заглушка
        return [
            'endpoint' => $endpoint,
            'params' => $params,
            'method' => $method
        ];
    }

    /**
     * Получить список кампаний
     */
    public function getCampaigns()
    {
        return $this->makeRequest('campaigns', [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => new \stdClass(),
                'FieldNames' => ['Id', 'Name', 'Status']
            ]
        ]);
    }

    /**
     * Получить статистику по кампаниям
     */
    public function getCampaignStats(array $campaignIds, string $startDate, string $endDate)
    {
        return $this->makeRequest('reports', [
            'params' => [
                'SelectionCriteria' => [
                    'Filter' => [
                        [
                            'Field' => 'CampaignId',
                            'Operator' => 'IN',
                            'Values' => $campaignIds
                        ]
                    ]
                ],
                'FieldNames' => ['CampaignId', 'Impressions', 'Clicks', 'Cost'],
                'ReportName' => 'Campaign Performance',
                'ReportType' => 'CAMPAIGN_PERFORMANCE_REPORT',
                'DateRangeType' => 'CUSTOM_DATE',
                'Format' => 'TSV',
                'IncludeVAT' => 'NO',
                'Page' => new \stdClass()
            ]
        ]);
    }
}