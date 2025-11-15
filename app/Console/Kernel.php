<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Регистрация команд для приложения.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Определение расписания задач.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Синхронизация данных каждый час (configurable via SYNC_INTERVAL_MINUTES)
        $syncInterval = env('SYNC_INTERVAL_MINUTES', 60);
        $schedule->command('analytics:sync')
                 ->everyMinutes($syncInterval)
                 ->timezone('Europe/Moscow')
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/sync.log'));

        // Ежедневная синхронизация данных в 03:00 ночи
        $schedule->command('analytics:sync-daily')
                 ->dailyAt('03:00')
                 ->timezone('Europe/Moscow')
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/sync-daily.log'));

        // Ежемесячная агрегация данных 1-го числа в 04:00
        $schedule->command('analytics:close-month')
                 ->monthlyOn(1, '04:00')
                 ->timezone('Europe/Moscow')
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/close-month.log'));

        // Ретри failed jobs каждые 5 минут
        $schedule->command('queue:retry all')
                 ->everyFiveMinutes()
                 ->withoutOverlapping();

        // Очистка старых failed jobs раз в неделю
        $schedule->command('queue:prune-failed')
                 ->weekly()
                 ->sundays()
                 ->at('01:00');

        // Очистка устаревших raw данных (старше 6 месяцев)
        $schedule->command('analytics:clean-raw-data')
                 ->monthly()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/clean-raw-data.log'));

        // Проверка здоровья интеграций каждые 10 минут
        $schedule->command('analytics:check-integrations')
                 ->everyTenMinutes()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/check-integrations.log'));

        // Принудительный рестарт воркеров очереди каждый час
        $schedule->command('queue:restart')
                 ->hourly();

        // Мониторинг очереди каждые 5 минут
        $schedule->command('queue:monitor', ['--max=100'])
                 ->everyFiveMinutes()
                 ->withoutOverlapping();

        // Бэкап базы данных ежедневно в 02:00
        $schedule->command('db:backup')
                 ->dailyAt('02:00')
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/db-backup.log'));

        // Очистка кеша метрик каждые 6 часов
        $schedule->command('analytics:clear-cache')
                 ->everySixHours()
                 ->withoutOverlapping();
    }

    /**
     * Получить времяzone, которое должно быть использовано по умолчанию для событий расписания.
     */
    protected function scheduleTimezone(): string
    {
        return 'Europe/Moscow';
    }
}