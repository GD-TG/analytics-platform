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
    Route::get('/auth/yandex/url', [\App\Http\Controllers\Auth\AuthController::class, 'getYandexAuthUrl']);
    
    // Yandex OAuth (для API токенов)
    Route::get('/yandex/auth-url', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'getAuthUrl']);
    Route::post('/yandex/exchange-code', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'exchangeCode']);
    Route::get('/yandex/validate-token', [\App\Http\Controllers\Yandex\YandexAuthController::class, 'validateToken']);
});