<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/scrape', [\App\Http\Controllers\Api\ContentController::class, 'update'])->name('scrape.update');

Route::prefix('dashboard')->group(function () {
    Route::get('/stats', [\App\Http\Controllers\Api\DashboardController::class, 'stats']);
    Route::get('/search', [\App\Http\Controllers\Api\DashboardController::class, 'search']);
});
