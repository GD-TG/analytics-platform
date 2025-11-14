<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\MonthlyMetrika;
use App\Models\MonthlyDirect;
use App\Models\MonthlySeo;
use App\Services\Insights\InsightsGenerator;
use App\Helpers\PeriodHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    /**
     * @var InsightsGenerator
     */
    private $insightsGenerator;

    public function __construct(InsightsGenerator $insightsGenerator)
    {
        $this->insightsGenerator = $insightsGenerator;
    }

    /**
     * Получить месячный отчет для проекта
     */
    public function getMonthlyReport(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|string|in:M,M-1,M-2',
            'include_insights' => 'sometimes|boolean',
            'include_comparison' => 'sometimes|boolean',
        ]);

        $period = $request->get('period', 'M');
        $includeInsights = $request->get('include_insights', true);
        $includeComparison = $request->get('include_comparison', true);

        try {
            $periodData = PeriodHelper::getPeriodByKey($period);
            
            // Получаем данные из всех источников
            $metrikaData = $this->getMetrikaData($project, $periodData);
            $directData = $this->getDirectData($project, $periodData);
            $seoData = $this->getSeoData($project, $periodData);
            $ageData = $this->getAgeData($project, $periodData);

            // Формируем основной отчет
            $report = [
                'project' => $project->only(['id', 'name', 'status']),
                'period' => $periodData,
                'metrics' => $this->combineMetrics($metrikaData, $directData, $seoData),
                'sources' => [
                    'metrika' => $metrikaData,
                    'direct' => $directData,
                    'seo' => $seoData,
                ],
                'demographics' => $ageData,
            ];

            // Добавляем сравнение с предыдущим периодом
            if ($includeComparison) {
                $report['comparison'] = $this->getComparisonData($project, $period);
            }

            // Добавляем инсайты
            if ($includeInsights) {
                $report['insights'] = $this->insightsGenerator->generateForProject($project, $periodData);
            }

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить обзор по всем проектам
     */
    public function getOverview(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|string|in:M,M-1,M-2',
        ]);

        $period = $request->get('period', 'M');
        $periodData = PeriodHelper::getPeriodByKey($period);

        $projects = Project::active()->get();
        
        $overview = [
            'period' => $periodData,
            'total_projects' => $projects->count(),
            'summary' => [
                'total_visits' => 0,
                'total_conversions' => 0,
                'total_cost' => 0,
                'total_revenue' => 0,
            ],
            'projects' => [],
        ];

        foreach ($projects as $project) {
            $projectData = $this->getProjectOverviewData($project, $periodData);
            $overview['projects'][] = $projectData;
            
            // Суммируем общие метрики
            $overview['summary']['total_visits'] += $projectData['metrics']['visits'] ?? 0;
            $overview['summary']['total_conversions'] += $projectData['metrics']['conversions'] ?? 0;
            $overview['summary']['total_cost'] += $projectData['metrics']['cost'] ?? 0;
            $overview['summary']['total_revenue'] += $projectData['metrics']['revenue'] ?? 0;
        }

        // Расчет общих KPI
        $overview['summary']['conversion_rate'] = MathHelper::calculateConversionRate(
            $overview['summary']['total_conversions'],
            $overview['summary']['total_visits']
        );
        $overview['summary']['roi'] = MathHelper::calculateROI(
            $overview['summary']['total_revenue'],
            $overview['summary']['total_cost']
        );

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    /**
     * Получить инсайты по всем проектам
     */
    public function getInsights(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|string|in:M,M-1,M-2',
            'type' => 'sometimes|string|in:anomalies,trends,recommendations',
        ]);

        $period = $request->get('period', 'M');
        $type = $request->get('type', 'anomalies');

        $periodData = PeriodHelper::getPeriodByKey($period);
        $projects = Project::active()->get();

        $insights = [
            'period' => $periodData,
            'type' => $type,
            'insights' => [],
        ];

        foreach ($projects as $project) {
            $projectInsights = $this->insightsGenerator->generateForProject($project, $periodData);
            
            if (!empty($projectInsights[$type])) {
                $insights['insights'][] = [
                    'project' => $project->only(['id', 'name']),
                    'data' => $projectInsights[$type],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $insights,
        ]);
    }

    /**
     * Получить доступные периоды для отчетов
     */
    public function getAvailablePeriods(Project $project): JsonResponse
    {
        $periods = PeriodHelper::getReportPeriods();
        $availablePeriods = [];

        foreach ($periods as $periodKey => $periodData) {
            // Проверяем, есть ли данные для этого периода
            $hasData = MonthlyMetrika::where('project_id', $project->id)
                ->where('month', $periodData['start']->format('Y-m'))
                ->exists();

            $availablePeriods[$periodKey] = [
                'period' => $periodData,
                'has_data' => $hasData,
                'is_aggregatable' => PeriodHelper::isAggregatablePeriod($periodData['end']),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $availablePeriods,
        ]);
    }

    /**
     * Перегенерировать отчет для проекта
     */
    public function regenerateReport(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'period' => 'required|string|in:M,M-1,M-2',
        ]);

        $period = $request->get('period');
        $periodData = PeriodHelper::getPeriodByKey($period);

        try {
            // Здесь будет логика перегенерации отчета
            // Пока просто возвращаем успех
            
            return response()->json([
                'success' => true,
                'message' => 'Report regeneration started',
                'data' => [
                    'project_id' => $project->id,
                    'period' => $periodData,
                    'regenerated_at' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить данные Яндекс.Метрики для отчета
     */
    private function getMetrikaData(Project $project, array $periodData): array
    {
        return MonthlyMetrika::where('project_id', $project->id)
            ->where('month', $periodData['start']->format('Y-m'))
            ->first()
            ?->toArray() ?? [];
    }

    /**
     * Получить данные Яндекс.Директа для отчета
     */
    private function getDirectData(Project $project, array $periodData): array
    {
        return MonthlyDirect::where('project_id', $project->id)
            ->where('month', $periodData['start']->format('Y-m'))
            ->first()
            ?->toArray() ?? [];
    }

    /**
     * Получить SEO данные для отчета
     */
    private function getSeoData(Project $project, array $periodData): array
    {
        return MonthlySeo::where('project_id', $project->id)
            ->where('month', $periodData['start']->format('Y-m'))
            ->first()
            ?->toArray() ?? [];
    }

    /**
     * Получить возрастные данные для отчета
     */
    private function getAgeData(Project $project, array $periodData): array
    {
        return $project->monthlyAgeGroups()
            ->where('month', $periodData['start']->format('Y-m'))
            ->get()
            ->toArray();
    }

    /**
     * Комбинирование метрик из разных источников
     */
    private function combineMetrics(array $metrikaData, array $directData, array $seoData): array
    {
        return [
            'visits' => $metrikaData['visits'] ?? 0,
            'users' => $metrikaData['users'] ?? 0,
            'page_views' => $metrikaData['page_views'] ?? 0,
            'bounce_rate' => $metrikaData['bounce_rate'] ?? 0,
            'avg_session_duration' => $metrikaData['avg_session_duration'] ?? 0,
            'conversions' => $metrikaData['conversions'] ?? 0,
            'conversion_rate' => $metrikaData['conversion_rate'] ?? 0,
            'cost' => $directData['cost'] ?? 0,
            'clicks' => $directData['clicks'] ?? 0,
            'impressions' => $directData['impressions'] ?? 0,
            'ctr' => $directData['ctr'] ?? 0,
            'cpc' => $directData['cpc'] ?? 0,
            'cpa' => $directData['cpa'] ?? 0,
            'roi' => $directData['roi'] ?? 0,
            'organic_traffic' => $seoData['organic_traffic'] ?? 0,
            'keywords' => $seoData['keywords'] ?? 0,
            'avg_position' => $seoData['avg_position'] ?? 0,
        ];
    }

    /**
     * Получить данные для сравнения с предыдущим периодом
     */
    private function getComparisonData(Project $project, string $currentPeriod): array
    {
        $comparisonPeriods = PeriodHelper::getComparisonPeriods($currentPeriod);
        
        $currentData = $this->getCombinedProjectData($project, $comparisonPeriods['current']);
        $previousData = $this->getCombinedProjectData($project, $comparisonPeriods['previous']);

        $comparison = [];
        $metrics = ['visits', 'conversions', 'cost', 'revenue', 'conversion_rate', 'roi'];

        foreach ($metrics as $metric) {
            if (isset($currentData[$metric]) && isset($previousData[$metric])) {
                $comparison[$metric] = [
                    'current' => $currentData[$metric],
                    'previous' => $previousData[$metric],
                    'absolute_change' => $currentData[$metric] - $previousData[$metric],
                    'percentage_change' => MathHelper::calculateGrowthRate(
                        $currentData[$metric],
                        $previousData[$metric]
                    ),
                ];
            }
        }

        return $comparison;
    }

    /**
     * Получить комбинированные данные проекта для периода
     */
    private function getCombinedProjectData(Project $project, array $periodData): array
    {
        $metrikaData = $this->getMetrikaData($project, $periodData);
        $directData = $this->getDirectData($project, $periodData);
        
        return $this->combineMetrics($metrikaData, $directData, []);
    }

    /**
     * Получить обзорные данные проекта
     */
    private function getProjectOverviewData(Project $project, array $periodData): array
    {
        $metrics = $this->getCombinedProjectData($project, $periodData);

        return [
            'project' => $project->only(['id', 'name', 'status']),
            'metrics' => $metrics,
            'kpi' => [
                'conversion_rate' => $metrics['conversion_rate'] ?? 0,
                'roi' => $metrics['roi'] ?? 0,
                'cpa' => $metrics['cpa'] ?? 0,
            ],
        ];
    }
}