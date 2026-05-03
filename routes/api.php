<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\NotificationController;

Route::get('/health-check', HealthController::class);
Route::get('/metrics', MetricsController::class);

Route::post('/notifications/batch/{batchId}/cancel', [NotificationController::class, 'cancelBatch']);
Route::get('/notifications/batch/{batchId}', [NotificationController::class, 'showByBatch']);
Route::post('/notifications/{id}/cancel', [NotificationController::class, 'cancel']);
Route::get('/notifications/{id}', [NotificationController::class, 'show']);

Route::post('/notifications', [NotificationController::class, 'store']);
Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications/batch', [NotificationController::class, 'batch']);
