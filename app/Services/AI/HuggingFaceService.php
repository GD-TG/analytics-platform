<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HuggingFaceService
{
    private string $apiKey;
    private string $apiUrl;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('huggingface.api_key');
        $this->apiUrl = config('huggingface.api_url');
        $this->timeout = config('huggingface.timeout', 30);
    }

    /**
     * Analyze project metrics and generate business insights (pulse)
     */
    public function analyzeBusPulse(array $metrics): array
    {
        try {
            $prompt = $this->buildBusPulsePrompt($metrics);
            $result = $this->queryModel('text_generation', $prompt);

            return [
                'success' => true,
                'pulse' => $this->parsePulse($result),
                'insight' => $this->generateInsight($metrics),
            ];
        } catch (\Exception $e) {
            Log::error('HuggingFace AI Error (Pulse):', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Failed to analyze business pulse',
            ];
        }
    }

    /**
     * Analyze traffic sources for pie chart data
     */
    public function analyzeSourcePie(array $sources): array
    {
        try {
            $categorized = $this->categorizeTrafficSources($sources);

            return [
                'success' => true,
                'data' => $categorized,
            ];
        } catch (\Exception $e) {
            Log::error('HuggingFace AI Error (Sources):', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Failed to analyze traffic sources',
            ];
        }
    }

    /**
     * Compare metrics between periods (hover data)
     */
    public function compareMetrics(array $current, array $previous): array
    {
        try {
            $comparison = [
                'growth' => [],
                'decline' => [],
                'stable' => [],
            ];

            foreach ($current as $key => $value) {
                if (!isset($previous[$key])) {
                    continue;
                }

                $change = $this->calculateChange($previous[$key], $value);

                if ($change > 5) {
                    $comparison['growth'][$key] = $change;
                } elseif ($change < -5) {
                    $comparison['decline'][$key] = $change;
                } else {
                    $comparison['stable'][$key] = $change;
                }
            }

            return [
                'success' => true,
                'data' => $comparison,
            ];
        } catch (\Exception $e) {
            Log::error('HuggingFace AI Error (Compare):', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Failed to compare metrics',
            ];
        }
    }

    /**
     * Generate thermometer status for project health
     */
    public function generateThermometer(array $metrics): string
    {
        try {
            // Analyze key metrics to determine health status
            $health = $this->assessProjectHealth($metrics);

            if ($health > 0.7) {
                return '🔥'; // Hot - project growing
            } elseif ($health > 0.3) {
                return '🌤'; // Stable
            } else {
                return '❄'; // Cold - project declining
            }
        } catch (\Exception $e) {
            Log::error('HuggingFace AI Error (Thermometer):', ['error' => $e->getMessage()]);
            return '🌤'; // Default to stable
        }
    }

    /**
     * Generate activity heatmap suggestions
     */
    public function generateActivityHeatmap(array $dailyData): array
    {
        try {
            $heatmap = [];

            foreach ($dailyData as $day => $metrics) {
                $intensity = $this->calculateIntensity($metrics);
                $heatmap[$day] = [
                    'intensity' => $intensity,
                    'suggestion' => $this->getSuggestionByIntensity($intensity),
                ];
            }

            return [
                'success' => true,
                'data' => $heatmap,
            ];
        } catch (\Exception $e) {
            Log::error('HuggingFace AI Error (Heatmap):', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Failed to generate activity heatmap',
            ];
        }
    }

    /**
     * Query HuggingFace API
     */
    private function queryModel(string $modelType, string $input): string
    {
        try {
            $model = config('huggingface.models.' . $modelType);

            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post("{$this->apiUrl}/{$model}", [
                    'inputs' => $input,
                ])
                ->throw();

            if ($response->successful()) {
                return $response->body();
            }

            throw new \Exception('API request failed');
        } catch (\Exception $e) {
            Log::error('HuggingFace Query Error:', ['error' => $e->getMessage()]);
            return '';
        }
    }

    // Helper methods

    private function buildBusPulsePrompt(array $metrics): string
    {
        return "Analyze the following metrics and provide a brief business insight: " .
            json_encode($metrics);
    }

    private function parsePulse(string $result): string
    {
        // Parse AI response to extract pulse insight
        return trim($result) ?: 'No insight available';
    }

    private function generateInsight(array $metrics): string
    {
        // Rule-based insight generation as fallback
        $visits = $metrics['visits'] ?? 0;
        $conversions = $metrics['conversions'] ?? 0;
        $bounce = $metrics['bounce_rate'] ?? 0;

        if ($bounce > 60) {
            return 'High bounce rate detected. Consider improving page content or user experience.';
        } elseif ($conversions > 0 && $visits > 100) {
            return 'Good conversion activity. Keep monitoring trends.';
        }

        return 'Project is performing as expected.';
    }

    private function categorizeTrafficSources(array $sources): array
    {
        $categories = [
            'organic' => 0,
            'direct' => 0,
            'referral' => 0,
            'paid' => 0,
            'social' => 0,
        ];

        foreach ($sources as $source) {
            if (stripos($source['name'], 'google') !== false || stripos($source['name'], 'organic') !== false) {
                $categories['organic'] += $source['visits'] ?? 0;
            } elseif (stripos($source['name'], 'direct') !== false) {
                $categories['direct'] += $source['visits'] ?? 0;
            } elseif (stripos($source['name'], 'social') !== false) {
                $categories['social'] += $source['visits'] ?? 0;
            } elseif (stripos($source['name'], 'yandex') !== false || stripos($source['name'], 'ads') !== false) {
                $categories['paid'] += $source['visits'] ?? 0;
            } else {
                $categories['referral'] += $source['visits'] ?? 0;
            }
        }

        return $categories;
    }

    private function calculateChange(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function assessProjectHealth(array $metrics): float
    {
        $score = 0.5; // Base score

        if (($metrics['visits'] ?? 0) > 1000) {
            $score += 0.2;
        }

        if (($metrics['conversions'] ?? 0) > 0) {
            $score += 0.15;
        }

        if (($metrics['bounce_rate'] ?? 0) < 50) {
            $score += 0.15;
        }

        return min($score, 1.0);
    }

    private function calculateIntensity(array $metrics): string
    {
        $visits = $metrics['visits'] ?? 0;

        if ($visits > 500) {
            return 'very-high';
        } elseif ($visits > 200) {
            return 'high';
        } elseif ($visits > 50) {
            return 'medium';
        } elseif ($visits > 0) {
            return 'low';
        }

        return 'none';
    }

    private function getSuggestionByIntensity(string $intensity): string
    {
        return match ($intensity) {
            'very-high' => 'Peak activity - monitor conversion funnel closely',
            'high' => 'Good traffic - ensure server capacity',
            'medium' => 'Normal activity - routine monitoring',
            'low' => 'Low activity - consider promotional activities',
            'none' => 'No activity - check tracking setup',
            default => 'Monitor activity',
        };
    }
}
