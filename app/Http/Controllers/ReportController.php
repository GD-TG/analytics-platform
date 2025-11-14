<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\MetricsMonthly;
use App\Models\MetricsAgeMonthly;
use App\Models\DirectTotalsMonthly;
use App\Models\DirectCampaignMonthly;
use App\Models\SeoQueriesMonthly;
use App\Helpers\PeriodHelper;
use App\Helpers\MathHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController
{
    /**
     * Получить отчет для проекта (формат как в ТЗ)
     */
    public function getReport(Request $request, $id): JsonResponse
    {
        try {
            $project = Project::findOrFail($id);
            
            // Получаем периоды M, M-1, M-2
            $periods = PeriodHelper::getReportPeriods();
            $periodKeys = ['M', 'M-1', 'M-2'];
            
            $report = [
                'projectid' => $project->id,
                'periods' => array_map(function($key) use ($periods) {
                    return $periods[$key]['start']->format('Y-m');
                }, $periodKeys),
                'metrika' => [
                    'summary' => [],
                    'age' => [],
                ],
                'direct' => [
                    'totals' => [],
                    'campaigns' => [],
                ],
                'seo' => [
                    'summary' => [],
                    'queries' => [],
                ],
            ];

            // Собираем данные по Метрике
            foreach ($periodKeys as $key) {
                $period = $periods[$key];
                $year = $period['start']->year;
                $month = $period['start']->month;
                
                $metrics = MetricsMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();
                
                if ($metrics) {
                    $report['metrika']['summary'][] = [
                        'month' => $period['start']->format('Y-m'),
                        'visits' => $metrics->visits ?? 0,
                        'users' => $metrics->users ?? 0,
                        'bounce' => (float)($metrics->bounce_rate ?? 0),
                        'avgSec' => $metrics->avg_session_duration_sec ?? 0,
                        'conv' => $metrics->conversions ?? 0,
                    ];
                }

                // Возрастные данные
                $ageData = MetricsAgeMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->get();
                
                foreach ($ageData as $age) {
                    $report['metrika']['age'][] = [
                        'month' => $period['start']->format('Y-m'),
                        'age' => $age->age_group,
                        'visits' => $age->visits ?? 0,
                        'users' => $age->users ?? 0,
                        'bounce' => (float)($age->bounce_rate ?? 0),
                        'avgSec' => $age->avg_session_duration_sec ?? 0,
                    ];
                }
            }

            // Собираем данные по Директу
            foreach ($periodKeys as $key) {
                $period = $periods[$key];
                $year = $period['start']->year;
                $month = $period['start']->month;
                
                $totals = DirectTotalsMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();
                
                if ($totals) {
                    $report['direct']['totals'][] = [
                        'month' => $period['start']->format('Y-m'),
                        'impressions' => $totals->impressions ?? 0,
                        'clicks' => $totals->clicks ?? 0,
                        'ctr' => (float)($totals->ctr_pct ?? 0),
                        'cpc' => (float)($totals->cpc ?? 0),
                        'conv' => $totals->conversions ?? 0,
                        'cpa' => (float)($totals->cpa ?? 0),
                        'cost' => (float)($totals->cost ?? 0),
                    ];
                }

                // Данные по кампаниям
                $campaigns = DirectCampaignMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->get();
                
                foreach ($campaigns as $campaign) {
                    $directCampaign = \App\Models\DirectCampaign::find($campaign->direct_campaign_id);
                    $report['direct']['campaigns'][] = [
                        'campaignId' => $directCampaign->campaign_id ?? 0,
                        'rows' => [[
                            'month' => $period['start']->format('Y-m'),
                            'impressions' => $campaign->impressions ?? 0,
                            'clicks' => $campaign->clicks ?? 0,
                            'ctr' => (float)($campaign->ctr_pct ?? 0),
                            'cpc' => (float)($campaign->cpc ?? 0),
                            'conv' => $campaign->conversions ?? 0,
                            'cpa' => (float)($campaign->cpa ?? 0),
                            'cost' => (float)($campaign->cost ?? 0),
                        ]],
                    ];
                }
            }

            // SEO данные
            foreach ($periodKeys as $key) {
                $period = $periods[$key];
                $year = $period['start']->year;
                $month = $period['start']->month;
                
                $seoQueries = SeoQueriesMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->get();
                
                if ($seoQueries->count() > 0) {
                    $report['seo']['summary'][] = [
                        'month' => $period['start']->format('Y-m'),
                        'visitors' => $seoQueries->sum('visitors') ?? 0,
                        'conv' => $seoQueries->sum('conversions') ?? 0,
                    ];
                    
                    foreach ($seoQueries as $query) {
                        $report['seo']['queries'][] = [
                            'month' => $period['start']->format('Y-m'),
                            'query' => $query->query ?? '',
                            'position' => $query->position ?? 0,
                            'url' => $query->url ?? '',
                        ];
                    }
                }
            }

            return response()->json($report);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить список проектов с термометром
     */
    public function getProjectsWithThermometer(Request $request): JsonResponse
    {
        try {
            $projects = Project::active()->get();
            $periods = PeriodHelper::getReportPeriods();
            
            $result = [];
            
            foreach ($projects as $project) {
                // Получаем данные за текущий и предыдущий месяц
                $currentPeriod = $periods['M'];
                $previousPeriod = $periods['M-1'];
                
                $currentMetrics = MetricsMonthly::where('project_id', $project->id)
                    ->where('year', $currentPeriod['start']->year)
                    ->where('month', $currentPeriod['start']->month)
                    ->first();
                
                $previousMetrics = MetricsMonthly::where('project_id', $project->id)
                    ->where('year', $previousPeriod['start']->year)
                    ->where('month', $previousPeriod['start']->month)
                    ->first();
                
                // Рассчитываем статус термометра
                $thermometer = $this->calculateThermometer($currentMetrics, $previousMetrics);
                
                $result[] = [
                    'id' => $project->id,
                    'name' => $project->name,
                    'thermometer' => $thermometer,
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Рассчитать статус термометра для проекта
     * 🔥 Проект растёт
     * 🌤 Стабильно
     * ❄ Есть падения
     */
    private function calculateThermometer($current, $previous): string
    {
        if (!$current || !$previous) {
            return '🌤'; // Стабильно, если нет данных
        }
        
        $keyMetrics = [
            'visits' => $current->visits ?? 0,
            'users' => $current->users ?? 0,
            'conversions' => $current->conversions ?? 0,
        ];
        
        $previousMetrics = [
            'visits' => $previous->visits ?? 0,
            'users' => $previous->users ?? 0,
            'conversions' => $previous->conversions ?? 0,
        ];
        
        $growthCount = 0;
        $declineCount = 0;
        $stableCount = 0;
        
        foreach ($keyMetrics as $key => $value) {
            $prevValue = $previousMetrics[$key] ?? 0;
            
            if ($prevValue == 0) {
                if ($value > 0) {
                    $growthCount++;
                } else {
                    $stableCount++;
                }
                continue;
            }
            
            $change = (($value - $prevValue) / $prevValue) * 100;
            
            if ($change > 5) {
                $growthCount++;
            } elseif ($change < -5) {
                $declineCount++;
            } else {
                $stableCount++;
            }
        }
        
        // 🔥 Проект растёт - если большинство метрик растут
        if ($growthCount > $declineCount && $growthCount > $stableCount) {
            return '🔥';
        }
        
        // ❄ Есть падения - если большинство метрик падают
        if ($declineCount > $growthCount && $declineCount > $stableCount) {
            return '❄';
        }
        
        // 🌤 Стабильно - во всех остальных случаях
        return '🌤';
    }

    /**
     * Получить статистику для страницы Statistics
     */
    public function getStatistics(Request $request, $id = null): JsonResponse
    {
        try {
            $projectId = $id ?? $request->get('project_id', 1);
            $project = Project::findOrFail($projectId);
            
            $periods = PeriodHelper::getReportPeriods();
            $metrics = [];
            
            // Получаем данные за 3 месяца
            foreach (['M', 'M-1', 'M-2'] as $key) {
                $period = $periods[$key];
                $year = $period['start']->year;
                $month = $period['start']->month;
                
                $data = MetricsMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();
                
                if ($data) {
                    $metrics[] = [
                        'month' => $period['start']->format('Y-m'),
                        'month_label' => $period['start']->translatedFormat('F Y'),
                        'visits' => $data->visits ?? 0,
                        'users' => $data->users ?? 0,
                        'bounce_rate' => (float)($data->bounce_rate ?? 0),
                        'avg_duration' => $data->avg_session_duration_sec ?? 0,
                        'conversions' => $data->conversions ?? 0,
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить данные визитов для графика
     */
    public function getVisits(Request $request, $id = null): JsonResponse
    {
        try {
            $projectId = $id ?? $request->get('project_id', 1);
            $project = Project::findOrFail($projectId);
            
            // Получаем данные за последние 31 день
            $startDate = Carbon::now()->subDays(31);
            $endDate = Carbon::now();
            
            // Здесь нужно будет получать дневные данные, пока возвращаем месячные
            $data = [];
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить источники трафика
     */
    public function getSources(Request $request, $id = null): JsonResponse
    {
        try {
            $projectId = $id ?? $request->get('project_id', 1);
            $project = Project::findOrFail($projectId);
            
            $periods = PeriodHelper::getReportPeriods();
            $sources = [];
            
            foreach (['M', 'M-1'] as $key) {
                $period = $periods[$key];
                $year = $period['start']->year;
                $month = $period['start']->month;
                
                // Здесь нужно получать данные по источникам из Метрики
                // Пока возвращаем структуру
                $sources[] = [
                    'month' => $period['start']->format('Y-m'),
                    'month_label' => $period['start']->translatedFormat('F Y'),
                    'sources' => [],
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $sources,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить возрастные данные
     */
    public function getAgeData(Request $request, $id = null): JsonResponse
    {
        try {
            $projectId = $id ?? $request->get('project_id', 1);
            $project = Project::findOrFail($projectId);
            
            $periods = PeriodHelper::getReportPeriods();
            $ageData = [];
            
            foreach (['M', 'M-1'] as $key) {
                $period = $periods[$key];
                $year = $period['start']->year;
                $month = $period['start']->month;
                
                $data = MetricsAgeMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->get();
                
                $ageData[] = [
                    'month' => $period['start']->format('Y-m'),
                    'month_label' => $period['start']->translatedFormat('F Y'),
                    'data' => $data->map(function($item) {
                        return [
                            'age_group' => $item->age_group,
                            'visits' => $item->visits ?? 0,
                            'users' => $item->users ?? 0,
                            'bounce_rate' => (float)($item->bounce_rate ?? 0),
                            'avg_duration' => $item->avg_session_duration_sec ?? 0,
                            'views' => 0, // Нужно добавить в модель
                        ];
                    })->toArray(),
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $ageData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
