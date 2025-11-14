<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API endpoints для фронтенда
Route::prefix('api')->group(function () {
    // Отчеты
    Route::get('/report/{id}', [ReportController::class, 'getReport']);
    Route::get('/statistics', [ReportController::class, 'getStatistics']);
    Route::get('/visits', [ReportController::class, 'getVisits']);
    Route::get('/sources', [ReportController::class, 'getSources']);
    Route::get('/age-data', [ReportController::class, 'getAgeData']);
    Route::get('/projects-thermometer', [ReportController::class, 'getProjectsWithThermometer']);
});

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});