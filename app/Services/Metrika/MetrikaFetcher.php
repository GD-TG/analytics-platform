<?php

namespace App\Services\Metrika;

use Carbon\Carbon;

class MetrikaFetcher
{
    protected $client;

    public function __construct(MetrikaClient $client)
    {
        $this->client = $client;
    }

    /**
     * Получить данные по визитам и сессиям
     */
    public function fetchVisitsData(int $counterId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->client->getVisitsData(
            $counterId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    /**
     * Получить данные по возрастным группам
     */
    public function fetchAgeData(int $counterId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->client->getAgeData(
            $counterId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    /**
     * Получить данные по целям
     */
    public function fetchGoalsData(int $counterId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->client->getGoalsData(
            $counterId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    /**
     * Получить информацию о счетчике
     */
    public function fetchCounterInfo(int $counterId): array
    {
        return $this->client->getCounterInfo($counterId);
    }

    /**
     * Получить список целей счетчика
     */
    public function fetchCounterGoals(int $counterId): array
    {
        return $this->client->getCounterGoals($counterId);
    }
}