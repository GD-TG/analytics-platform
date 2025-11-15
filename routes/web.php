<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ReportController;

// Главная страница
Route::get('/', function () {
    return view('welcome');
});

// API маршруты для аналитики
Route::prefix('api')->group(function () {
    // Проекты
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    
    // Кампании
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/{id}', [CampaignController::class, 'show']);
    
    // Отчеты
    Route::get('/reports/daily', [ReportController::class, 'daily']);
    Route::get('/reports/monthly', [ReportController::class, 'monthly']);
    Route::get('/reports/period', [ReportController::class, 'period']);
});

// Dashboard маршруты
Route::get('/dashboard', function () {
    return 'Analytics Dashboard';
})->name('dashboard');

// Health check (публичный маршрут, без middleware)
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()], 200, [
        'Content-Type' => 'application/json',
    ]);
})->withoutMiddleware(['web']);

// Fallback маршрут
Route::fallback(function () {
    return response()->json(['error' => 'Route not found'], 404);
});