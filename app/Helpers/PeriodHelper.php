<?php

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use InvalidArgumentException;

class PeriodHelper
{
    /**
     * Получить период для отчёта: M, M-1, M-2
     */
    public static function getReportPeriods(string $month = null): array
    {
        $currentMonth = $month ? Carbon::parse($month)->startOfMonth() : Carbon::now()->startOfMonth();
        
        return [
            'M' => [
                'period' => 'M',
                'start' => $currentMonth->copy(),
                'end' => $currentMonth->copy()->endOfMonth(),
                'label' => $currentMonth->format('M Y'),
            ],
            'M-1' => [
                'period' => 'M-1',
                'start' => $currentMonth->copy()->subMonth()->startOfMonth(),
                'end' => $currentMonth->copy()->subMonth()->endOfMonth(),
                'label' => $currentMonth->copy()->subMonth()->format('M Y'),
            ],
            'M-2' => [
                'period' => 'M-2',
                'start' => $currentMonth->copy()->subMonths(2)->startOfMonth(),
                'end' => $currentMonth->copy()->subMonths(2)->endOfMonth(),
                'label' => $currentMonth->copy()->subMonths(2)->format('M Y'),
            ],
        ];
    }

    /**
     * Получить данные для конкретного периода по ключу (M, M-1, M-2)
     */
    public static function getPeriodByKey(string $periodKey, string $month = null): array
    {
        $periods = self::getReportPeriods($month);
        
        if (!isset($periods[$periodKey])) {
            throw new InvalidArgumentException("Invalid period key: {$periodKey}. Available: M, M-1, M-2");
        }
        
        return $periods[$periodKey];
    }

    /**
     * Получить все даты в периоде (для daily агрегации)
     */
    public static function getDatesInPeriod(Carbon $start, Carbon $end): array
    {
        $period = CarbonPeriod::create($start, $end);
        $dates = [];
        
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }
        
        return $dates;
    }

    /**
     * Получить период для daily синхронизации (последние 90 дней)
     */
    public static function getDailySyncPeriod(): array
    {
        $end = Carbon::now()->subDay(); // Вчера
        $start = Carbon::now()->subDays(90); // 90 дней назад
        
        return [
            'start' => $start,
            'end' => $end,
            'days' => self::getDatesInPeriod($start, $end),
        ];
    }

    /**
     * Получить период для месячной агрегации
     */
    public static function getMonthlyAggregationPeriod(string $month = null): array
    {
        $targetMonth = $month ? Carbon::parse($month) : Carbon::now()->subMonth();
        
        return [
            'start' => $targetMonth->copy()->startOfMonth(),
            'end' => $targetMonth->copy()->endOfMonth(),
            'month_key' => $targetMonth->format('Y-m'),
        ];
    }

    /**
     * Проверить, является ли период валидным для агрегации
     * (прошло минимум 3 дня после окончания месяца)
     */
    public static function isAggregatablePeriod(Carbon $periodEnd): bool
    {
        return Carbon::now()->diffInDays($periodEnd) >= 3;
    }

    /**
     * Получить human-readable описание периода
     */
    public static function getPeriodDescription(string $periodKey, string $month = null): string
    {
        $period = self::getPeriodByKey($periodKey, $month);
        
        return sprintf(
            '%s (%s - %s)',
            $period['label'],
            $period['start']->format('d.m.Y'),
            $period['end']->format('d.m.Y')
        );
    }

    /**
     * Получить периоды для сравнения (текущий vs предыдущий)
     */
    public static function getComparisonPeriods(string $periodKey, string $month = null): array
    {
        $current = self::getPeriodByKey($periodKey, $month);
        
        // Для M сравниваем с M-1, для M-1 с M-2 и т.д.
        $previousKey = match($periodKey) {
            'M' => 'M-1',
            'M-1' => 'M-2',
            'M-2' => 'M-3',
            default => 'M-1'
        };
        
        try {
            $previous = self::getPeriodByKey($previousKey, $month);
        } catch (InvalidArgumentException) {
            // Если предыдущего периода нет, используем тот же но за предыдущий год
            $previous = [
                'period' => $previousKey,
                'start' => $current['start']->copy()->subYear(),
                'end' => $current['end']->copy()->subYear(),
                'label' => $current['start']->copy()->subYear()->format('M Y'),
            ];
        }
        
        return [
            'current' => $current,
            'previous' => $previous,
        ];
    }

    /**
     * Получить все доступные периоды для селекта в UI
     */
    public static function getPeriodsForSelect(): array
    {
        $periods = self::getReportPeriods();
        
        return collect($periods)->mapWithKeys(function ($period) {
            return [$period['period'] => $period['label']];
        })->toArray();
    }

    /**
     * Валидация периода (не будущее время, не слишком старое)
     */
    public static function validatePeriod(Carbon $start, Carbon $end): bool
    {
        $now = Carbon::now();
        
        // Период не может быть в будущем
        if ($start->greaterThan($now) || $end->greaterThan($now)) {
            return false;
        }
        
        // Период не может быть старше 2 лет
        if ($start->lessThan($now->copy()->subYears(2))) {
            return false;
        }
        
        // Конец не может быть раньше начала
        if ($end->lessThan($start)) {
            return false;
        }
        
        return true;
    }
}