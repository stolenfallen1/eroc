<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Biometric\BiometricsController;

Route::controller(BiometricsController::class)->group(function () {
    Route::get('get-biometric-transaction', 'index');
    Route::post('save-biometric-transaction', 'store');
});