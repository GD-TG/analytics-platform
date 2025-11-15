<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

// API endpoints для фронтенда (публичные, без авторизации)
Route::prefix('api')->group(function () {
    // Отчеты
    Route::get('/report/{id}', [ReportController::class, 'getReport']);
    Route::get('/statistics', [ReportController::class, 'getStatistics']);
    Route::get('/visits', [ReportController::class, 'getVisits']);
    Route::get('/sources', [ReportController::class, 'getSources']);
    Route::get('/age-data', [ReportController::class, 'getAgeData']);
    Route::get('/projects-thermometer', [ReportController::class, 'getProjectsWithThermometer']);

    // AI анализ
    Route::get('/ai/analyze/{project}', [\App\Http\Controllers\AI\AnalysisController::class, 'analyzeProject']);

    // Авторизация
    // ...existing code...
    // (auth и yandex OAuth маршруты удалены)
    // ...existing code...

    // Projects API (CRUD)
    Route::get('/projects', [\App\Http\Controllers\Api\ProjectController::class, 'index']);
    Route::post('/projects', [\App\Http\Controllers\Api\ProjectController::class, 'store']);
    Route::get('/projects/{id}', [\App\Http\Controllers\Api\ProjectController::class, 'show']);
    Route::put('/projects/{id}', [\App\Http\Controllers\Api\ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [\App\Http\Controllers\Api\ProjectController::class, 'destroy']);

    // Counters API
    Route::get('/projects/{projectId}/counters', [\App\Http\Controllers\Api\CounterController::class, 'index']);
    Route::post('/projects/{projectId}/counters', [\App\Http\Controllers\Api\CounterController::class, 'store']);
    Route::delete('/projects/{projectId}/counters/{counterId}', [\App\Http\Controllers\Api\CounterController::class, 'destroy']);

    // Direct Accounts API
    Route::get('/projects/{projectId}/direct-accounts', [\App\Http\Controllers\Api\DirectAccountController::class, 'index']);
    Route::post('/projects/{projectId}/direct-accounts', [\App\Http\Controllers\Api\DirectAccountController::class, 'store']);
    Route::delete('/projects/{projectId}/direct-accounts/{accountId}', [\App\Http\Controllers\Api\DirectAccountController::class, 'destroy']);

    // Goals API
    Route::get('/projects/{projectId}/goals', [\App\Http\Controllers\Api\GoalController::class, 'index']);
    Route::post('/projects/{projectId}/goals', [\App\Http\Controllers\Api\GoalController::class, 'store']);
    Route::put('/projects/{projectId}/goals/{goalId}', [\App\Http\Controllers\Api\GoalController::class, 'update']);
    Route::delete('/projects/{projectId}/goals/{goalId}', [\App\Http\Controllers\Api\GoalController::class, 'destroy']);

    // Sync API
    Route::post('/projects/{projectId}/sync', [\App\Http\Controllers\Api\SyncController::class, 'trigger']);
    Route::get('/projects/{projectId}/sync/status', [\App\Http\Controllers\Api\SyncController::class, 'status']);

    // Report API (3-month contract)
    Route::get('/projects/{projectId}/report', [\App\Http\Controllers\Api\ReportApiController::class, 'show']);

    // AI Analytics API
    Route::get('/projects/{projectId}/ai/pulse', [\App\Http\Controllers\Api\AnalyticsAIController::class, 'busPulse']);
    Route::get('/projects/{projectId}/ai/sources-pie', [\App\Http\Controllers\Api\AnalyticsAIController::class, 'sourcePie']);
    Route::post('/projects/{projectId}/ai/compare', [\App\Http\Controllers\Api\AnalyticsAIController::class, 'compareMetrics']);
    Route::get('/projects/{projectId}/ai/thermometer', [\App\Http\Controllers\Api\AnalyticsAIController::class, 'thermometer']);
    Route::get('/projects/{projectId}/ai/heatmap', [\App\Http\Controllers\Api\AnalyticsAIController::class, 'activityHeatmap']);
});
