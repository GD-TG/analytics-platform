<?php

namespace App\Http\Controllers\Api;

use App\Models\Counter;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class CounterController extends Controller
{
    /**
     * List counters for project
     */
    public function index($projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);
            $counters = Counter::where('project_id', $projectId)->get();

            return response()->json([
                'success' => true,
                'data' => $counters,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create counter for project
     */
    public function store(Request $request, $projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);

            $validated = $request->validate([
                'provider' => 'required|string',
                'external_id' => 'required|string',
                'name' => 'nullable|string',
            ]);

            $counter = Counter::create([
                'project_id' => $projectId,
                'provider' => $validated['provider'],
                'external_id' => $validated['external_id'],
                'name' => $validated['name'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $counter,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete counter
     */
    public function destroy($projectId, $counterId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);
            $counter = Counter::where('project_id', $projectId)->findOrFail($counterId);
            $counter->delete();

            return response()->json([
                'success' => true,
                'message' => 'Counter deleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
