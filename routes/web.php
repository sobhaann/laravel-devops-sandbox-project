<?php

use App\Http\Controllers\ConversionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ConversionController::class, 'index'])
    ->name('conversions.index');

Route::post('/convert', [ConversionController::class, 'store'])
    ->name('conversions.store');

Route::get('/convert', fn () => redirect()->route('conversions.index'));