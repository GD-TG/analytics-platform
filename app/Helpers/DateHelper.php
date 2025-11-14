<?php

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use InvalidArgumentException;

class DateHelper
{
    /**
     * Форматирование даты для API Яндекс.Метрики
     */
    public static function formatForApi(Carbon $date): string
    {
        return $date->format('Y-m-d');
    }

    /**
     * Форматирование даты для UI (человеко-читаемый формат)
     */
    public static function formatForUI(Carbon $date, bool $withTime = false): string
    {
        if ($withTime) {
            return $date->format('d.m.Y H:i');
        }
        
        return $date->format('d.m.Y');
    }

    /**
     * Форматирование периода для UI
     */
    public static function formatPeriodForUI(Carbon $start, Carbon $end): string
    {
        if ($start->format('Y-m') === $end->format('Y-m')) {
            // В рамках одного месяца
            return $start->format('d') . '-' . $end->format('d.m.Y');
        } elseif ($start->format('Y') === $end->format('Y')) {
            // В рамках одного года
            return $start->format('d.m') . ' - ' . $end->format('d.m.Y');
        } else {
            // Разные годы
            return $start->format('d.m.Y') . ' - ' . $end->format('d.m.Y');
        }
    }

    /**
     * Получить первый день текущего месяца
     */
    public static function getFirstDayOfMonth(?string $month = null): Carbon
    {
        if ($month) {
            return Carbon::parse($month)->startOfMonth();
        }
        
        return Carbon::now()->startOfMonth();
    }

    /**
     * Получить последний день текущего месяца
     */
    public static function getLastDayOfMonth(?string $month = null): Carbon
    {
        if ($month) {
            return Carbon::parse($month)->endOfMonth();
        }
        
        return Carbon::now()->endOfMonth();
    }

    /**
     * Проверить, является ли дата сегодняшним днем
     */
    public static function isToday(Carbon $date): bool
    {
        return $date->isToday();
    }

    /**
     * Проверить, является ли дата вчерашним днем
     */
    public static function isYesterday(Carbon $date): bool
    {
        return $date->isYesterday();
    }

    /**
     * Проверить, находится ли дата в текущем месяце
     */
    public static function isInCurrentMonth(Carbon $date): bool
    {
        return $date->format('Y-m') === Carbon::now()->format('Y-m');
    }

    /**
     * Получить разницу в рабочих днях (исключая выходные)
     */
    public static function getBusinessDaysCount(Carbon $start, Carbon $end): int
    {
        $period = CarbonPeriod::create($start, $end);
        $businessDays = 0;
        
        foreach ($period as $date) {
            if (!$date->isWeekend()) {
                $businessDays++;
            }
        }
        
        return $businessDays;
    }

    /**
     * Получить начало текущей недели (понедельник)
     */
    public static function getStartOfWeek(?Carbon $date = null): Carbon
    {
        $date = $date ?: Carbon::now();
        return $date->copy()->startOfWeek();
    }

    /**
     * Получить конец текущей недели (воскресенье)
     */
    public static function getEndOfWeek(?Carbon $date = null): Carbon
    {
        $date = $date ?: Carbon::now();
        return $date->copy()->endOfWeek();
    }

    /**
     * Получить квартал для даты
     */
    public static function getQuarter(Carbon $date): int
    {
        return (int) ceil($date->month / 3);
    }

    /**
     * Получить начало квартала
     */
    public static function getStartOfQuarter(Carbon $date): Carbon
    {
        $quarter = self::getQuarter($date);
        $month = ($quarter - 1) * 3 + 1;
        
        return $date->copy()->month($month)->startOfMonth();
    }

    /**
     * Получить конец квартала
     */
    public static function getEndOfQuarter(Carbon $date): Carbon
    {
        $quarter = self::getQuarter($date);
        $month = $quarter * 3;
        
        return $date->copy()->month($month)->endOfMonth();
    }

    /**
     * Получить список месяцев для селекта
     */
    public static function getMonthsForSelect(int $count = 12): array
    {
        $months = [];
        $current = Carbon::now()->startOfMonth();
        
        for ($i = 0; $i < $count; $i++) {
            $monthKey = $current->format('Y-m');
            $monthLabel = $current->translatedFormat('F Y');
            
            $months[$monthKey] = $monthLabel;
            $current->subMonth();
        }
        
        return $months;
    }

    /**
     * Преобразовать период в массив дней
     */
    public static function periodToDaysArray(Carbon $start, Carbon $end): array
    {
        $period = CarbonPeriod::create($start, $end);
        $days = [];
        
        foreach ($period as $date) {
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('d'),
                'weekday' => $date->translatedFormat('D'),
                'is_weekend' => $date->isWeekend(),
            ];
        }
        
