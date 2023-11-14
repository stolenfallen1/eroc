<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\POS\v1\NewItemsController;
use App\Http\Controllers\POS\v1\NewCustomersController;

Route::controller(NewCustomersController::class)->group(function () {
    Route::get('customers', 'index');
});
Route::controller(NewItemsController::class)->group(function () {
    Route::get('pos-warehouse-items', 'index');
});
?>