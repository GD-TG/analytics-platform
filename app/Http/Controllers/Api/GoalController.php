<?php

namespace App\Http\Controllers\Api;

use App\Models\Goal;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class GoalController extends Controller
{
    /**
     * List goals for project
     */
    public function index($projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);
            $goals = Goal::where('project_id', $projectId)->get();

            return response()->json([
                'success' => true,
                'data' => $goals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create goal for project
     */
    public function store(Request $request, $projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'external_id' => 'nullable|string',
                'config' => 'nullable|array',
            ]);

            $goal = Goal::create([
                'project_id' => $projectId,
                'name' => $validated['name'],
                'external_id' => $validated['external_id'] ?? null,
                'config' => $validated['config'] ?? [],
            ]);

            return response()->json([
                'success' => true,
                'data' => $goal,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update goal
     */
    public function update(Request $request, $projectId, $goalId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);
            $goal = Goal::where('project_id', $projectId)->findOrFail($goalId);

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'external_id' => 'nullable|string',
                'config' => 'nullable|array',
            ]);

            $goal->update($validated);

            return response()->json([
                'success' => true,
                'data' => $goal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete goal
     */
    public function destroy($projectId, $goalId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);
            $goal = Goal::where('project_id', $projectId)->findOrFail($goalId);
            $goal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Goal deleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
