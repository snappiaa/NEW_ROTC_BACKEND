<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\CadetController;
use App\Http\Controllers\Api\AttendanceHistoryController;
use App\Http\Controllers\Api\UserController;


// Public routes (no authentication needed)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Attendance routes
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);

    // Cadet routes
    Route::get('/cadets', [CadetController::class, 'index']);
    Route::get('/cadets/count', [CadetController::class, 'count']);
    Route::post('/cadets', [CadetController::class, 'store']);
    Route::get('/cadets/{id}', [CadetController::class, 'show']);
    Route::put('/cadets/{id}', [CadetController::class, 'update']);
    Route::delete('/cadets/{id}', [CadetController::class, 'destroy']);
});
