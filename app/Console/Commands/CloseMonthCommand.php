<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\Aggregate\AggregateMetrikaMonthlyJob;
use App\Jobs\Aggregate\AggregateDirectMonthlyJob;
use App\Jobs\Aggregate\AggregateSeoMonthlyJob;
use App\Models\Project;
use App\Helpers\PeriodHelper;

class CloseMonthCommand extends Command
{
    protected $signature = 'analytics:close-month {month?}';
    protected $description = 'Агрегация месячных данных и формирование отчетов';

    public function handle()
    {
        $month = $this->argument('month') ?: now()->subMonth()->format('Y-m');
        
        $this->info("Starting monthly aggregation for {$month}...");

        $aggregationPeriod = PeriodHelper::getMonthlyAggregationPeriod($month);
        
        if (!PeriodHelper::isAggregatablePeriod($aggregationPeriod['end'])) {
            $this->warn("Period {$month} is not ready for aggregation (too recent)");
            return 1;
        }

        $projects = Project::active()->get();

        foreach ($projects as $project) {
            // Агрегация Яндекс.Метрики
            AggregateMetrikaMonthlyJob::dispatch($project, $aggregationPeriod)
                ->onQueue('aggregation');

            // Агрегация Яндекс.Директа
            AggregateDirectMonthlyJob::dispatch($project, $aggregationPeriod)
                ->onQueue('aggregation');

            // Агрегация SEO данных
            AggregateSeoMonthlyJob::dispatch($project, $aggregationPeriod)
                ->onQueue('aggregation');
        }

        $this->info("Monthly aggregation for {$month} started for {$projects->count()} projects");
        return 0;
    }
}