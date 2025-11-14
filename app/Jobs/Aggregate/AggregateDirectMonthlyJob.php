<?php

namespace App\Jobs\Aggregate;

use App\Models\Project;
use App\Models\MonthlyDirect;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateDirectMonthlyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $project;
    public $aggregationPeriod;
    public $timeout = 600; // 10 минут
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Project $project, array $aggregationPeriod)
    {
        $this->project = $project;
        $this->aggregationPeriod = $aggregationPeriod;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $month = $this->aggregationPeriod['month_key'];
        $startDate = $this->aggregationPeriod['start'];
        $endDate = $this->aggregationPeriod['end'];

        try {
            DB::beginTransaction();

            // Получаем агрегированные данные по кампаниям проекта
            $campaignsData = $this->getAggregatedCampaignsData($startDate, $endDate);
            
            // Создаем или обновляем запись MonthlyDirect
            $monthlyDirect = MonthlyDirect::updateOrCreate(
                [
                    'project_id' => $this->project->id,
                    'month' => $month,
                ],
                $this->prepareMonthlyData($campaignsData)
            );

            // Обновляем связь с кампаниями
            $this->updateCampaignsMonthlyData($monthlyDirect, $campaignsData);

            DB::commit();

            \Log::info("Monthly Direct aggregation completed for project {$this->project->id}, month {$month}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to aggregate Direct monthly data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Получить агрегированные данные по кампаниям
     */
    private function getAggregatedCampaignsData($startDate, $endDate): array
    {
        return Campaign::where('project_id', $this->project->id)
            ->whereHas('dailyData', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->with(['dailyData' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            }])
            ->get()
            ->map(function ($campaign) {
                return $this->aggregateCampaignData($campaign);
            })
            ->toArray();
    }

    /**
     * Агрегировать данные по одной кампании
     */
    private function aggregateCampaignData(Campaign $campaign): array
    {
        $dailyData = $campaign->dailyData;

        if ($dailyData->isEmpty()) {
            return [
                'campaign_id' => $campaign->id,
                'clicks' => 0,
                'impressions' => 0,
                'cost' => 0,
                'conversions' => 0,
                'ctr' => 0,
                'cpc' => 0,
                'cpa' => 0,
                'roi' => 0,
            ];
        }

        $clicks = $dailyData->sum('clicks');
        $impressions = $dailyData->sum('impressions');
        $cost = $dailyData->sum('cost');
        $conversions = $dailyData->sum('conversions');

        return [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'clicks' => $clicks,
            'impressions' => $impressions,
            'cost' => $cost,
            'conversions' => $conversions,
            'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0,
            'cpc' => $clicks > 0 ? $cost / $clicks : 0,
            'cpa' => $conversions > 0 ? $cost / $conversions : 0,
            'roi' => $this->calculateROI($cost, $conversions, $campaign),
        ];
    }

    /**
     * Расчет ROI для кампании
     */
    private function calculateROI(float $cost, int $conversions, Campaign $campaign): float
    {
        // Здесь должна быть логика расчета ROI на основе данных о доходах
        // Пока используем упрощенный расчет
        $averageOrderValue = $campaign->settings['average_order_value'] ?? 0;
        $revenue = $conversions * $averageOrderValue;
        
        if ($cost == 0) {
            return 0;
        }

        return (($revenue - $cost) / $cost) * 100;
    }

    /**
     * Подготовка данных для MonthlyDirect
     */
    private function prepareMonthlyData(array $campaignsData): array
    {
        $totalClicks = array_sum(array_column($campaignsData, 'clicks'));
        $totalImpressions = array_sum(array_column($campaignsData, 'impressions'));
        $totalCost = array_sum(array_column($campaignsData, 'cost'));
        $totalConversions = array_sum(array_column($campaignsData, 'conversions'));

        return [
            'clicks' => $totalClicks,
            'impressions' => $totalImpressions,
            'cost' => $totalCost,
            'conversions' => $totalConversions,
            'ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
            'cpc' => $totalClicks > 0 ? $totalCost / $totalClicks : 0,
            'cpa' => $totalConversions > 0 ? $totalCost / $totalConversions : 0,
            'roi' => $this->calculateTotalROI($totalCost, $totalConversions),
            'campaigns_count' => count($campaignsData),
            'active_campaigns_count' => count(array_filter($campaignsData, function ($data) {
                return $data['clicks'] > 0;
            })),
            'data' => [
                'campaigns' => $campaignsData,
                'top_campaigns' => $this->getTopCampaigns($campaignsData),
                'performance_metrics' => $this->calculatePerformanceMetrics($campaignsData),
            ],
        ];
    }

    /**
     * Расчет общего ROI
     */
    private function calculateTotalROI(float $totalCost, int $totalConversions): float
    {
        // Упрощенный расчет ROI
        $averageRevenuePerConversion = 1000; // Заглушка - нужно получать из данных
        $totalRevenue = $totalConversions * $averageRevenuePerConversion;
        
        if ($totalCost == 0) {
            return 0;
        }

        return (($totalRevenue - $totalCost) / $totalCost) * 100;
    }

    /**
     * Получить топ кампании по различным метрикам
     */
    private function getTopCampaigns(array $campaignsData): array
    {
        $sortedByCost = $campaignsData;
        usort($sortedByCost, function ($a, $b) {
            return $b['cost'] <=> $a['cost'];
        });

        $sortedByConversions = $campaignsData;
        usort($sortedByConversions, function ($a, $b) {
            return $b['conversions'] <=> $a['conversions'];
        });

        $sortedByROI = array_filter($campaignsData, function ($data) {
            return $data['cost'] > 0;
        });
        usort($sortedByROI, function ($a, $b) {
            return $b['roi'] <=> $a['roi'];
        });

        return [
            'by_cost' => array_slice($sortedByCost, 0, 5),
            'by_conversions' => array_slice($sortedByConversions, 0, 5),
            'by_roi' => array_slice($sortedByROI, 0, 5),
        ];
    }

    /**
     * Расчет метрик производительности
     */
    private function calculatePerformanceMetrics(array $campaignsData): array
    {
        $costs = array_column($campaignsData, 'cost');
        $conversions = array_column($campaignsData, 'conversions');
        $ctrs = array_column($campaignsData, 'ctr');

        return [
            'avg_cost' => count($costs) > 0 ? array_sum($costs) / count($costs) : 0,
            'avg_conversions' => count($conversions) > 0 ? array_sum($conversions) / count($conversions) : 0,
            'avg_ctr' => count($ctrs) > 0 ? array_sum($ctrs) / count($ctrs) : 0,
            'max_cost' => count($costs) > 0 ? max($costs) : 0,
            'min_cost' => count($costs) > 0 ? min($costs) : 0,
        ];
    }

    /**
     * Обновление месячных данных кампаний
     */
    private function updateCampaignsMonthlyData(MonthlyDirect $monthlyDirect, array $campaignsData): void
    {
        foreach ($campaignsData as $campaignData) {
            $campaign = Campaign::find($campaignData['campaign_id']);
            
            if ($campaign) {
                $campaign->monthlyData()->updateOrCreate(
                    [
                        'month' => $this->aggregationPeriod['month_key'],
                    ],
                    [
                        'clicks' => $campaignData['clicks'],
                        'impressions' => $campaignData['impressions'],
                        'cost' => $campaignData['cost'],
                        'conversions' => $campaignData['conversions'],
                        'ctr' => $campaignData['ctr'],
                        'cpc' => $campaignData['cpc'],
                        'cpa' => $campaignData['cpa'],
                        'roi' => $campaignData['roi'],
                    ]
                );
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        \Log::error("AggregateDirectMonthlyJob failed for project {$this->project->id}: " . $exception->getMessage());
    }
}