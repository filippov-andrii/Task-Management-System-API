<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('tasks', TaskController::class)->names([
        'index' => 'v1.tasks.index',
        'show' => 'v1.tasks.show',
        'store' => 'v1.tasks.store',
        'update' => 'v1.tasks.update',
        'destroy' => 'v1.tasks.destroy',
    ]);
});
