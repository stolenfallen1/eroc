<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Biometric\BiometricsController;

Route::controller(BiometricsController::class)->group(function () {
    Route::post('save-biometric-transaction', 'store');
});