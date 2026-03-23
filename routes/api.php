<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/assign-role', [AuthController::class, 'assignRole']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Task routes with Spatie permission middleware
    Route::get('/tasks',          [TaskController::class, 'index'])->middleware('permission:view tasks');
    Route::post('/tasks',         [TaskController::class, 'store'])->middleware('permission:create tasks');
    Route::get('/tasks/{task}',   [TaskController::class, 'show'])->middleware('permission:view tasks');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->middleware('permission:edit tasks');
    Route::put('/tasks/{task}',   [TaskController::class, 'update'])->middleware('permission:edit tasks');
    Route::delete('/tasks/{task}',[TaskController::class, 'destroy'])->middleware('permission:delete tasks');
});