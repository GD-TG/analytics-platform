<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\MetricsMonthly;
use App\Models\MetricsAgeMonthly;
use App\Models\DirectTotalsMonthly;
use App\Models\DirectCampaignMonthly;
use App\Models\DirectCampaign;
use App\Models\SeoQueriesMonthly;
use App\Helpers\PeriodHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ReportApiController extends Controller
{
    /**
     * Get 3-month report for project (по ТЗ)
     * 
     * Contract: {
     *   "projectId": 123,
     *   "periods": ["2025-11","2025-10","2025-09"],
     *   "metrika": {
     *     "summary": [{"month":"2025-11","visits":1000,...}],
     *     "age": [{"month":"2025-11","age":"25-34",...}]
     *   },
     *   "direct": {
     *     "totals": [{"month":"2025-11","impressions":50000,...}],
     *     "campaigns": [{"campaignId":111,"name":"Brand","rows":[...]}]
     *   },
     *   "seo": {
     *     "summary": [{"month":"2025-11","visitors":400,...}],
     *     "queries": [{"month":"2025-11","query":"пример",...}]
     *   }
     * }
     */
    public function show($projectId): JsonResponse
    {
        try {
            $project = Project::where('user_id', auth()->id())->findOrFail($projectId);

            // Get periods M, M-1, M-2
            $periods = PeriodHelper::getReportPeriods();
            $periodKeys = ['M', 'M-1', 'M-2'];

            $report = [
                'projectId' => $project->id,
                'periods' => array_map(function ($key) use ($periods) {
                    return $periods[$key]['start']->format('Y-m');
                }, $periodKeys),
                'metrika' => [
                    'summary' => [],
                    'age' => [],
                ],
                'direct' => [
                    'totals' => [],
                    'campaigns' => [],
                ],
                'seo' => [
                    'summary' => [],
                    'queries' => [],
                ],
            ];

            // ========== Yandex Metrika Data ==========
            foreach ($periodKeys as $key) {
                $period = $periods[$key];
                $year = $period['start']->year;
                $month = $period['start']->month;
                $monthStr = $period['start']->format('Y-m');

                // Summary: visits, users, bounce rate, avg session duration, conversions
                $metrics = MetricsMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();

                if ($metrics) {
                    $report['metrika']['summary'][] = [
                        'month' => $monthStr,
                        'visits' => (int)($metrics->visits ?? 0),
                        'users' => (int)($metrics->users ?? 0),
                        'bounce' => round((float)($metrics->bounce_rate ?? 0), 1),
                        'avgSec' => (int)($metrics->avg_session_duration_sec ?? 0),
                        'conv' => (int)($metrics->conversions ?? 0),
                    ];
                }

                // Age demographics
                $ageData = MetricsAgeMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->get();

                foreach ($ageData as $ageMetric) {
                    $report['metrika']['age'][] = [
                        'month' => $monthStr,
                        'age' => $ageMetric->age_group ?? '0-100',
                        'visits' => (int)($ageMetric->visits ?? 0),
                        'users' => (int)($ageMetric->users ?? 0),
                        'bounce' => round((float)($ageMetric->bounce_rate ?? 0), 1),
                        'avgSec' => (int)($ageMetric->avg_session_duration_sec ?? 0),
                    ];
                }
            }

            // ========== Yandex Direct Data ==========
            foreach ($periodKeys as $key) {
                $period = $periods[$key];
                $year = $period['start']->year;
                $month = $period['start']->month;
                $monthStr = $period['start']->format('Y-m');

                // Totals: impressions, clicks, CTR, CPC, conversions, CPA, cost
                $totals = DirectTotalsMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();

                if ($totals) {
                    $report['direct']['totals'][] = [
                        'month' => $monthStr,
                        'impressions' => (int)($totals->impressions ?? 0),
                        'clicks' => (int)($totals->clicks ?? 0),
                        'ctr' => round((float)($totals->ctr_pct ?? 0), 1),
                        'cpc' => round((float)($totals->cpc ?? 0), 2),
                        'conv' => (int)($totals->conversions ?? 0),
                        'cpa' => round((float)($totals->cpa ?? 0), 2),
                        'cost' => round((float)($totals->cost ?? 0), 2),
                    ];
                }

                // Campaigns data
                $campaigns = DirectCampaignMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->with('directCampaign')
                    ->get();

                // Group by campaign
                $campaignsById = [];
                foreach ($campaigns as $campaignMonthly) {
                    $campaign = $campaignMonthly->directCampaign;
                    if (!$campaign) {
                        continue;
                    }

                    $campaignId = $campaign->campaign_id ?? $campaign->id;

                    if (!isset($campaignsById[$campaignId])) {
                        $campaignsById[$campaignId] = [
                            'campaignId' => $campaignId,
                            'name' => $campaign->name ?? "Campaign {$campaignId}",
                            'rows' => [],
                        ];
                    }

                    $campaignsById[$campaignId]['rows'][] = [
                        'month' => $monthStr,
                        'impressions' => (int)($campaignMonthly->impressions ?? 0),
                        'clicks' => (int)($campaignMonthly->clicks ?? 0),
                        'ctr' => round((float)($campaignMonthly->ctr_pct ?? 0), 1),
                        'cpc' => round((float)($campaignMonthly->cpc ?? 0), 2),
                        'conv' => (int)($campaignMonthly->conversions ?? 0),
                        'cpa' => round((float)($campaignMonthly->cpa ?? 0), 2),
                        'cost' => round((float)($campaignMonthly->cost ?? 0), 2),
                    ];
                }

                // Add campaigns to report
                foreach ($campaignsById as $campaign) {
                    $report['direct']['campaigns'][] = $campaign;
                }
            }

            // ========== SEO Data ==========
            foreach ($periodKeys as $key) {
                $period = $periods[$key];
                $year = $period['start']->year;
                $month = $period['start']->month;
                $monthStr = $period['start']->format('Y-m');

                // SEO Summary
                $seoData = SeoQueriesMonthly::where('project_id', $project->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->get();

                if ($seoData->count() > 0) {
                    $report['seo']['summary'][] = [
                        'month' => $monthStr,
                        'visitors' => (int)($seoData->sum('visitors') ?? 0),
                        'conv' => (int)($seoData->sum('conversions') ?? 0),
                    ];

                    // SEO Queries
                    foreach ($seoData as $query) {
                        $report['seo']['queries'][] = [
                            'month' => $monthStr,
                            'query' => $query->query ?? '',
                            'position' => (int)($query->position ?? 0),
                            'url' => $query->url ?? '',
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
