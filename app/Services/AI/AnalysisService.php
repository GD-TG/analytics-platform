<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Helpers\PeriodHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalysisService
{
    /**
     * API URL для Hugging Face Inference API
     * Используем модель T5-small для анализа данных (около 240MB)
     * или можно использовать через API без локальной установки
     */
    private const HF_API_URL = 'https://api-inference.huggingface.co/models';
    
    /**
     * Модель для анализа данных
     * t5-small - легкая модель для текстового анализа (240MB)
     * Можно заменить на другие модели при необходимости
     */
    private const MODEL_NAME = 't5-small';
    
    /**
     * Анализировать данные проекта и сгенерировать отчет
     */
    public function analyzeProject(Project $project, array $periodData): array
    {
        try {
            // Получаем данные проекта
            $metrics = $this->getProjectMetrics($project, $periodData);
            
            // Формируем промпт для анализа
            $prompt = $this->buildAnalysisPrompt($metrics);
            
            // Генерируем анализ через AI
            $analysis = $this->generateAnalysis($prompt);
            
            // Форматируем результат
            return $this->formatAnalysis($analysis, $metrics);
            
        } catch (\Exception $e) {
            Log::error('AI Analysis failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback на базовый анализ без AI
            return $this->generateBasicAnalysis($project, $periodData);
        }
    }
    
    /**
     * Получить метрики проекта
     */
    private function getProjectMetrics(Project $project, array $periodData): array
    {
        $year = $periodData['start']->year;
        $month = $periodData['start']->month;
        
        $metrics = \App\Models\MetricsMonthly::where('project_id', $project->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
        
        if (!$metrics) {
            return [
                'visits' => 0,
                'conversions' => 0,
                'bounce_rate' => 0,
                'avg_duration' => 0,
            ];
        }
        
        return [
            'visits' => $metrics->visits ?? 0,
            'conversions' => $metrics->conversions ?? 0,
            'bounce_rate' => (float)($metrics->bounce_rate ?? 0),
            'avg_duration' => $metrics->avg_session_duration_sec ?? 0,
        ];
    }
    
    /**
     * Построить промпт для анализа
     */
    private function buildAnalysisPrompt(array $metrics): string
    {
        return sprintf(
            "Проанализируй следующие метрики веб-сайта и дай краткий отчет:\n" .
            "Визиты: %d\n" .
            "Конверсии: %d\n" .
            "Процент отказов: %.2f%%\n" .
            "Среднее время на сайте: %d сек\n\n" .
            "Дай краткий анализ (2-3 предложения) с выводами и рекомендациями.",
            $metrics['visits'],
            $metrics['conversions'],
            $metrics['bounce_rate'],
            $metrics['avg_duration']
        );
    }
    
    /**
     * Генерировать анализ через AI
     */
    private function generateAnalysis(string $prompt): string
    {
        try {
            // Используем Hugging Face Inference API
            // Для продакшена лучше использовать локальную модель или свой сервер
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('HUGGINGFACE_API_KEY', ''),
                ])
                ->post(self::HF_API_URL . '/' . self::MODEL_NAME, [
                    'inputs' => $prompt,
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data[0]['generated_text'] ?? '';
            }
            
            throw new \Exception('AI API request failed');
            
        } catch (\Exception $e) {
            Log::warning('AI API call failed, using fallback', [
                'error' => $e->getMessage(),
            ]);
            
            // Fallback на простое правило-основанное решение
            return $this->generateRuleBasedAnalysis($prompt);
        }
    }
    
    /**
     * Генерация анализа на основе правил (fallback)
     */
    private function generateRuleBasedAnalysis(string $prompt): string
    {
        // Простой анализ на основе правил
        // В реальном проекте здесь будет более сложная логика
        
        if (strpos($prompt, 'Процент отказов: 3') !== false) {
            return "Высокий процент отказов указывает на проблемы с контентом или пользовательским опытом. " .
                   "Рекомендуется улучшить релевантность контента и оптимизировать скорость загрузки страниц.";
        }
        
        return "Анализ данных показывает стабильные показатели. " .
               "Рекомендуется продолжить текущую стратегию и мониторить ключевые метрики.";
    }
    
    /**
     * Форматировать анализ
     */
    private function formatAnalysis(string $analysis, array $metrics): array
    {
        return [
            'summary' => $analysis,
            'metrics' => $metrics,
            'recommendations' => $this->extractRecommendations($analysis),
            'insights' => $this->generateInsights($metrics),
        ];
    }
    
    /**
     * Извлечь рекомендации из анализа
     */
    private function extractRecommendations(string $analysis): array
    {
        // Простое извлечение рекомендаций
        // В реальном проекте можно использовать NLP для более точного извлечения
        
        $recommendations = [];
        
        if (stripos($analysis, 'отказы') !== false) {
            $recommendations[] = 'Улучшить контент и пользовательский опыт';
        }
        
        if (stripos($analysis, 'конверсии') !== false) {
            $recommendations[] = 'Оптимизировать воронку конверсий';
        }
        
        return $recommendations;
    }
    
    /**
     * Генерировать инсайты
     */
    private function generateInsights(array $metrics): array
    {
        $insights = [];
        
        $conversionRate = $metrics['visits'] > 0 
            ? ($metrics['conversions'] / $metrics['visits']) * 100 
            : 0;
        
        if ($conversionRate < 2) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Низкий коэффициент конверсии. Рекомендуется оптимизация.',
            ];
        }
        
        if ($metrics['bounce_rate'] > 50) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Высокий процент отказов. Необходимо улучшить релевантность контента.',
            ];
        }
        
        return $insights;
    }
    
    /**
     * Базовый анализ без AI (fallback)
     */
    private function generateBasicAnalysis(Project $project, array $periodData): array
    {
        return [
            'summary' => 'Анализ данных проекта выполнен. Рекомендуется проверить ключевые метрики.',
            'metrics' => [],
            'recommendations' => [
                'Мониторить ключевые показатели',
                'Оптимизировать конверсии',
            ],
            'insights' => [],
        ];
    }
}

