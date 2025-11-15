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
    Route::post('/auth/register', [\App\Http\Controllers\Auth\AuthController::class, 'register']);
    Route::post('/auth/login', [\App\Http\Controllers\Auth\AuthController::class, 'login']);
    Route::post('/auth/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/me', [\App\Http\Controllers\Auth\AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('/auth/yandex', [\App\Http\Controllers\Auth\AuthController::class, 'yandexAuth']);
    Route::post('/auth/yandex/callback', [\App\Http\Controllers\Auth\AuthController::class, 'yandexCallback']);
    Route::get('/auth/yandex/url', [\App\Http\Controllers\Auth\AuthController::class, 'getYandexAuthUrl']);

    // Yandex OAuth (для API токенов)
    Route::get('/yandex/auth-url', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'getAuthUrl']);
    Route::post('/yandex/exchange-code', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'exchangeCode']);
    Route::get('/yandex/validate-token', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'validateToken']);

    // New per-user OAuth endpoints (controller in App\Http\Controllers\Yandex\YandexAuthController)
    Route::get('/yandex/auth-url-new', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'authUrl']);

    // Protected OAuth endpoints (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/yandex/exchange-code-new', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'exchangeCode']);
        Route::get('/yandex/validate-token-new', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'validateToken']);
        Route::get('/yandex/counters', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'listCounters']);
        Route::post('/yandex/counters/save', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'saveCounters']);

        // Dashboard endpoints
        Route::get('/dashboard/sync-status', [\App\Http\Controllers\DashboardController::class, 'getSyncStatus']);
        Route::get('/dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'getStats']);
        Route::get('/dashboard/recent-syncs', [\App\Http\Controllers\DashboardController::class, 'getRecentSyncs']);

        // Settings endpoints
        Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'getSettings']);
        Route::post('/settings/yandex-metrika', [\App\Http\Controllers\SettingsController::class, 'updateYandexMetrika']);
        Route::post('/settings/yandex-direct', [\App\Http\Controllers\SettingsController::class, 'updateYandexDirect']);
        Route::post('/settings/sync', [\App\Http\Controllers\SettingsController::class, 'updateSyncSettings']);
        Route::post('/settings/test/yandex-metrika', [\App\Http\Controllers\SettingsController::class, 'testYandexMetrika']);
        Route::post('/settings/test/yandex-direct', [\App\Http\Controllers\SettingsController::class, 'testYandexDirect']);

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
});
