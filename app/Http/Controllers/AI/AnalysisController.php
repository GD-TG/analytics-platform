<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\AI\AnalysisService;
use App\Helpers\PeriodHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    private AnalysisService $analysisService;

    public function __construct(AnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Получить AI анализ для проекта
     */
    public function analyzeProject(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|string|in:M,M-1,M-2',
        ]);

        $period = $request->get('period', 'M');
        $periodData = PeriodHelper::getPeriodByKey($period);

        try {
            $analysis = $this->analysisService->analyzeProject($project, $periodData);

            return response()->json([
                'success' => true,
                'data' => $analysis,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AI analysis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

