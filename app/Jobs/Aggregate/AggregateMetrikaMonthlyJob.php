<?php

namespace App\Jobs\Aggregate;

use App\Models\Project;
use App\Models\MonthlyMetrika;
use App\Models\MonthlyAgeGroup;
use App\Models\Counter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateMetrikaMonthlyJob implements ShouldQueue
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

            // Получаем агрегированные данные по счетчикам проекта
            $countersData = $this->getAggregatedCountersData($startDate, $endDate);
            
            // Создаем или обновляем запись MonthlyMetrika
            $monthlyMetrika = MonthlyMetrika::updateOrCreate(
                [
                    'project_id' => $this->project->id,
                    'month' => $month,
                ],
                $this->prepareMonthlyData($countersData)
            );

            // Сохраняем возрастные группы
            $this->saveAgeGroupsData($monthlyMetrika, $countersData);

            DB::commit();

            \Log::info("Monthly Metrika aggregation completed for project {$this->project->id}, month {$month}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to aggregate Metrika monthly data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Получить агрегированные данные по счетчикам
     */
    private function getAggregatedCountersData($startDate, $endDate): array
    {
        return Counter::where('project_id', $this->project->id)
            ->whereHas('dailyData', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->with(['dailyData' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            }])
            ->with(['ageData' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            }])
            ->get()
            ->map(function ($counter) {
                return $this->aggregateCounterData($counter);
            })
            ->toArray();
    }

    /**
     * Агрегировать данные по одному счетчику
     */
    private function aggregateCounterData(Counter $counter): array
    {
        $dailyData = $counter->dailyData;
        $ageData = $counter->ageData;

        if ($dailyData->isEmpty()) {
            return [
                'counter_id' => $counter->id,
                'visits' => 0,
                'users' => 0,
                'page_views' => 0,
                'bounce_rate' => 0,
                'avg_session_duration' => 0,
                'conversions' => 0,
                'age_groups' => [],
            ];
        }

        $visits = $dailyData->sum('visits');
        $users = $dailyData->sum('users');
        $pageViews = $dailyData->sum('page_views');
        $totalDuration = $dailyData->sum('avg_session_duration') * $dailyData->count();
        $conversions = $dailyData->sum('conversions');

        return [
            'counter_id' => $counter->id,
            'counter_name' => $counter->name,
            'visits' => $visits,
            'users' => $users,
            'page_views' => $pageViews,
            'bounce_rate' => $this->calculateAverageBounceRate($dailyData),
            'avg_session_duration' => $visits > 0 ? $totalDuration / $visits : 0,
            'conversions' => $conversions,
            'conversion_rate' => $visits > 0 ? ($conversions / $visits) * 100 : 0,
            'page_views_per_visit' => $visits > 0 ? $pageViews / $visits : 0,
            'age_groups' => $this->aggregateAgeGroups($ageData),
        ];
    }

    /**
     * Расчет среднего bounce rate
     */
    private function calculateAverageBounceRate($dailyData): float
    {
        $totalVisits = $dailyData->sum('visits');
        $totalBounces = $dailyData->sum('bounces');

        if ($totalVisits == 0) {
            return 0;
        }

        return ($totalBounces / $totalVisits) * 100;
    }

    /**
     * Агрегация возрастных групп
     */
    private function aggregateAgeGroups($ageData): array
    {
        if ($ageData->isEmpty()) {
            return [];
        }

        $ageGroups = [
            '18-24' => 0,
            '25-34' => 0,
            '35-44' => 0,
            '45-54' => 0,
            '55+' => 0,
        ];

        foreach ($ageData as $data) {
            $ageDistribution = $data->age_distribution ?? [];
            foreach ($ageDistribution as $ageGroup => $percentage) {
                if (isset($ageGroups[$ageGroup])) {
                    $ageGroups[$ageGroup] += $percentage;
                }
            }
        }

        // Нормализуем проценты
        $total = array_sum($ageGroups);
        if ($total > 0) {
            foreach ($ageGroups as $ageGroup => $value) {
                $ageGroups[$ageGroup] = ($value / $total) * 100;
            }
        }

        return $ageGroups;
    }

    /**
     * Подготовка данных для MonthlyMetrika
     */
    private function prepareMonthlyData(array $countersData): array
    {
        $totalVisits = array_sum(array_column($countersData, 'visits'));
        $totalUsers = array_sum(array_column($countersData, 'users'));
        $totalPageViews = array_sum(array_column($countersData, 'page_views'));
        $totalConversions = array_sum(array_column($countersData, 'conversions'));

        // Агрегируем возрастные группы из всех счетчиков
        $combinedAgeGroups = $this->combineAgeGroups($countersData);

        return [
            'visits' => $totalVisits,
            'users' => $totalUsers,
            'page_views' => $totalPageViews,
            'bounce_rate' => $this->calculateOverallBounceRate($countersData),
            'avg_session_duration' => $this->calculateOverallAvgDuration($countersData),
            'conversions' => $totalConversions,
            'conversion_rate' => $totalVisits > 0 ? ($totalConversions / $totalVisits) * 100 : 0,
            'page_views_per_visit' => $totalVisits > 0 ? $totalPageViews / $totalVisits : 0,
            'counters_count' => count($countersData),
            'active_counters_count' => count(array_filter($countersData, function ($data) {
                return $data['visits'] > 0;
            })),
            'data' => [
                'counters' => $countersData,
                'top_counters' => $this->getTopCounters($countersData),
                'traffic_metrics' => $this->calculateTrafficMetrics($countersData),
                'age_distribution' => $combinedAgeGroups,
            ],
        ];
    }

    /**
     * Расчет общего bounce rate
     */
    private function calculateOverallBounceRate(array $countersData): float
    {
        $totalVisits = 0;
        $totalBounces = 0;

        foreach ($countersData as $counterData) {
            $totalVisits += $counterData['visits'];
            // Предполагаем, что bounce rate уже в процентах
            $totalBounces += ($counterData['bounce_rate'] / 100) * $counterData['visits'];
        }

        if ($totalVisits == 0) {
            return 0;
        }

        return ($totalBounces / $totalVisits) * 100;
    }

    /**
     * Расчет средней продолжительности сессии
     */
    private function calculateOverallAvgDuration(array $countersData): float
    {
        $totalDuration = 0;
        $totalVisits = 0;

        foreach ($countersData as $counterData) {
            $totalDuration += $counterData['avg_session_duration'] * $counterData['visits'];
            $totalVisits += $counterData['visits'];
        }

        if ($totalVisits == 0) {
            return 0;
        }

        return $totalDuration / $totalVisits;
    }

    /**
     * Комбинирование возрастных групп из всех счетчиков
     */
    private function combineAgeGroups(array $countersData): array
    {
        $combined = [
            '18-24' => 0,
            '25-34' => 0,
            '35-44' => 0,
            '45-54' => 0,
            '55+' => 0,
        ];

        $totalVisits = 0;

        foreach ($countersData as $counterData) {
            $counterVisits = $counterData['visits'];
            $ageGroups = $counterData['age_groups'] ?? [];

            foreach ($ageGroups as $ageGroup => $percentage) {
                if (isset($combined[$ageGroup])) {
                    $combined[$ageGroup] += ($percentage / 100) * $counterVisits;
                }
            }
            $totalVisits += $counterVisits;
        }

        // Нормализуем к процентам
        if ($totalVisits > 0) {
            foreach ($combined as $ageGroup => $value) {
                $combined[$ageGroup] = ($value / $totalVisits) * 100;
            }
        }

        return $combined;
    }

    /**
     * Получить топ счетчики по различным метрикам
     */
    private function getTopCounters(array $countersData): array
    {
        $sortedByVisits = $countersData;
        usort($sortedByVisits, function ($a, $b) {
            return $b['visits'] <=> $a['visits'];
        });

        $sortedByConversions = $countersData;
        usort($sortedByConversions, function ($a, $b) {
            return $b['conversions'] <=> $a['conversions'];
        });

        $sortedByConversionRate = array_filter($countersData, function ($data) {
            return $data['visits'] > 0;
        });
        usort($sortedByConversionRate, function ($a, $b) {
            return $b['conversion_rate'] <=> $a['conversion_rate'];
        });

        return [
            'by_visits' => array_slice($sortedByVisits, 0, 5),
            'by_conversions' => array_slice($sortedByConversions, 0, 5),
            'by_conversion_rate' => array_slice($sortedByConversionRate, 0, 5),
        ];
    }

    /**
     * Расчет метрик трафика
     */
    private function calculateTrafficMetrics(array $countersData): array
    {
        $visits = array_column($countersData, 'visits');
        $conversionRates = array_column($countersData, 'conversion_rate');
        $durations = array_column($countersData, 'avg_session_duration');

        return [
            'avg_visits' => count($visits) > 0 ? array_sum($visits) / count($visits) : 0,
            'avg_conversion_rate' => count($conversionRates) > 0 ? array_sum($conversionRates) / count($conversionRates) : 0,
            'avg_duration' => count($durations) > 0 ? array_sum($durations) / count($durations) : 0,
            'max_visits' => count($visits) > 0 ? max($visits) : 0,
            'min_visits' => count($visits) > 0 ? min($visits) : 0,
        ];
    }

    /**
     * Сохранение данных возрастных групп
     */
    private function saveAgeGroupsData(MonthlyMetrika $monthlyMetrika, array $countersData): void
    {
        $ageDistribution = $this->combineAgeGroups($countersData);

        foreach ($ageDistribution as $ageGroup => $percentage) {
            MonthlyAgeGroup::updateOrCreate(
                [
                    'project_id' => $this->project->id,
                    'month' => $this->aggregationPeriod['month_key'],
                    'age_group' => $ageGroup,
                ],
                [
                    'percentage' => $percentage,
                    'visits' => $this->calculateAgeGroupVisits($countersData, $ageGroup),
                ]
            );
        }
    }

    /**
     * Расчет количества визитов по возрастной группе
     */
    private function calculateAgeGroupVisits(array $countersData, string $ageGroup): int
    {
        $totalVisits = 0;

        foreach ($countersData as $counterData) {
            $counterVisits = $counterData['visits'];
            $ageGroups = $counterData['age_groups'] ?? [];
            $agePercentage = $ageGroups[$ageGroup] ?? 0;
            
            $totalVisits += ($agePercentage / 100) * $counterVisits;
        }

        return (int) round($totalVisits);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        \Log::error("AggregateMetrikaMonthlyJob failed for project {$this->project->id}: " . $exception->getMessage());
    }
}