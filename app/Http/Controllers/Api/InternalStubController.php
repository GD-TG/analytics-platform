<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class InternalStubController extends Controller
{
    public function storeProject(Request $request): JsonResponse
    {
        $name = $request->input('name', 'New Project');
        $slug = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($name));

        // Возвращаем заглушку — в реальной системе создаётся Project
        return response()->json([
            'id' => rand(1000, 9999),
            'name' => $name,
            'slug' => $slug,
            'message' => 'Stub: project created',
        ], 201);
    }

    public function addCounter(Request $request, $projectId): JsonResponse
    {
        $counterId = $request->input('counter_id', null);

        return response()->json([
            'projectId' => (int)$projectId,
            'counterId' => $counterId,
            'message' => 'Stub: counter attached',
        ], 200);
    }

    public function addDirectAccount(Request $request, $projectId): JsonResponse
    {
        $login = $request->input('login', 'direct_user');

        return response()->json([
            'projectId' => (int)$projectId,
            'login' => $login,
            'message' => 'Stub: direct account attached',
        ], 200);
    }

    public function addGoal(Request $request, $projectId): JsonResponse
    {
        $name = $request->input('name', 'Conversion Goal');
        $isConversion = filter_var($request->input('is_conversion', true), FILTER_VALIDATE_BOOLEAN);

        return response()->json([
            'projectId' => (int)$projectId,
            'goal' => [
                'id' => rand(10000, 99999),
                'name' => $name,
                'is_conversion' => $isConversion,
            ],
            'message' => 'Stub: goal registered',
        ], 201);
    }

    public function triggerSync(Request $request, $projectId): JsonResponse
    {
        // Просто подтверждаем, что задача поставлена в очередь
        return response()->json([
            'projectId' => (int)$projectId,
            'status' => 'queued',
            'message' => 'Stub: sync queued',
        ], 202);
    }

    public function showReport(Request $request, $projectId): JsonResponse
    {
        // Собираем периоды за последние 3 месяца
        $periods = [];
        for ($i = 0; $i < 3; $i++) {
            $periods[] = Carbon::now()->subMonths($i)->format('Y-m');
        }

        // Соберём простые заглушки, соответствующие контракту
        $metricaSummary = [];
        $metricaAge = [];
        $directTotals = [];
        $directCampaigns = [];
        $seoSummary = [];
        $seoQueries = [];

        foreach ($periods as $p) {
            $metricaSummary[] = [
                'month' => $p,
                'visits' => 1000,
                'users' => 800,
                'bounce' => 32.1,
                'avgSec' => 75,
                'conv' => 35,
            ];

            $metricaAge[] = [
                'month' => $p,
                'age' => '25-34',
                'visits' => 300,
                'users' => 250,
                'bounce' => 30.0,
                'avgSec' => 80,
            ];

            $directTotals[] = [
                'month' => $p,
                'impressions' => 50000,
                'clicks' => 2500,
                'ctr' => 5.0,
                'cpc' => 18.5,
                'conv' => 60,
                'cpa' => 770,
                'cost' => 46250,
            ];

            $directCampaigns[] = [
                'campaignId' => 111,
                'name' => 'Brand',
                'rows' => [
                    [
                        'month' => $p,
                        'impressions' => 20000,
                        'clicks' => 1200,
                        'ctr' => 6.0,
                        'cpc' => 15.0,
                        'conv' => 25,
                        'cost' => 18000,
                    ],
                ],
            ];

            $seoSummary[] = [
                'month' => $p,
                'visitors' => 400,
                'conv' => 8,
            ];

            $seoQueries[] = [
                'month' => $p,
                'query' => 'пример',
                'position' => 12,
                'url' => '/page',
            ];
        }

        $payload = [
            'projectId' => (int)$projectId,
            'periods' => $periods,
            'metrica' => [
                'summary' => $metricaSummary,
                'age' => $metricaAge,
            ],
            'direct' => [
                'totals' => $directTotals,
                'campaigns' => $directCampaigns,
            ],
            'seo' => [
                'summary' => $seoSummary,
                'queries' => $seoQueries,
            ],
        ];

        return response()->json($payload, 200);
    }
}
