<?php

namespace App\Jobs\Process;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\RawApiResponse;
use App\Models\MetricsMonthly; // Изменено с MonthlyMetrika
use App\Models\MetricsAgeMonthly; // Изменено с MonthlyAgeGroup
use App\Models\Goal;
use Carbon\Carbon;

class ParseMetrikaResponseJob implements ShouldQueue
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
        $this->onQueue('metrika-process');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Processing Metrika response #{$this->rawResponse->id}", [
                'project_id' => $this->rawResponse->project_id,
                'source' => $this->rawResponse->source
            ]);

            $responseData = $this->rawResponse->response_data;
            $requestParams = $this->rawResponse->request_params;

            // Обрабатываем данные визитов
            if (isset($responseData['visits'])) {
                $this->processVisitsData($responseData['visits'], $requestParams);
            }

            // Обрабатываем возрастные данные
            if (isset($responseData['age'])) {
                $this->processAgeData($responseData['age'], $requestParams);
            }

            // Обрабатываем данные по целям
            if (isset($responseData['goals'])) {
                $this->processGoalsData($responseData['goals'], $requestParams);
            }

            // Помечаем ответ как обработанный
            $this->rawResponse->update([
                'processed_at' => now(),
                'response_code' => 200
            ]);

            Log::info("Successfully processed Metrika response #{$this->rawResponse->id}");

        } catch (\Exception $e) {
            Log::error("Failed to process Metrika response #{$this->rawResponse->id}", [
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
     * Обработка данных визитов и сессий
     */
    protected function processVisitsData(array $visitsData, array $requestParams): void
    {
        if (!isset($visitsData['data']) || empty($visitsData['data'])) {
            return;
        }

        $counterId = $requestParams['counter_id'];
        $startDate = Carbon::parse($requestParams['start_date']);
        $endDate = Carbon::parse($requestParams['end_date']);

        // Агрегируем данные по месяцам
        $monthlyData = [];

        foreach ($visitsData['data'] as $dataPoint) {
            if (!isset($dataPoint['dimensions']) || !isset($dataPoint['metrics'])) {
                continue;
            }

            $date = $dataPoint['dimensions'][0]['name'] ?? null;
            if (!$date) {
                continue;
            }

            $dateObj = Carbon::parse($date);
            $year = $dateObj->year;
            $month = $dateObj->month;

            $key = "{$year}-{$month}";

            if (!isset($monthlyData[$key])) {
                $monthlyData[$key] = [
                    'year' => $year,
                    'month' => $month,
                    'visits' => 0,
                    'users' => 0,
                    'pageviews' => 0,
                    'bounce_rate_sum' => 0,
                    'avg_duration_sum' => 0,
                    'total_visits' => 0
                ];
            }

            $metrics = $dataPoint['metrics'][0] ?? [];
            if (count($metrics) >= 5) {
                $monthlyData[$key]['visits'] += $metrics[0] ?? 0; // visits
                $monthlyData[$key]['users'] += $metrics[1] ?? 0; // users
                $monthlyData[$key]['pageviews'] += $metrics[2] ?? 0; // pageviews
                
                // Взвешенное среднее для bounce rate
                $monthlyData[$key]['bounce_rate_sum'] += ($metrics[3] ?? 0) * $metrics[0];
                // Взвешенное среднее для длительности
                $monthlyData[$key]['avg_duration_sum'] += ($metrics[4] ?? 0) * $metrics[0];
                
                $monthlyData[$key]['total_visits'] += $metrics[0] ?? 0;
            }
        }

        // Сохраняем агрегированные данные
        foreach ($monthlyData as $data) {
            if ($data['total_visits'] > 0) {
                $bounceRate = $data['bounce_rate_sum'] / $data['total_visits'];
                $avgDuration = $data['avg_duration_sum'] / $data['total_visits'];
            } else {
                $bounceRate = 0;
                $avgDuration = 0;
            }

            MetricsMonthly::updateOrCreate( // Изменено с MonthlyMetrika
                [
                    'project_id' => $this->rawResponse->project_id,
                    'year' => $data['year'],
                    'month' => $data['month']
                ],
                [
                    'visits' => $data['visits'],
                    'users' => $data['users'],
                    'pageviews' => $data['pageviews'],
                    'bounce_rate' => round($bounceRate, 2),
                    'avg_session_duration_sec' => (int) round($avgDuration),
                    'conversions' => 0 // Будет заполнено из целей
                ]
            );
        }
    }

    /**
     * Обработка возрастных данных
     */
    protected function processAgeData(array $ageData, array $requestParams): void
    {
        if (!isset($ageData['data']) || empty($ageData['data'])) {
            return;
        }

        $monthlyAgeData = [];

        foreach ($ageData['data'] as $dataPoint) {
            if (!isset($dataPoint['dimensions']) || count($dataPoint['dimensions']) < 2) {
                continue;
            }

            $ageGroup = $dataPoint['dimensions'][0]['name'] ?? 'unknown';
            $date = $dataPoint['dimensions'][1]['name'] ?? null;
            
            if (!$date) {
                continue;
            }

            $dateObj = Carbon::parse($date);
            $year = $dateObj->year;
            $month = $dateObj->month;

            $key = "{$year}-{$month}-{$ageGroup}";

            if (!isset($monthlyAgeData[$key])) {
                $monthlyAgeData[$key] = [
                    'year' => $year,
                    'month' => $month,
                    'age_group' => $ageGroup,
                    'visits' => 0,
                    'users' => 0,
                    'bounce_rate_sum' => 0,
                    'avg_duration_sum' => 0,
                    'total_visits' => 0
                ];
            }

            $metrics = $dataPoint['metrics'][0] ?? [];
            if (count($metrics) >= 4) {
                $monthlyAgeData[$key]['visits'] += $metrics[0] ?? 0;
                $monthlyAgeData[$key]['users'] += $metrics[1] ?? 0;
                $monthlyAgeData[$key]['bounce_rate_sum'] += ($metrics[2] ?? 0) * $metrics[0];
                $monthlyAgeData[$key]['avg_duration_sum'] += ($metrics[3] ?? 0) * $metrics[0];
                $monthlyAgeData[$key]['total_visits'] += $metrics[0] ?? 0;
            }
        }

        // Сохраняем возрастные данные
        foreach ($monthlyAgeData as $data) {
            if ($data['total_visits'] > 0) {
                $bounceRate = $data['bounce_rate_sum'] / $data['total_visits'];
                $avgDuration = $data['avg_duration_sum'] / $data['total_visits'];
            } else {
                $bounceRate = 0;
                $avgDuration = 0;
            }

            MetricsAgeMonthly::updateOrCreate( // Изменено с MonthlyAgeGroup
                [
                    'project_id' => $this->rawResponse->project_id,
                    'year' => $data['year'],
                    'month' => $data['month'],
                    'age_group' => $data['age_group']
                ],
                [
                    'visits' => $data['visits'],
                    'users' => $data['users'],
                    'bounce_rate' => round($bounceRate, 2),
                    'avg_session_duration_sec' => (int) round($avgDuration)
                ]
            );
        }
    }

    /**
     * Обработка данных по целям
     */
    protected function processGoalsData(array $goalsData, array $requestParams): void
    {
        if (!isset($goalsData['data']) || empty($goalsData['data'])) {
            return;
        }

        $counterId = $requestParams['counter_id'];
        $monthlyConversions = [];

        // Получаем конверсионные цели для этого счетчика
        $conversionGoals = Goal::where('counter_id', $counterId)
            ->where('is_conversion', true)
            ->pluck('goal_id')
            ->toArray();

        foreach ($goalsData['data'] as $dataPoint) {
            if (!isset($dataPoint['dimensions']) || count($dataPoint['dimensions']) < 2) {
                continue;
            }

            $goalId = (int) $dataPoint['dimensions'][0]['name'];
            $date = $dataPoint['dimensions'][1]['name'] ?? null;
            
            if (!$date || !in_array($goalId, $conversionGoals)) {
                continue;
            }

            $dateObj = Carbon::parse($date);
            $year = $dateObj->year;
            $month = $dateObj->month;

            $key = "{$year}-{$month}";

            if (!isset($monthlyConversions[$key])) {
                $monthlyConversions[$key] = 0;
            }

            $metrics = $dataPoint['metrics'][0] ?? [];
            if (!empty($metrics)) {
                $monthlyConversions[$key] += $metrics[0] ?? 0; // goalReaches
            }
        }

        // Обновляем конверсии в основных метриках
        foreach ($monthlyConversions as $period => $conversions) {
            list($year, $month) = explode('-', $period);
            
            MetricsMonthly::where('project_id', $this->rawResponse->project_id) // Изменено с MonthlyMetrika
                ->where('year', $year)
                ->where('month', $month)
                ->update(['conversions' => $conversions]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        Log::error("ParseMetrikaResponseJob failed for response #{$this->rawResponse->id}", [
            'raw_response_id' => $this->rawResponse->id,
            'project_id' => $this->rawResponse->project_id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->rawResponse->update([
            'response_code' => 500
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'metrika',
            'parse',
            'response:' . $this->rawResponse->id,
            'project:' . $this->rawResponse->project_id
        ];
    }
}