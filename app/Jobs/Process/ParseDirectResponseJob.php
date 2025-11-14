<?php

namespace App\Jobs\Process;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\RawApiResponse;
use App\Models\DirectCampaign;
use App\Models\DirectCampaignMonthly;
use App\Models\DirectTotalsMonthly;
use App\Models\DirectAccount;
use Carbon\Carbon;

class ParseDirectResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $rawResponse;
    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(RawApiResponse $rawResponse)
    {
        $this->rawResponse = $rawResponse;
        $this->onQueue('direct-process');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Processing Direct response #{$this->rawResponse->id}", [
                'project_id' => $this->rawResponse->project_id,
                'source' => $this->rawResponse->source
            ]);

            $responseData = $this->rawResponse->response_data;
            $requestParams = $this->rawResponse->request_params;

            // Обрабатываем данные по кампаниям
            if (isset($responseData['campaigns'])) {
                $this->processCampaignsData($responseData['campaigns'], $requestParams);
            }

            // Обрабатываем итоговые данные
            if (isset($responseData['totals'])) {
                $this->processTotalsData($responseData['totals'], $requestParams);
            }

            // Помечаем ответ как обработанный
            $this->rawResponse->update([
                'processed_at' => now(),
                'response_code' => 200
            ]);

            Log::info("Successfully processed Direct response #{$this->rawResponse->id}");

        } catch (\Exception $e) {
            Log::error("Failed to process Direct response #{$this->rawResponse->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->rawResponse->update([
                'response_code' => 500
            ]);

            $this->release(60);
        }
    }

    /**
     * Обработка данных по кампаниям
     */
    protected function processCampaignsData(array $campaignsData, array $requestParams): void
    {
        $directAccountId = $requestParams['direct_account_id'];
        $startDate = Carbon::parse($requestParams['start_date']);
        $endDate = Carbon::parse($requestParams['end_date']);

        $directAccount = DirectAccount::find($directAccountId);
        if (!$directAccount) {
            Log::error("DirectAccount not found: {$directAccountId}");
            return;
        }

        // Синхронизируем список кампаний
        if (isset($campaignsData['campaigns'])) {
            foreach ($campaignsData['campaigns'] as $campaignData) {
                DirectCampaign::updateOrCreate(
                    [
                        'direct_account_id' => $directAccountId,
                        'campaign_id' => $campaignData['Id']
                    ],
                    [
                        'name' => $campaignData['Name'] ?? '',
                        'status' => $campaignData['Status'] ?? 'UNKNOWN'
                    ]
                );
            }
        }

        // Обрабатываем статистику по кампаниям
        if (isset($campaignsData['stats']) && !empty($campaignsData['stats'])) {
            $monthlyData = [];

            // Агрегируем данные по месяцам
            foreach ($campaignsData['stats'] as $stat) {
                $campaignId = $stat['campaign_id'] ?? 0;
                if (!$campaignId) {
                    continue;
                }

                $directCampaign = DirectCampaign::where('direct_account_id', $directAccountId)
                    ->where('campaign_id', $campaignId)
                    ->first();

                if (!$directCampaign) {
                    continue;
                }

                // Определяем месяц из периода запроса
                // Для упрощения используем месяц из startDate
                $year = $startDate->year;
                $month = $startDate->month;

                $key = "{$directCampaign->id}-{$year}-{$month}";

                if (!isset($monthlyData[$key])) {
                    $monthlyData[$key] = [
                        'direct_campaign_id' => $directCampaign->id,
                        'year' => $year,
                        'month' => $month,
                        'impressions' => 0,
                        'clicks' => 0,
                        'cost' => 0,
                        'conversions' => 0,
                    ];
                }

                $monthlyData[$key]['impressions'] += $stat['impressions'] ?? 0;
                $monthlyData[$key]['clicks'] += $stat['clicks'] ?? 0;
                $monthlyData[$key]['cost'] += $stat['cost'] ?? 0;
                $monthlyData[$key]['conversions'] += $stat['conversions'] ?? 0;
            }

            // Сохраняем агрегированные данные
            foreach ($monthlyData as $data) {
                $ctr = $data['impressions'] > 0 
                    ? ($data['clicks'] / $data['impressions']) * 100 
                    : 0;
                $cpc = $data['clicks'] > 0 
                    ? $data['cost'] / $data['clicks'] 
                    : 0;
                $cpa = $data['conversions'] > 0 
                    ? $data['cost'] / $data['conversions'] 
                    : null;

                DirectCampaignMonthly::updateOrCreate(
                    [
                        'project_id' => $this->rawResponse->project_id,
                        'direct_campaign_id' => $data['direct_campaign_id'],
                        'year' => $data['year'],
                        'month' => $data['month']
                    ],
                    [
                        'impressions' => $data['impressions'],
                        'clicks' => $data['clicks'],
                        'ctr_pct' => round($ctr, 2),
                        'cpc' => round($cpc, 2),
                        'conversions' => $data['conversions'] > 0 ? $data['conversions'] : null,
                        'cpa' => $cpa ? round($cpa, 2) : null,
                        'cost' => round($data['cost'], 2)
                    ]
                );
            }
        }
    }

    /**
     * Обработка итоговых данных
     */
    protected function processTotalsData(array $totalsData, array $requestParams): void
    {
        $startDate = Carbon::parse($requestParams['start_date']);
        $year = $startDate->year;
        $month = $startDate->month;

        DirectTotalsMonthly::updateOrCreate(
            [
                'project_id' => $this->rawResponse->project_id,
                'year' => $year,
                'month' => $month
            ],
            [
                'impressions' => $totalsData['impressions'] ?? 0,
                'clicks' => $totalsData['clicks'] ?? 0,
                'ctr_pct' => round($totalsData['ctr'] ?? 0, 2),
                'cpc' => round($totalsData['avg_cpc'] ?? 0, 2),
                'conversions' => $totalsData['conversions'] ?? null,
                'cpa' => $totalsData['cpa'] ? round($totalsData['cpa'], 2) : null,
                'cost' => round($totalsData['cost'] ?? 0, 2)
            ]
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        Log::error("ParseDirectResponseJob failed for response #{$this->rawResponse->id}", [
            'raw_response_id' => $this->rawResponse->id,
            'project_id' => $this->rawResponse->project_id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->rawResponse->update([
            'response_code' => 500
        ]);
    }
}

