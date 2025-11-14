<?php

namespace App\Helpers;

use InvalidArgumentException;

class MathHelper
{
    /**
     * Безопасное деление с обработкой нуля
     */
    public static function safeDivide(float $numerator, float $denominator, float $default = 0.0): float
    {
        if ($denominator == 0) {
            return $default;
        }
        
        return $numerator / $denominator;
    }

    /**
     * Расчет процента изменения между двумя значениями
     */
    public static function calculateGrowthRate(float $current, float $previous, int $precision = 2): ?float
    {
        if ($previous == 0) {
            return null;
        }
        
        $growth = (($current - $previous) / abs($previous)) * 100;
        
        return round($growth, $precision);
    }

    /**
     * Расчет ROI (Return on Investment)
     */
    public static function calculateROI(float $revenue, float $cost, int $precision = 2): ?float
    {
        if ($cost == 0) {
            return null;
        }
        
        $roi = (($revenue - $cost) / $cost) * 100;
        
        return round($roi, $precision);
    }

    /**
     * Расчет CR (Conversion Rate)
     */
    public static function calculateConversionRate(int $conversions, int $sessions, int $precision = 2): float
    {
        return round(self::safeDivide($conversions, $sessions) * 100, $precision);
    }

    /**
     * Расчет среднего значения (Average)
     */
    public static function calculateAverage(float $total, int $count, int $precision = 2): ?float
    {
        if ($count == 0) {
            return null;
        }
        
        return round($total / $count, $precision);
    }

    /**
     * Нормализация значения в диапазон [0, 1]
     */
    public static function normalize(float $value, float $min, float $max): float
    {
        if ($max - $min == 0) {
            return 0;
        }
        
        return ($value - $min) / ($max - $min);
    }

    /**
     * Денормализация значения из диапазона [0, 1]
     */
    public static function denormalize(float $normalized, float $min, float $max): float
    {
        return $min + ($normalized * ($max - $min));
    }

    /**
     * Округление до значимых цифр (для больших чисел)
     */
    public static function roundSignificant(float $number, int $significantDigits = 3): float
    {
        if ($number == 0) {
            return 0;
        }
        
        $magnitude = floor(log10(abs($number)));
        $scale = pow(10, $significantDigits - $magnitude - 1);
        
        return round($number * $scale) / $scale;
    }

    /**
     * Форматирование больших чисел (тысячи, миллионы)
     */
    public static function formatLargeNumber(float $number, int $decimals = 1): string
    {
        $absNumber = abs($number);
        
        if ($absNumber >= 1000000) {
            return round($number / 1000000, $decimals) . 'M';
        } elseif ($absNumber >= 1000) {
            return round($number / 1000, $decimals) . 'K';
        }
        
        return (string) round($number, $decimals);
    }

    /**
     * Расчет процента от общего
     */
    public static function calculatePercentage(float $part, float $total, int $precision = 2): float
    {
        return round(self::safeDivide($part, $total) * 100, $precision);
    }

    /**
     * Линейная интерполяция
     */
    public static function lerp(float $a, float $b, float $t): float
    {
        return $a + ($b - $a) * $t;
    }

    /**
     * Ограничение значения в диапазон
     */
    public static function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * Расчет CAGR (Compound Annual Growth Rate)
     */
    public static function calculateCAGR(float $beginningValue, float $endingValue, int $years): ?float
    {
        if ($beginningValue <= 0 || $years <= 0) {
            return null;
        }
        
        $cagr = pow(($endingValue / $beginningValue), (1 / $years)) - 1;
        
        return round($cagr * 100, 2);
    }

    /**
     * Расчет CPA (Cost Per Action)
     */
    public static function calculateCPA(float $cost, int $conversions): ?float
    {
        if ($conversions == 0) {
            return null;
        }
        
        return round($cost / $conversions, 2);
    }

    /**
     * Расчет CPC (Cost Per Click)
     */
    public static function calculateCPC(float $cost, int $clicks): ?float
    {
        return self::calculateCPA($cost, $clicks);
    }

    /**
     * Расчет CPM (Cost Per Mille)
     */
    public static function calculateCPM(float $cost, int $impressions): ?float
    {
        if ($impressions == 0) {
            return null;
        }
        
        return round(($cost / $impressions) * 1000, 2);
    }

    /**
     * Расчет CTR (Click Through Rate)
     */
    public static function calculateCTR(int $clicks, int $impressions, int $precision = 2): float
    {
        return self::calculateConversionRate($clicks, $impressions, $precision);
    }

    /**
     * Стандартное отклонение
     */
    public static function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        
        if ($count < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / $count;
        $variance = 0.0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return sqrt($variance / ($count - 1));
    }

    /**
     * Медиана
     */
    public static function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0;
        }
        
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        } else {
            return $values[$middle];
        }
    }

    /**
     * Процентиль
     */
    public static function calculatePercentile(array $values, float $percentile): float
    {
        if (empty($values)) {
            return 0;
        }
        
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if (floor($index) == $index) {
            return $values[$index];
        }
        
        $lower = $values[floor($index)];
        $upper = $values[ceil($index)];
        
        return self::lerp($lower, $upper, $index - floor($index));
    }

    /**
     * Z-score нормализация
     */
    public static function calculateZScore(float $value, float $mean, float $stdDev): float
    {
        if ($stdDev == 0) {
            return 0;
        }
        
        return ($value - $mean) / $stdDev;
    }

    /**
     * Проверка на аномалию с помощью IQR метода
     */
    public static function isAnomalyByIQR(float $value, array $dataset, float $threshold = 1.5): bool
    {
        if (count($dataset) < 4) {
            return false;
        }
        
        $q1 = self::calculatePercentile($dataset, 25);
        $q3 = self::calculatePercentile($dataset, 75);
        $iqr = $q3 - $q1;
        
        $lowerBound = $q1 - ($threshold * $iqr);
        $upperBound = $q3 + ($threshold * $iqr);
        
        return $value < $lowerBound || $value > $upperBound;
    }

    /**
     * Сумма массива с проверкой
     */
    public static function safeSum(array $values): float
    {
        return array_sum(array_filter($values, 'is_numeric'));
    }

    /**
     * Среднее массива с проверкой
     */
    public static function safeAverage(array $values, int $precision = 2): ?float
    {
        $numericValues = array_filter($values, 'is_numeric');
        
        if (empty($numericValues)) {
            return null;
        }
        
        return round(array_sum($numericValues) / count($numericValues), $precision);
    }
}