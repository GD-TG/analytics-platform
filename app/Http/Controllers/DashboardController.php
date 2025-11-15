<?php

namespace App\Http\Controllers;

use App\Models\YandexAccount;
use App\Models\YandexCounter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get sync status for authenticated user's accounts
     */
    public function getSyncStatus(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get user's accounts with their counters
        $accounts = YandexAccount::where('user_id', $userId)
            ->with('counters')
            ->get();

        if ($accounts->isEmpty()) {
            return response()->json([
                'accounts' => [],
                'summary' => [
                    'total_accounts' => 0,
                    'active_accounts' => 0,
                    'total_counters' => 0,
                    'synced_counters' => 0,
                    'pending_counters' => 0,
                    'overdue_counters' => 0,
                    'sync_percentage' => 0,
                ],
            ]);
        }

        $syncIntervalMinutes = config('metrika.sync_interval_minutes', 60);
        $totalCounters = 0;
        $syncedCounters = 0;
        $pendingCounters = 0;
        $overdueCounters = 0;
        $activeAccounts = 0;

        $accountsData = [];

        foreach ($accounts as $account) {
            if (!$account->revoked) {
                $activeAccounts++;
            }

            $accountCountersData = [];
            foreach ($account->counters as $counter) {
                $totalCounters++;

                $counterData = [
                    'id' => $counter->id,
                    'counter_id' => $counter->counter_id,
                    'name' => $counter->name,
                    'active' => $counter->active,
                    'last_fetched_at' => $counter->last_fetched_at?->toIso8601String(),
                    'status' => $this->getCounterStatus($counter, $syncIntervalMinutes),
                ];

                // Count status
                if (!$counter->last_fetched_at) {
                    $pendingCounters++;
                } elseif ($counter->last_fetched_at->addMinutes($syncIntervalMinutes)->isPast()) {
                    $overdueCounters++;
                } else {
                    $syncedCounters++;
                }

                $accountCountersData[] = $counterData;
            }

            $accountsData[] = [
                'id' => $account->id,
                'revoked' => $account->revoked,
                'counters' => $accountCountersData,
            ];
        }

        $syncPercentage = $totalCounters > 0 ? round(($syncedCounters / $totalCounters) * 100) : 0;

        return response()->json([
            'accounts' => $accountsData,
            'summary' => [
                'total_accounts' => $accounts->count(),
                'active_accounts' => $activeAccounts,
                'total_counters' => $totalCounters,
                'synced_counters' => $syncedCounters,
                'pending_counters' => $pendingCounters,
                'overdue_counters' => $overdueCounters,
                'sync_percentage' => $syncPercentage,
                'sync_interval_minutes' => $syncIntervalMinutes,
            ],
        ]);
    }

    /**
     * Get sync statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get stats from metrics_monthly
        $stats = DB::table('metrics_monthly')
            ->join('yandex_counters', 'metrics_monthly.counter_id', '=', 'yandex_counters.id')
            ->join('projects', 'yandex_counters.project_id', '=', 'projects.id')
            ->where('projects.user_id', $userId)
            ->selectRaw('
                COUNT(DISTINCT metrics_monthly.id) as total_records,
                COUNT(DISTINCT metrics_monthly.counter_id) as counters_with_data,
                MAX(metrics_monthly.date) as latest_date,
                MIN(metrics_monthly.date) as earliest_date,
                SUM(CAST(metrics_monthly.visits AS DECIMAL)) as total_visits,
                SUM(CAST(metrics_monthly.users AS DECIMAL)) as total_users
            ')
            ->first();

        return response()->json([
            'total_records' => $stats->total_records ?? 0,
            'counters_with_data' => $stats->counters_with_data ?? 0,
            'latest_date' => $stats->latest_date,
            'earliest_date' => $stats->earliest_date,
            'total_visits' => (int) ($stats->total_visits ?? 0),
            'total_users' => (int) ($stats->total_users ?? 0),
        ]);
    }

    /**
     * Get recent sync jobs
     */
    public function getRecentSyncs(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $limit = $request->get('limit', 10);

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get recent syncs from job logs (if available)
        $syncs = DB::table('yandex_counters')
            ->join('projects', 'yandex_counters.project_id', '=', 'projects.id')
            ->where('projects.user_id', $userId)
            ->whereNotNull('yandex_counters.last_fetched_at')
            ->select('yandex_counters.counter_id', 'yandex_counters.last_fetched_at')
            ->orderByDesc('yandex_counters.last_fetched_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'syncs' => $syncs->map(function ($sync) {
                return [
                    'counter_id' => $sync->counter_id,
                    'synced_at' => $sync->last_fetched_at,
                    'time_ago' => $this->formatTimeAgo($sync->last_fetched_at),
                ];
            }),
        ]);
    }

    /**
     * Get counter status
     */
    private function getCounterStatus(YandexCounter $counter, int $syncIntervalMinutes): string
    {
        if (!$counter->active) {
            return 'inactive';
        }

        if (!$counter->last_fetched_at) {
            return 'pending';
        }

        if ($counter->last_fetched_at->addMinutes($syncIntervalMinutes)->isPast()) {
            return 'overdue';
        }

        return 'synced';
    }

    /**
     * Format time ago
     */
    private function formatTimeAgo(string $date): string
    {
        $carbon = Carbon::parse($date);
        $minutesAgo = now()->diffInMinutes($carbon);

        if ($minutesAgo < 1) {
            return 'just now';
        } elseif ($minutesAgo < 60) {
            return "{$minutesAgo}m ago";
        } else {
            $hoursAgo = now()->diffInHours($carbon);
            if ($hoursAgo < 24) {
                return "{$hoursAgo}h ago";
            } else {
                $daysAgo = now()->diffInDays($carbon);
                return "{$daysAgo}d ago";
            }
        }
    }
}
