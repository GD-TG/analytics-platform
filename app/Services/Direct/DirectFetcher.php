<?php

namespace App\Services\Direct;

use App\Models\DirectAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DirectFetcher
{
    protected $client;

    public function __construct(DirectClient $client)
    {
        $this->client = $client;
    }

    /**
     * Получить данные по кампаниям
     */
    public function fetchCampaignsData(DirectAccount $account, Carbon $startDate, Carbon $endDate): array
    {
        $this->client->setClientLogin($account->client_login);

        // Получаем список кампаний
        $campaigns = $this->client->getCampaigns();
        
        if (empty($campaigns)) {
            Log::warning("No campaigns found for account {$account->client_login}");
            return [];
        }

        $campaignIds = array_column($campaigns, 'Id');
        
        // Получаем статистику по кампаниям
        $stats = $this->client->getCampaignStats(
            $campaignIds,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        return [
            'campaigns' => $campaigns,
            'stats' => $stats
        ];
    }

    /**
     * Получить итоговые данные
     */
    public function fetchTotalsData(DirectAccount $account, Carbon $startDate, Carbon $endDate): array
    {
        $this->client->setClientLogin($account->client_login);

        return $this->client->getTotalStats(
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }
}

