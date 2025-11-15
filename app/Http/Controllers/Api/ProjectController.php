<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ProjectController extends Controller
{
    /**
     * List all projects for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $projects = Project::where('user_id', auth()->id())
                ->with(['counters', 'directAccounts', 'goals'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $projects,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get project by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())
                ->with(['counters', 'directAccounts', 'goals'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $project,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new project
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'settings' => 'nullable|array',
            ]);

            $project = Project::create([
                'user_id' => auth()->id(),
                'name' => $validated['name'],
                'settings' => $validated['settings'] ?? [],
            ]);

            return response()->json([
                'success' => true,
                'data' => $project,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update project
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($id);

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'settings' => 'nullable|array',
            ]);

            $project->update($validated);

            return response()->json([
                'success' => true,
                'data' => $project,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete project
     */
    public function destroy($id): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($id);
            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'Project deleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
