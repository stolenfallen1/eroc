<?php

use App\Http\Controllers\POS\PosController;
use Illuminate\Support\Facades\Route;


// get all items 

Route::controller(PosController::class)->group(function () {
    Route::post('get-all-items', 'index');
    Route::get('get-all-category', 'getCategory');
  });