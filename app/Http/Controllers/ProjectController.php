<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    /**
     * Получить список проектов
     */
    public function index(Request $request): JsonResponse
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

        $query = Project::with(['counters', 'campaigns']);

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

        $projects = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $projects->items(),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'last_page' => $projects->lastPage(),
            ]
        ]);
    }

    /**
     * Получить детальную информацию о проекте
     */
    public function show(Project $project): JsonResponse
    {
        $project->load(['counters', 'campaigns', 'monthlyMetrika', 'monthlyDirect', 'monthlySeo']);

        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    /**
     * Создать новый проект
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'status' => 'required|string|in:active,paused,archived',
            'timezone' => 'sometimes|string|timezone',
            'currency' => 'sometimes|string|in:RUB,USD,EUR',
            'metrika_counters' => 'sometimes|array',
            'metrika_counters.*' => 'integer',
            'direct_client_id' => 'sometimes|string|max:100',
            'settings' => 'sometimes|array',
        ]);

        try {
            DB::beginTransaction();

            $project = Project::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
                'timezone' => $request->timezone ?? 'Europe/Moscow',
                'currency' => $request->currency ?? 'RUB',
                'metrika_counters' => $request->metrika_counters,
                'direct_client_id' => $request->direct_client_id,
                'settings' => $request->get('settings', []),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => $project->load(['counters', 'campaigns']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить проект
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'status' => 'sometimes|string|in:active,paused,archived',
            'timezone' => 'sometimes|string|timezone',
            'currency' => 'sometimes|string|in:RUB,USD,EUR',
            'metrika_counters' => 'sometimes|array',
            'metrika_counters.*' => 'integer',
            'direct_client_id' => 'sometimes|string|max:100',
            'settings' => 'sometimes|array',
        ]);

        try {
            DB::beginTransaction();

            $project->update($request->only([
                'name', 'description', 'status', 'timezone', 'currency',
                'metrika_counters', 'direct_client_id', 'settings'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully',
                'data' => $project->fresh(['counters', 'campaigns']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить проект
     */
    public function destroy(Project $project): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Удаляем связанные данные
            $project->counters()->delete();
            $project->campaigns()->delete();
            $project->monthlyMetrika()->delete();
            $project->monthlyDirect()->delete();
            $project->monthlySeo()->delete();
            $project->monthlyAgeGroups()->delete();
            
            $project->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить список проектов для селекта (упрощенный)
     */
    public function getProjectsForSelect(): JsonResponse
    {
        $projects = Project::active()
            ->select(['id', 'name', 'status'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    /**
     * Получить статистику проекта
     */
    public function getProjectStats(Project $project, string $period): JsonResponse
    {
        $validPeriods = ['M', 'M-1', 'M-2'];
        
        if (!in_array($period, $validPeriods)) {
            throw ValidationException::withMessages([
                'period' => 'Invalid period. Available: M, M-1, M-2',
            ]);
        }

        try {
            $stats = $project->getStatsForPeriod($period);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'meta' => [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'period' => $period,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get project stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить интеграционные настройки проекта
     */
    public function getIntegrationSettings(Project $project): JsonResponse
    {
        $settings = $project->getIntegrationSettings();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Массовое обновление статусов проектов
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'projects' => 'required|array',
            'projects.*.id' => 'required|exists:projects,id',
            'projects.*.status' => 'required|string|in:active,paused,archived',
        ]);

        try {
            DB::beginTransaction();

            $updated = 0;
            foreach ($request->projects as $projectData) {
                $project = Project::find($projectData['id']);

                if ($project) {
                    $project->update(['status' => $projectData['status']]);
                    $updated++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Updated {$updated} projects",
                'data' => [
                    'updated_count' => $updated,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update projects',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}