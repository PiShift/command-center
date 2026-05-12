<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WarRoomController;
use App\Http\Controllers\TaskController;

Route::middleware(['auth', 'war-room'])->group(function () {
    Route::get('/war-room', [WarRoomController::class, 'index'])->name('war-room');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
});
