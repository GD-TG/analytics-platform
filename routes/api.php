<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Публичные API endpoints для аналитики
Route::prefix('v1')->group(function () {
    // Данные метрик
    Route::get('/metrics/daily', [ReportController::class, 'dailyMetrics']);
    Route::get('/metrics/monthly', [ReportController::class, 'monthlyMetrics']);
    Route::get('/metrics/range', [ReportController::class, 'metricsRange']);
    
    // Данные директа
    Route::get('/direct/campaigns', [ReportController::class, 'directCampaigns']);
    Route::get('/direct/summary', [ReportController::class, 'directSummary']);
    
    // SEO данные
    Route::get('/seo/queries', [ReportController::class, 'seoQueries']);
    
    // Интеграции
    Route::post('/integrations/metrika/sync', [ReportController::class, 'syncMetrika']);
    Route::post('/integrations/direct/sync', [ReportController::class, 'syncDirect']);
});

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});