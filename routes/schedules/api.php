<?php

use App\Http\Controllers\Schedules\ORSchedulesController;
use Illuminate\Support\Facades\Route;


/*
Route::controller(ORSchedulesController::class)->group(function () {
    Route::get('get-schedules', 'index');
});*/
/*
Route::get('/schedules', [ORSchedulesController::class, 'index']);
*/

Route::controller(ORSchedulesController::class)->group(function () {
    Route::get('get-schedules', 'index');
});
