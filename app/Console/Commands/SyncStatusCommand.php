<?php

namespace App\Console\Commands;

use App\Models\YandexAccount;
use App\Models\YandexCounter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:sync-status';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Show sync status for all accounts and counters';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📊 Sync Status Report');
        $this->line('═══════════════════════════════════════════════════');

        // Get all accounts
        $accounts = YandexAccount::with('counters')->get();

        if ($accounts->isEmpty()) {
            $this->warn('No accounts found');
            return 1;
        }

        $totalCounters = 0;
        $syncedCounters = 0;
        $pendingCounters = 0;
        $overdueCounters = 0;

        $syncIntervalMinutes = config('metrika.sync_interval_minutes', 60);

        /** @var \App\Models\YandexAccount $account */
        foreach ($accounts as $account) {
            $this->line("");
            $this->info("👤 Account {$account->id} (User: {$account->user_id})");
            $this->line("   Status: " . ($account->revoked ? '❌ REVOKED' : '✅ ACTIVE'));

            if ($account->counters->isEmpty()) {
                $this->warn("   No counters");
                continue;
            }

            $this->info("   Counters: {$account->counters->count()}");

            /** @var \App\Models\YandexCounter $counter */
            foreach ($account->counters as $counter) {
                $totalCounters++;

                $statusIcon = $counter->active ? '✅' : '⏹️';
                $status = $counter->active ? 'ACTIVE' : 'INACTIVE';

                if (!$counter->last_fetched_at) {
                    $pendingCounters++;
                    $this->line("   {$statusIcon} Counter {$counter->counter_id}: PENDING (never synced)");
                } elseif ($counter->last_fetched_at->addMinutes($syncIntervalMinutes)->isPast()) {
                    $overdueCounters++;
                    $minutesAgo = now()->diffInMinutes($counter->last_fetched_at);
                    $this->error("   {$statusIcon} Counter {$counter->counter_id}: OVERDUE (last sync {$minutesAgo}m ago)");
                } else {
                    $syncedCounters++;
                    $minutesAgo = now()->diffInMinutes($counter->last_fetched_at);
                    $nextSyncIn = $counter->last_fetched_at->addMinutes($syncIntervalMinutes)->diffInMinutes();
                    $this->line("   {$statusIcon} Counter {$counter->counter_id}: OK (synced {$minutesAgo}m ago, next in {$nextSyncIn}m)");
                }
            }
        }

        // Summary
        $this->line("");
        $this->line('═══════════════════════════════════════════════════');
        $this->info('Summary:');
        $this->line("   Total counters: {$totalCounters}");
        $this->line("   ✅ In sync: {$syncedCounters}");
        if ($pendingCounters > 0) {
            $this->warn("   ⏳ Pending: {$pendingCounters}");
        }
        if ($overdueCounters > 0) {
            $this->error("   🔴 Overdue: {$overdueCounters}");
        }

        $syncPercentage = $totalCounters > 0 ? round(($syncedCounters / $totalCounters) * 100) : 0;
        $this->info("   Overall: {$syncPercentage}%");

        // Next scheduled sync
        $this->line("");
        $this->info("⏰ Next scheduled sync: in ~{$syncIntervalMinutes} minutes");
        $this->info("   (Run 'php artisan analytics:sync --force' to sync now)");

        return 0;
    }
}
