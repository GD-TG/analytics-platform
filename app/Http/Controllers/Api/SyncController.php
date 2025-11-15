<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Controller;

class SyncController extends Controller
{
    /**
     * Trigger sync for project
     */
    public function trigger($projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);

            // Dispatch sync job via artisan command (queue)
            Artisan::queue('analytics:sync', ['project_id' => $projectId]);

            return response()->json([
                'success' => true,
                'message' => 'Sync job queued',
                'project_id' => $projectId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status
     */
    public function status($projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);

            // Get last sync status from cache or database
            $syncStatus = cache()->get("sync_status_{$projectId}", [
                'status' => 'idle',
                'last_sync' => null,
                'progress' => 0,
            ]);

            return response()->json([
                'success' => true,
                'data' => $syncStatus,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
