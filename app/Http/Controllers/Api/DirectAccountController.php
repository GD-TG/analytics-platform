<?php

namespace App\Http\Controllers\Api;

use App\Models\DirectAccount;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class DirectAccountController extends Controller
{
    /**
     * List direct accounts for project
     */
    public function index($projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);
            $accounts = DirectAccount::where('project_id', $projectId)->get();

            return response()->json([
                'success' => true,
                'data' => $accounts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create direct account for project
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

            $account = DirectAccount::create([
                'project_id' => $projectId,
                'provider' => $validated['provider'],
                'external_id' => $validated['external_id'],
                'name' => $validated['name'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $account,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete direct account
     */
    public function destroy($projectId, $accountId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);
            $account = DirectAccount::where('project_id', $projectId)->findOrFail($accountId);
            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Direct account deleted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
