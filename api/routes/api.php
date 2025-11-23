<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('tasks', TaskController::class)->names([
        'index' => 'v1.tasks.index',
        'show' => 'v1.tasks.show',
        'store' => 'v1.tasks.store',
        'update' => 'v1.tasks.update',
        'destroy' => 'v1.tasks.destroy',
    ]);

    Route::post('register', [AuthController::class, 'register'])->name('v1.auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('v1.auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [AuthController::class, 'profile'])->name('v1.auth.profile');
        Route::post('logout', [AuthController::class, 'logout'])->name('v1.auth.logout');
    });
});
