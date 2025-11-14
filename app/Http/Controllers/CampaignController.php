<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Project;
use App\Services\Direct\DirectClient;
use App\Services\Direct\DirectFetcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    /**
     * @var DirectClient
     */
    private $directClient;

    /**
     * @var DirectFetcher
     */
    private $directFetcher;

    public function __construct(DirectClient $directClient, DirectFetcher $directFetcher)
    {
        $this->directClient = $directClient;
        $this->directFetcher = $directFetcher;
    }

    /**
     * Получить список кампаний для проекта
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:100',
            'status' => 'sometimes|string|in:active,paused,archived',
            'sort_by' => 'sometimes|string|in:name,status,created_at',
            'sort_dir' => 'sometimes|string|in:asc,desc',
        ]);

        $perPage = $request->get('per_page', 20);
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');

        $query = $project->campaigns()->with(['monthlyData']);

        // Поиск по названию
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Фильтр по статусу
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Сортировка
        $query->orderBy($sortBy, $sortDir);

        $campaigns = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $campaigns->items(),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
                'last_page' => $campaigns->lastPage(),
            ]
        ]);
    }

    /**
     * Получить детальную информацию о кампании
     */
    public function show(Project $project, Campaign $campaign): JsonResponse
    {
        $campaign->load(['monthlyData', 'project']);

        return response()->json([
            'success' => true,
            'data' => $campaign,
        ]);
    }

    /**
     * Создать новую кампанию (ручное добавление)
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'direct_campaign_id' => 'required|integer|unique:campaigns,direct_campaign_id',
            'name' => 'required|string|max:255',
            'status' => 'required|string|in:active,paused,archived',
            'budget' => 'sometimes|numeric|min:0',
            'daily_budget' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
        ]);

        try {
            DB::beginTransaction();

            $campaign = $project->campaigns()->create([
                'direct_campaign_id' => $request->direct_campaign_id,
                'name' => $request->name,
                'status' => $request->status,
                'budget' => $request->budget,
                'daily_budget' => $request->daily_budget,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'settings' => $request->get('settings', []),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => $campaign->load('project'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить кампанию
     */
    public function update(Request $request, Project $project, Campaign $campaign): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|in:active,paused,archived',
            'budget' => 'sometimes|numeric|min:0',
            'daily_budget' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'settings' => 'sometimes|array',
        ]);

        try {
            DB::beginTransaction();

            $campaign->update($request->only([
                'name', 'status', 'budget', 'daily_budget', 
                'start_date', 'end_date', 'settings'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data' => $campaign->fresh(['monthlyData', 'project']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить кампанию
     */
    public function destroy(Project $project, Campaign $campaign): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Удаляем связанные данные
            $campaign->monthlyData()->delete();
            $campaign->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Синхронизировать кампании из Яндекс.Директа
     */
    public function syncFromDirect(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'force' => 'sometimes|boolean',
        ]);

        try {
            $force = $request->get('force', false);
            
            $this->directFetcher->syncCampaigns($project, $force);

            return response()->json([
                'success' => true,
                'message' => 'Campaigns synchronization started',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync campaigns',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить статистику кампании по периодам
     */
    public function getStats(Project $project, Campaign $campaign, string $period): JsonResponse
    {
        $validPeriods = ['M', 'M-1', 'M-2', 'custom'];
        
        if (!in_array($period, $validPeriods)) {
            throw ValidationException::withMessages([
                'period' => 'Invalid period. Available: M, M-1, M-2, custom',
            ]);
        }

        try {
            $stats = $campaign->getStatsForPeriod($period);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'meta' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'period' => $period,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get campaign stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить детальную статистику кампании с сравнением
     */
    public function getDetailedStats(Request $request, Project $project, Campaign $campaign): JsonResponse
    {
        $request->validate([
            'period' => 'required|string|in:M,M-1,M-2',
            'compare_with' => 'sometimes|string|in:M-1,M-2,previous_year',
        ]);

        try {
            $period = $request->get('period', 'M');
            $compareWith = $request->get('compare_with', 'M-1');

            $currentStats = $campaign->getStatsForPeriod($period);
            $previousStats = $campaign->getStatsForPeriod($compareWith);

            $comparison = $this->calculateComparison($currentStats, $previousStats);

            return response()->json([
                'success' => true,
                'data' => [
                    'current' => $currentStats,
                    'previous' => $previousStats,
                    'comparison' => $comparison,
                ],
                'meta' => [
                    'period' => $period,
                    'compare_with' => $compareWith,
                    'campaign' => $campaign->only(['id', 'name', 'status']),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get detailed stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить список кампаний для селекта (упрощенный)
     */
    public function getCampaignsForSelect(Project $project): JsonResponse
    {
        $campaigns = $project->campaigns()
            ->active()
            ->select(['id', 'direct_campaign_id', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    /**
     * Массовое обновление статусов кампаний
     */
    public function bulkUpdate(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'campaigns' => 'required|array',
            'campaigns.*.id' => 'required|exists:campaigns,id',
            'campaigns.*.status' => 'required|string|in:active,paused,archived',
        ]);

        try {
            DB::beginTransaction();

            $updated = 0;
            foreach ($request->campaigns as $campaignData) {
                $campaign = Campaign::where('id', $campaignData['id'])
                    ->where('project_id', $project->id)
                    ->first();

                if ($campaign) {
                    $campaign->update(['status' => $campaignData['status']]);
                    $updated++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Updated {$updated} campaigns",
                'data' => [
                    'updated_count' => $updated,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaigns',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Расчет сравнения между двумя периодами
     */
    private function calculateComparison(array $current, array $previous): array
    {
        $comparison = [];

        $metrics = [
            'clicks', 'impressions', 'cost', 'conversions', 
            'ctr', 'cpc', 'cpa', 'roi'
        ];

        foreach ($metrics as $metric) {
            if (isset($current[$metric]) && isset($previous[$metric])) {
                $currentValue = $current[$metric];
                $previousValue = $previous[$metric];

                $comparison[$metric] = [
                    'current' => $currentValue,
                    'previous' => $previousValue,
                    'absolute_change' => $currentValue - $previousValue,
                    'percentage_change' => $previousValue != 0 
                        ? (($currentValue - $previousValue) / abs($previousValue)) * 100 
                        : null,
                ];
            }
        }

        return $comparison;
    }
}