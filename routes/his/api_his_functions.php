<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HIS\his_functions\HISPostChargesController;


Route::controller(HISPostChargesController::class)->group(function () {
    Route::post('get-his-charges', 'chargehistory');
    Route::post('post-his-charge', 'charge');
}); 