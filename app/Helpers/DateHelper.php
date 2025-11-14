<?php

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use InvalidArgumentException;

class DateHelper
{
    // Константы для часто используемых форматов
    public const API_DATE_FORMAT = 'Y-m-d';
    public const UI_DATE_FORMAT = 'd.m.Y';
    public const UI_DATETIME_FORMAT = 'd.m.Y H:i';
    
    // Российские праздники (можно вынести в конфиг)
    public const HOLIDAYS = [
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

    /**
     * Форматирование даты для API Яндекс.Метрики
     */
    public static function formatForApi(CarbonInterface $date): string
    {
        return $date->format(self::API_DATE_FORMAT);
    }

    /**
     * Форматирование даты для UI (человеко-читаемый формат)
     */
    public static function formatForUI(CarbonInterface $date, bool $withTime = false): string
    {
        return $date->format($withTime ? self::UI_DATETIME_FORMAT : self::UI_DATE_FORMAT);
    }

    /**
     * Получить период для ежедневной синхронизации (вчерашний день)
     */
    public static function getDailySyncPeriod(): array
    {
        $yesterday = Carbon::yesterday();
        
        return [
            'start' => $yesterday->copy()->startOfDay(),
            'end' => $yesterday->copy()->endOfDay(),
            'label' => 'Вчера (' . $yesterday->format('d.m.Y') . ')',
        ];
    }

    /**
     * Получить период за последние N дней
     */
    public static function getLastDaysPeriod(int $days): array
    {
        if ($days <= 0) {
            throw new InvalidArgumentException('Days count must be positive');
        }

        $end = Carbon::yesterday()->endOfDay();
        $start = Carbon::yesterday()->subDays($days - 1)->startOfDay();
        
        return [
            'start' => $start,
            'end' => $end,
            'label' => "Последние {$days} " . self::pluralize($days, ['день', 'дня', 'дней']),
        ];
    }

    /**
     * Получить период за текущий год
     */
    public static function getCurrentYearPeriod(): array
    {
        $start = Carbon::now()->startOfYear();
        $end = Carbon::now()->endOfYear();
        
        return [
            'start' => $start,
            'end' => $end,
            'label' => 'Текущий год',
        ];
    }

    /**
     * Получить период за прошлый год
     */
    public static function getLastYearPeriod(): array
    {
        $start = Carbon::now()->subYear()->startOfYear();
        $end = Carbon::now()->subYear()->endOfYear();
        
        return [
            'start' => $start,
            'end' => $end,
            'label' => 'Прошлый год',
        ];
    }

    /**
     * Получить начало дня
     */
    public static function getStartOfDay(?CarbonInterface $date = null): Carbon
    {
        $date = $date ?: Carbon::now();
        return $date->copy()->startOfDay();
    }

    /**
     * Получить конец дня
     */
    public static function getEndOfDay(?CarbonInterface $date = null): Carbon
    {
        $date = $date ?: Carbon::now();
        return $date->copy()->endOfDay();
    }

    /**
     * Проверить, находится ли дата в указанном периоде
     */
    public static function isDateInPeriod(CarbonInterface $date, CarbonInterface $start, CarbonInterface $end): bool
    {
        return $date->between($start, $end);
    }

    /**
     * Получить разницу между датами в днях
     */
    public static function getDaysDiff(CarbonInterface $start, CarbonInterface $end): int
    {
        return $start->diffInDays($end);
    }

    /**
     * Добавить дни к дате
     */
    public static function addDays(CarbonInterface $date, int $days): Carbon
    {
        return $date->copy()->addDays($days);
    }

    /**
     * Вычесть дни из даты
     */
    public static function subDays(CarbonInterface $date, int $days): Carbon
    {
        return $date->copy()->subDays($days);
    }

    /**
     * Получить список дат за период
     */
    public static function getDatesArray(CarbonInterface $start, CarbonInterface $end, string $format = 'Y-m-d'): array
    {
        $period = CarbonPeriod::create($start, $end);
        $dates = [];
        
        foreach ($period as $date) {
            $dates[] = $date->format($format);
        }
        
        return $dates;
    }

    /**
     * Парсинг даты из строки с учетом локали
     */
    public static function parseFromString(string $dateString, ?string $timezone = null): Carbon
    {
        $timezone = $timezone ?: self::getUserTimezone();
        return Carbon::parse($dateString, $timezone);
    }

    /**
     * Проверить, является ли период выходными днями
     */
    public static function isWeekendPeriod(CarbonInterface $start, CarbonInterface $end): bool
    {
        $period = CarbonPeriod::create($start, $end);
        
        foreach ($period as $date) {
            if (!$date->isWeekend()) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Получить следующий рабочий день
     */
    public static function getNextBusinessDay(CarbonInterface $date): Carbon
    {
        $nextDay = $date->copy()->addDay();
        
        while (self::isHoliday($nextDay)) {
            $nextDay->addDay();
        }
        
        return $nextDay;
    }

    /**
     * Получить предыдущий рабочий день
     */
    public static function getPreviousBusinessDay(CarbonInterface $date): Carbon
    {
        $prevDay = $date->copy()->subDay();
        
        while (self::isHoliday($prevDay)) {
            $prevDay->subDay();
        }
        
        return $prevDay;
    }

    /**
     * Получить текущий финансовый год
     */
    public static function getCurrentFiscalYear(?CarbonInterface $date = null): int
    {
        $date = $date ?: Carbon::now();
        
        // Финансовый год начинается с 1 апреля
        if ($date->month >= 4) {
            return $date->year;
        }
        
        return $date->year - 1;
    }

    /**
     * Получить начало финансового года
     */
    public static function getStartOfFiscalYear(?CarbonInterface $date = null): Carbon
    {
        $date = $date ?: Carbon::now();
        $fiscalYear = self::getCurrentFiscalYear($date);
        
        return Carbon::create($fiscalYear, 4, 1)->startOfDay();
    }

    /**
     * Получить конец финансового года
     */
    public static function getEndOfFiscalYear(?CarbonInterface $date = null): Carbon
    {
        $date = $date ?: Carbon::now();
        $fiscalYear = self::getCurrentFiscalYear($date);
        
        return Carbon::create($fiscalYear + 1, 3, 31)->endOfDay();
    }

    /**
     * Форматирование периода для UI
     */
    public static function formatPeriodForUI(CarbonInterface $start, CarbonInterface $end): string
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
    public static function isToday(CarbonInterface $date): bool
    {
        return $date->isToday();
    }

    /**
     * Проверить, является ли дата вчерашним днем
     */
    public static function isYesterday(CarbonInterface $date): bool
    {
        return $date->isYesterday();
    }

    /**
     * Проверить, находится ли дата в текущем месяце
     */
    public static function isInCurrentMonth(CarbonInterface $date): bool
    {
        return $date->format('Y-m') === Carbon::now()->format('Y-m');
    }

    /**
     * Получить разницу в рабочих днях (исключая выходные)
     */
    public static function getBusinessDaysCount(CarbonInterface $start, CarbonInterface $end): int
    {
        $period = CarbonPeriod::create($start, $end);
        $businessDays = 0;
        
        foreach ($period as $date) {
            if (!$date->isWeekend() && !self::isHoliday($date)) {
                $businessDays++;
            }
        }
        
        return $businessDays;
    }

    /**
     * Получить начало текущей недели (понедельник)
     */
    public static function getStartOfWeek(?CarbonInterface $date = null): Carbon
    {
        $date = $date ?: Carbon::now();
        return $date->copy()->startOfWeek();
    }

    /**
     * Получить конец текущей недели (воскресенье)
     */
    public static function getEndOfWeek(?CarbonInterface $date = null): Carbon
    {
        $date = $date ?: Carbon::now();
        return $date->copy()->endOfWeek();
    }

    /**
     * Получить квартал для даты
     */
    public static function getQuarter(CarbonInterface $date): int
    {
        return (int) ceil($date->month / 3);
    }

    /**
     * Получить начало квартала
     */
    public static function getStartOfQuarter(CarbonInterface $date): Carbon
    {
        $quarter = self::getQuarter($date);
        $month = ($quarter - 1) * 3 + 1;
        
        return $date->copy()->month($month)->startOfMonth();
    }

    /**
     * Получить конец квартала
     */
    public static function getEndOfQuarter(CarbonInterface $date): Carbon
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
    public static function periodToDaysArray(CarbonInterface $start, CarbonInterface $end): array
    {
        $period = CarbonPeriod::create($start, $end);
        $days = [];
        
        foreach ($period as $date) {
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('d'),
                'weekday' => $date->translatedFormat('D'),
                'is_weekend' => $date->isWeekend(),
                'is_holiday' => self::isHoliday($date),
            ];
        }
        
        return $days;
    }

    /**
     * Получить временные метки для графика
     */
    public static function getChartTimestamps(CarbonInterface $start, CarbonInterface $end, string $groupBy = 'day'): array
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
                    if ($date->dayOfWeek === CarbonInterface::MONDAY) {
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
    public static function isValidSyncPeriod(CarbonInterface $start, CarbonInterface $end): bool
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
    public static function getHumanDiff(CarbonInterface $from, ?CarbonInterface $to = null): string
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
    public static function isHoliday(CarbonInterface $date): bool
    {
        return $date->isWeekend() || in_array($date->format('m-d'), self::HOLIDAYS);
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
    public static function toUserTimezone(CarbonInterface $date, ?string $timezone = null): Carbon
    {
        $timezone = $timezone ?: self::getUserTimezone();
        return $date->copy()->timezone($timezone);
    }

    /**
     * Получить возраст по дате рождения
     */
    public static function getAge(CarbonInterface $birthDate): int
    {
        return $birthDate->age;
    }

    /**
     * Проверить, истекла ли дата
     */
    public static function isExpired(CarbonInterface $date): bool
    {
        return $date->isPast();
    }

    /**
     * Получить оставшееся время до даты
     */
    public static function getTimeLeft(CarbonInterface $date): string
    {
        if ($date->isPast()) {
            return 'Истекло';
        }
        
        return self::getHumanDiff(Carbon::now(), $date);
    }

    /**
     * Получить все предопределенные периоды для UI
     */
    public static function getPredefinedPeriods(): array
    {
        return [
            'today' => self::getTodayPeriod(),
            'yesterday' => self::getYesterdayPeriod(),
            'current_week' => self::getCurrentWeekPeriod(),
            'last_week' => self::getLastWeekPeriod(),
            'current_month' => self::getCurrentMonthPeriod(),
            'last_month' => self::getLastMonthPeriod(),
            'current_year' => self::getCurrentYearPeriod(),
            'last_year' => self::getLastYearPeriod(),
            'last_7_days' => self::getLastDaysPeriod(7),
            'last_30_days' => self::getLastDaysPeriod(30),
            'last_90_days' => self::getLastDaysPeriod(90),
        ];
    }
}