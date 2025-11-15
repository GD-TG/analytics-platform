<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Project;
use App\Models\Counter;
use App\Models\DirectAccount;
use App\Models\Goal;
use App\Models\MetricsMonthly;
use App\Models\MetricsAgeMonthly;
use App\Models\DirectTotalsMonthly;
use App\Models\DirectCampaignMonthly;
use App\Models\SeoQueriesMonthly;
use Carbon\Carbon;

class DevSampleSeeder extends Seeder
{
    public function run()
    {
        // Очистим немного для idempotency (только для dev)
        Project::truncate();
        Counter::truncate();
        DirectAccount::truncate();
        Goal::truncate();
        // Таблицы метрик
        MetricsMonthly::truncate();
        MetricsAgeMonthly::truncate();
        DirectTotalsMonthly::truncate();
        DirectCampaignMonthly::truncate();
        SeoQueriesMonthly::truncate();

        $project = Project::create([
            'name' => 'Demo Project',
            'slug' => 'demo-project',
            'timezone' => 'Europe/Moscow',
            'currency' => 'RUB',
            'is_active' => true,
        ]);

        // Счётчик метрики
        $counter = Counter::create([
            'project_id' => $project->id,
            'counter_id' => 12345678,
            'name' => 'Demo Counter',
        ]);

        // Direct account
        $direct = DirectAccount::create([
            'project_id' => $project->id,
            'login' => 'demo_direct',
            'token' => 'demo_token',
        ]);

        // Цель
        $goal = Goal::create([
            'project_id' => $project->id,
            'name' => 'Demo Conversion Goal',
            'external_id' => 1,
            'is_conversion' => true,
            'config' => json_encode(['type' => 'page_view', 'path' => '/thank-you']),
        ]);

        // Создадим данные за последние 3 мес
        $months = [];
        for ($i = 0; $i < 3; $i++) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('Y-m');

            $year = (int)$date->format('Y');
            $month = (int)$date->format('m');

            MetricsMonthly::create([
                'project_id' => $project->id,
                'year' => $year,
                'month' => $month,
                'visits' => 1000 - ($i * 100),
                'users' => 800 - ($i * 80),
                'bounce' => 32.1,
                'avg_seconds' => 75 + ($i * 2),
                'conversions' => 35 - ($i * 2),
            ]);

            MetricsAgeMonthly::create([
                'project_id' => $project->id,
                'year' => $year,
                'month' => $month,
                'age_group' => '25-34',
                'visits' => 300 - ($i * 20),
                'users' => 250 - ($i * 18),
                'bounce' => 30.0,
                'avg_seconds' => 80,
                'conversions' => 10 - $i,
            ]);

            DirectTotalsMonthly::create([
                'project_id' => $project->id,
                'year' => $year,
                'month' => $month,
                'impressions' => 50000 - ($i * 2000),
                'clicks' => 2500 - ($i * 150),
                'ctr' => 5.0,
                'cpc' => 18.5,
                'conversions' => 60 - ($i * 3),
                'cpa' => 770,
                'cost' => 46250 - ($i * 2000),
            ]);

            $campaign = DirectCampaignMonthly::create([
                'project_id' => $project->id,
                'campaign_id' => 111 + $i,
                'name' => 'Brand ' . ($i + 1),
                'year' => $year,
                'month' => $month,
                'impressions' => 20000 - ($i * 1000),
                'clicks' => 1200 - ($i * 80),
                'ctr' => 6.0,
                'cpc' => 15.0,
                'conversions' => 25 - $i,
                'cost' => 18000 - ($i * 900),
            ]);

            SeoQueriesMonthly::create([
                'project_id' => $project->id,
                'year' => $year,
                'month' => $month,
                'query' => 'пример ' . ($i + 1),
                'position' => 12 + $i,
                'url' => '/page-' . ($i + 1),
                'visitors' => 400 - ($i * 10),
                'conversions' => 8 - $i,
            ]);
        }

        $this->command->info('Dev sample data created for project id: ' . $project->id);
    }
}
