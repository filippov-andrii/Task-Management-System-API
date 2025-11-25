<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('tasks/by-user/{userId}', [TaskController::class, 'tasksByUser'])
            ->name('v1.tasks.byUser');
        Route::get('tasks/by-project/{projectId}', [TaskController::class, 'tasksByProject'])
            ->name('v1.tasks.byProject');
        Route::get('tasks/overdue', [TaskController::class, 'overdueTasks'])
            ->name('v1.tasks.overdue');

        Route::apiResource('tasks', TaskController::class)->names([
            'index' => 'v1.tasks.index',
            'show' => 'v1.tasks.show',
            'store' => 'v1.tasks.store',
            'update' => 'v1.tasks.update',
            'destroy' => 'v1.tasks.destroy',
        ]);

        Route::apiResource('projects', ProjectController::class)->names([
            'index' => 'v1.projects.index',
            'show' => 'v1.projects.show',
            'store' => 'v1.projects.store',
            'update' => 'v1.projects.update',
            'destroy' => 'v1.projects.destroy',
        ]);
        Route::get('projects/{project}/tasks', [ProjectController::class, 'tasks'])
            ->name('v1.projects.tasks');
    });

    Route::post('register', [AuthController::class, 'register'])->name('v1.auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('v1.auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [AuthController::class, 'profile'])->name('v1.auth.profile');
        Route::post('logout', [AuthController::class, 'logout'])->name('v1.auth.logout');
    });
});
