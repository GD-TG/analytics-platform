<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Services\AI\HuggingFaceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class AnalyticsAIController extends Controller
{
    private HuggingFaceService $aiService;

    public function __construct(HuggingFaceService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Analyze business pulse for project
     */
    public function busPulse($projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);

            // Fetch latest metrics for the project
            $metrics = $this->getProjectMetrics($projectId);

            $result = $this->aiService->analyzeBusPulse($metrics);

            return response()->json([
                'success' => $result['success'],
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
     * Analyze traffic sources (pie chart)
     */
    public function sourcePie($projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);

            $sources = $this->getTrafficSources($projectId);
            $result = $this->aiService->analyzeSourcePie($sources);

            return response()->json([
                'success' => $result['success'],
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
     * Compare metrics between periods
     */
    public function compareMetrics($projectId, Request $request): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);

            $current = $request->get('current', []);
            $previous = $request->get('previous', []);

            $result = $this->aiService->compareMetrics($current, $previous);

            return response()->json([
                'success' => $result['success'],
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
     * Generate thermometer status
     */
    public function thermometer($projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);

            $metrics = $this->getProjectMetrics($projectId);
            $status = $this->aiService->generateThermometer($metrics);

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate activity heatmap
     */
    public function activityHeatmap($projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);

            $dailyData = $this->getDailyActivityData($projectId);
            $result = $this->aiService->generateActivityHeatmap($dailyData);

            return response()->json([
                'success' => $result['success'],
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Helper methods

    private function getProjectMetrics($projectId): array
    {
        // Fetch latest metrics from MetricsMonthly
        // This is a simplified version - expand as needed
        return [
            'visits' => 5000,
            'users' => 2000,
            'conversions' => 150,
            'bounce_rate' => 45,
            'avg_session_duration' => 240,
        ];
    }

    private function getTrafficSources($projectId): array
    {
        // Fetch traffic source data
        return [
            ['name' => 'Google Organic', 'visits' => 3000],
            ['name' => 'Direct', 'visits' => 1000],
            ['name' => 'Social Media', 'visits' => 800],
            ['name' => 'Referral', 'visits' => 200],
        ];
    }

    private function getDailyActivityData($projectId): array
    {
        // Fetch daily activity for heatmap
        $data = [];
        for ($i = 0; $i < 30; $i++) {
            $day = now()->subDays($i)->format('Y-m-d');
            $data[$day] = [
                'visits' => rand(50, 500),
                'users' => rand(20, 300),
            ];
        }
        return $data;
    }
}
