<?php

namespace App\Jobs\Aggregate;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\Project;
use App\Models\SeoQueriesMonthly;
use App\Models\RawApiResponse;
use Carbon\Carbon;

class AggregateSeoMonthlyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $project;
    public $period;
    public $tries = 3;
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(Project $project, array $period)
    {
        $this->project = $project;
        $this->period = $period;
        $this->onQueue('seo-aggregation');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting SEO monthly aggregation for project {$this->project->id}", [
                'project_id' => $this->project->id,
                'period' => $this->period
            ]);

            $year = $this->period['year'];
            $month = $this->period['month'];
            $startDate = $this->period['start'];
            $endDate = $this->period['end'];

            // Получаем сырые SEO данные за период
            $rawSeoData = $this->getRawSeoData($startDate, $endDate);

            if (empty($rawSeoData)) {
                Log::warning("No raw SEO data found for project {$this->project->id} in period {$year}-{$month}");
                return;
            }

            // Агрегируем данные по запросам
            $aggregatedData = $this->aggregateSeoData($rawSeoData, $year, $month);

            // Сохраняем агрегированные данные
            $this->saveAggregatedData($aggregatedData, $year, $month);

            Log::info("Successfully aggregated SEO data for project {$this->project->id}", [
                'project_id' => $this->project->id,
                'year' => $year,
                'month' => $month,
                'queries_count' => count($aggregatedData)
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to aggregate SEO data for project {$this->project->id}", [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->release(60);
        }
    }

    /**
     * Получить сырые SEO данные за период
     */
    protected function getRawSeoData(Carbon $startDate, Carbon $endDate): array
    {
        return RawApiResponse::where('project_id', $this->project->id)
            ->where('source', 'seo')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('processed_at')
            ->get()
            ->pluck('response_data')
            ->flatMap(function ($responseData) {
                return $responseData['queries'] ?? [];
            })
            ->toArray();
    }

    /**
     * Агрегировать SEO данные по запросам
     */
    protected function aggregateSeoData(array $rawData, int $year, int $month): array
    {
        $aggregated = [];

        foreach ($rawData as $queryData) {
            if (empty($queryData['query']) || !isset($queryData['position'])) {
                continue;
            }

            $query = $queryData['query'];
            $url = $queryData['url'] ?? null;
            $position = (int) $queryData['position'];

            $key = md5($query . $url);

            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'query' => $query,
                    'url' => $url,
                    'positions' => [],
                    'count' => 0
                ];
            }

            $aggregated[$key]['positions'][] = $position;
            $aggregated[$key]['count']++;
        }

        // Вычисляем среднюю позицию для каждого запроса
        $result = [];
        foreach ($aggregated as $data) {
            if (count($data['positions']) > 0) {
                $avgPosition = array_sum($data['positions']) / count($data['positions']);
                
                $result[] = [
                    'query' => $data['query'],
                    'url' => $data['url'],
                    'position' => (int) round($avgPosition),
                    'data_points' => $data['count']
                ];
            }
        }

        // Сортируем по позиции (лучшие позиции первыми)
        usort($result, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $result;
    }

    /**
     * Сохранить агрегированные данные в SeoQueriesMonthly
     */
    protected function saveAggregatedData(array $aggregatedData, int $year, int $month): void
    {
        foreach ($aggregatedData as $data) {
            SeoQueriesMonthly::updateOrCreate(
                [
                    'project_id' => $this->project->id,
                    'year' => $year,
                    'month' => $month,
                    'query' => $data['query'],
                    'url' => $data['url']
                ],
                [
                    'position' => $data['position']
                ]
            );
        }

        // Удаляем старые записи за этот период которых нет в новых данных
        $existingQueries = array_map(function ($data) {
            return md5($data['query'] . $data['url']);
        }, $aggregatedData);

        SeoQueriesMonthly::where('project_id', $this->project->id)
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->each(function ($record) use ($existingQueries) {
                $key = md5($record->query . $record->url);
                if (!in_array($key, $existingQueries)) {
                    $record->delete();
                }
            });
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        Log::error("AggregateSeoMonthlyJob failed for project {$this->project->id}", [
            'project_id' => $this->project->id,
            'period' => $this->period,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'seo',
            'aggregation',
            'project:' . $this->project->id,
            'period:' . $this->period['year'] . '-' . $this->period['month']
        ];
    }
}