<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Кастомные команды для аналитики
Artisan::command('analytics:status', function () {
    $this->info('Analytics Platform Status:');
    $this->line('- Daily sync: ' . (Artisan::call('analytics:sync-daily') === 0 ? 'OK' : 'ERROR'));
    $this->line('- Monthly aggregation: ' . (Artisan::call('analytics:close-month') === 0 ? 'OK' : 'ERROR'));
})->purpose('Check analytics platform status');