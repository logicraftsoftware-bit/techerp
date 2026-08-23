<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\JobsController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SalaryController;
use App\Http\Middleware\RunMissedCheckInMarker;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Technicians interact with the CRM almost entirely through this API now, so the
// missed-check-in sweep (otherwise only triggered by web traffic) is re-attached here too.
Route::middleware(['auth:sanctum', 'technician.active', RunMissedCheckInMarker::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);

    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);

    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::post('/leaves', [LeaveController::class, 'store']);
    Route::put('/leaves/{leave}', [LeaveController::class, 'update']);
    Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy']);

    Route::get('/salary', [SalaryController::class, 'index']);
    Route::get('/salary/{salary}', [SalaryController::class, 'show']);

    Route::get('/jobs', [JobsController::class, 'index']);
    Route::get('/jobs/{assignment}', [JobsController::class, 'show']);
    Route::patch('/jobs/{assignment}/status', [JobsController::class, 'updateStatus']);
    Route::post('/jobs/{assignment}/parts', [JobsController::class, 'storeParts']);
    Route::get('/jobs/{assignment}/job-card', [JobsController::class, 'jobCard']);

    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

    Route::get('/holidays', [HolidayController::class, 'index']);
});