        return $days;
    }

    /**
     * Получить временные метки для графика
     */
    public static function getChartTimestamps(Carbon $start, Carbon $end, string $groupBy = 'day'): array
    {
        $period = CarbonPeriod::create($start, $end);
        $timestamps = [];
        
        foreach ($period as $date) {
            switch ($groupBy) {
                case 'hour':
                    for ($hour = 0; $hour < 24; $hour++) {
                        $timestamp = $date->copy()->setHour($hour);
                        $timestamps[] = $timestamp->format('Y-m-d H:00:00');
                    }
                    break;
                    
                case 'week':
                    if ($date->dayOfWeek === Carbon::MONDAY) {
                        $timestamps[] = $date->format('Y-m-d');
                    }
                    break;
                    
                case 'month':
                    if ($date->day === 1) {
                        $timestamps[] = $date->format('Y-m-d');
                    }
                    break;
                    
                case 'day':
                default:
                    $timestamps[] = $date->format('Y-m-d');
                    break;
            }
        }
        
        return $timestamps;
    }

    /**
     * Проверить, является ли период валидным для синхронизации
     */
    public static function isValidSyncPeriod(Carbon $start, Carbon $end): bool
    {
        $now = Carbon::now();
        
        // Период не может быть в будущем
        if ($start->greaterThan($now) || $end->greaterThan($now)) {
            return false;
        }
        
        // Максимальный период - 90 дней (ограничение API)
        if ($start->diffInDays($end) > 90) {
            return false;
        }
        
        // Начало не может быть позже конца
        if ($start->greaterThan($end)) {
            return false;
        }
        
        return true;
    }

    /**
     * Получить период "сегодня"
     */
    public static function getTodayPeriod(): array
    {
        $today = Carbon::today();
        
        return [
            'start' => $today,
            'end' => $today->copy()->endOfDay(),
            'label' => 'Сегодня',
        ];
    }

    /**
     * Получить период "вчера"
     */
    public static function getYesterdayPeriod(): array
    {
        $yesterday = Carbon::yesterday();
        
        return [
            'start' => $yesterday,
            'end' => $yesterday->copy()->endOfDay(),
            'label' => 'Вчера',
        ];
    }

    /**
     * Получить период "текущая неделя"
     */
    public static function getCurrentWeekPeriod(): array
    {
        $start = self::getStartOfWeek();
        $end = self::getEndOfWeek();
        
        return [
            'start' => $start,
            'end' => $end,
            'label' => 'Текущая неделя',
        ];
    }

    /**
     * Получить период "прошлая неделя"
     */
    public static function getLastWeekPeriod(): array
    {
        $start = self::getStartOfWeek()->subWeek();
        $end = self::getEndOfWeek()->subWeek();
        
        return [
            'start' => $start,
            'end' => $end,
            'label' => 'Прошлая неделя',
        ];
    }

    /**
     * Получить период "текущий месяц"
     */
    public static function getCurrentMonthPeriod(): array
    {
        $start = self::getFirstDayOfMonth();
        $end = self::getLastDayOfMonth();
        
        return [
            'start' => $start,
            'end' => $end,
            'label' => 'Текущий месяц',
        ];
    }

    /**
     * Получить период "прошлый месяц"
     */
    public static function getLastMonthPeriod(): array
    {
        $start = self::getFirstDayOfMonth()->subMonth();
        $end = self::getLastDayOfMonth()->subMonth();
        
        return [
            'start' => $start,
            'end' => $end,
            'label' => 'Прошлый месяц',
        ];
    }

    /**
     * Получить человеко-читаемую разницу во времени
     */
    public static function getHumanDiff(Carbon $from, ?Carbon $to = null): string
    {
        $to = $to ?: Carbon::now();
        
        $diff = $from->diff($to);
        
        if ($diff->days > 30) {
            return $from->diffForHumans($to);
        } elseif ($diff->days > 0) {
            return $diff->days . ' ' . self::pluralize($diff->days, ['день', 'дня', 'дней']);
        } elseif ($diff->h > 0) {
            return $diff->h . ' ' . self::pluralize($diff->h, ['час', 'часа', 'часов']);
        } else {
            return $diff->i . ' ' . self::pluralize($diff->i, ['минуту', 'минуты', 'минут']);
        }
    }

    /**
     * Склонение слов для русского языка
     */
    private static function pluralize(int $number, array $titles): string
    {
        $cases = [2, 0, 1, 1, 1, 2];
        return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }

    /**
     * Проверить, является ли дата выходным днем
     */
    public static function isHoliday(Carbon $date): bool
    {
        // Статические праздники России (можно вынести в конфиг)
        $holidays = [
            '01-01', // Новый год
            '01-02', 
            '01-07', // Рождество
            '02-23', // День защитника отечества
            '03-08', // Международный женский день
            '05-01', // Праздник весны и труда
            '05-09', // День Победы
            '06-12', // День России
            '11-04', // День народного единства
        ];
        
        return $date->isWeekend() || in_array($date->format('m-d'), $holidays);
    }

    /**
     * Получить временную зону пользователя (по умолчанию Europe/Moscow)
     */
    public static function getUserTimezone(): string
    {
        return config('app.timezone', 'Europe/Moscow');
    }

    /**
     * Конвертировать дату в временную зону пользователя
     */
    public static function toUserTimezone(Carbon $date, ?string $timezone = null): Carbon
    {
        $timezone = $timezone ?: self::getUserTimezone();
        return $date->copy()->timezone($timezone);
    }

    /**
     * Получить возраст по дате рождения
     */
    public static function getAge(Carbon $birthDate): int
    {
        return $birthDate->age;
    }

    /**
     * Проверить, истекла ли дата
     */
    public static function isExpired(Carbon $date): bool
    {
        return $date->isPast();
    }

    /**
     * Получить оставшееся время до даты
     */
    public static function getTimeLeft(Carbon $date): string
    {
        if ($date->isPast()) {
            return 'Истекло';
        }
        
        return self::getHumanDiff(Carbon::now(), $date);
    }
}