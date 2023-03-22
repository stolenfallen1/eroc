<?php

use App\Http\Controllers\POS\PosController;
use App\Http\Controllers\POS\CustomersController;
use Illuminate\Support\Facades\Route;


// get all items 

Route::controller(PosController::class)->group(function () {
    Route::post('get-all-items', 'index');
    Route::get('get-all-category', 'getCategory');
    Route::post('get-item-batchno', 'getbatchno');
    Route::post('save-orders', 'saveorders');
    

});

Route::controller(CustomersController::class)->group(function () {
  Route::get('get-all-customer', 'index');
  Route::post('create-customers', 'store');
  Route::post('search-customer', 'index');
});