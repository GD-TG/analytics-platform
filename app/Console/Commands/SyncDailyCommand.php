<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\Fetch\FetchMetrikaJob;
use App\Jobs\Fetch\FetchDirectJob;
use App\Models\Project;
use App\Helpers\DateHelper;

class SyncDailyCommand extends Command
{
    protected $signature = 'analytics:sync-daily';
    protected $description = 'Ежедневная синхронизация данных из Яндекс.Метрики и Директа';

    public function handle()
    {
        $this->info('Starting daily sync...');
        
        $syncPeriod = DateHelper::getDailySyncPeriod();
        $projects = Project::with(['counters', 'directAccounts'])->active()->get();

        $this->info("Sync period: {$syncPeriod['start']->format('Y-m-d')} to {$syncPeriod['end']->format('Y-m-d')}");
        $this->info("Found {$projects->count()} active projects");

        foreach ($projects as $project) {
            // Синхронизация Яндекс.Метрики
            foreach ($project->counters as $counter) {
                FetchMetrikaJob::dispatch($counter, $syncPeriod['start'], $syncPeriod['end'])
                    ->onQueue('high-priority');
            }

            // Синхронизация Яндекс.Директа
            foreach ($project->directAccounts as $directAccount) {
                FetchDirectJob::dispatch($directAccount, $syncPeriod['start'], $syncPeriod['end'])
                    ->onQueue('high-priority');
            }
        }

        $this->info('Daily sync jobs dispatched successfully');
    }
}