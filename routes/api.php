<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\CadetController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReportController; // ✅ ADD THIS

// Public routes (no authentication needed)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Attendance routes
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance/stats', [AttendanceController::class, 'stats']);
    Route::get('/attendance/recent', [AttendanceController::class, 'recent']);
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);

    // History routes
    Route::get('/history', [HistoryController::class, 'index']);
    Route::get('/history/date', [HistoryController::class, 'getDateDetails']);
    Route::get('/history/download', [HistoryController::class, 'download']);
    Route::post('/history/save', [HistoryController::class, 'saveToHistory']);

    // Report routes - ✅ ADD THESE
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/students', [ReportController::class, 'studentsByStatus']);
    Route::get('/reports/download', [ReportController::class, 'download']);

    // Cadet routes
    Route::get('/cadets', [CadetController::class, 'index']);
    Route::get('/cadets/count', [CadetController::class, 'count']);
    Route::post('/cadets', [CadetController::class, 'store']);
    Route::get('/cadets/{id}', [CadetController::class, 'show']);
    Route::put('/cadets/{id}', [CadetController::class, 'update']);
    Route::delete('/cadets/{id}', [CadetController::class, 'destroy']);
});
