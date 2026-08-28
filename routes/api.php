<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('students/{id}/restore', [StudentController::class, 'restore']);
    Route::delete('students/{id}/force', [StudentController::class, 'forceDestroy']);
    Route::get('students/statistics', [StudentController::class, 'statistics']);
    Route::apiResource('students', StudentController::class);
});