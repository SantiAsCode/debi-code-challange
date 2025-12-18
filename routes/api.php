<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/scrape', [\App\Http\Controllers\ContentController::class, 'update'])->name('scrape.update');