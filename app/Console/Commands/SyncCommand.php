<?php

namespace App\Console\Commands;

use App\Models\YandexAccount;
use App\Models\YandexCounter;
use App\Jobs\Fetch\FetchMetrikaJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:sync {--account-id= : Specific account ID to sync} {--counter-id= : Specific counter ID to sync} {--force : Force sync regardless of last_fetched_at}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Synchronize Yandex Metrika data for all active accounts and counters';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting Metrika data sync...');
        $startTime = microtime(true);

        try {
            // Get accounts to sync
            $query = YandexAccount::query()
                ->where('revoked', false)
                ->with('counters');

            if ($accountId = $this->option('account-id')) {
                $query->where('id', $accountId);
                $this->info("Syncing specific account: {$accountId}");
            }

            $accounts = $query->get();

            if ($accounts->isEmpty()) {
                $this->warn('No active accounts found to sync');
                return 1;
            }

            $this->info("Found {$accounts->count()} active account(s)");

            $totalCounters = 0;
            $successCount = 0;
            $failureCount = 0;

            // Process each account
            /** @var \App\Models\YandexAccount $account */
            foreach ($accounts as $account) {
                $this->line("");
                $this->info("Account: {$account->id} (User: {$account->user_id})");

                // Get counters to sync
                $counterQuery = $account->counters()->where('active', true);

                if ($counterId = $this->option('counter-id')) {
                    $counterQuery->where('id', $counterId);
                }

                $counters = $counterQuery->get();

                if ($counters->isEmpty()) {
                    $this->warn("  No active counters for account {$account->id}");
                    continue;
                }

                $this->info("  Found {$counters->count()} counter(s)");

                // Process each counter
                /** @var \App\Models\YandexCounter $counter */
                foreach ($counters as $counter) {
                    $totalCounters++;

                    // Check if sync is needed (respecting last_fetched_at)
                    if (!$this->shouldSync($counter)) {
                        $this->line("  ⏭️  Counter {$counter->id}: recently synced, skipping");
                        continue;
                    }

                    try {
                        // Queue fetch job
                        FetchMetrikaJob::dispatch(
                            accountId: $account->id,
                            counterId: $counter->id,
                            userId: $account->user_id
                        );

                        // Update last_fetched_at
                        $counter->update(['last_fetched_at' => now()]);

                        $this->line("  ✅ Counter {$counter->id}: queued for sync");
                        $successCount++;
                    } catch (\Exception $e) {
                        $this->error("  ❌ Counter {$counter->id}: {$e->getMessage()}");
                        Log::error('Sync command failed for counter', [
                            'counter_id' => $counter->id,
                            'account_id' => $account->id,
                            'error' => $e->getMessage(),
                        ]);
                        $failureCount++;
                    }
                }
            }

            // Summary
            $this->line("");
            $this->info("═══════════════════════════════════");
            $this->info("Sync Summary:");
            $this->info("  Total counters: {$totalCounters}");
            $this->info("  Queued: {$successCount}");
            if ($failureCount > 0) {
                $this->error("  Failed: {$failureCount}");
            }
            $this->info("═══════════════════════════════════");

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✅ Sync completed in {$duration}s");

            return 0;
        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            Log::error('Sync command error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Determine if counter should be synced based on last_fetched_at
     */
    private function shouldSync(YandexCounter $counter): bool
    {
        // Force sync if requested
        if ($this->option('force')) {
            return true;
        }

        // Never synced before - always sync
        if (!$counter->last_fetched_at) {
            return true;
        }

        // Get sync interval from config (default 60 minutes)
        $syncIntervalMinutes = config('metrika.sync_interval_minutes', 60);

        // Sync if enough time has passed since last fetch
        return $counter->last_fetched_at->addMinutes($syncIntervalMinutes)->isPast();
    }
}
