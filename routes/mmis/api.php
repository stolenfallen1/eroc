<?php

use App\Http\Controllers\MMIS\PurchaseRequestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MMIS\UserController;

Route::controller(UserController::class)->group(function () {
  Route::get('getpermission', 'getpermission');
  Route::post('verify-passcode', 'getpermission');
});

Route::controller(PurchaseRequestController::class)->group(function () {
  Route::get('purchase-request', [PurchaseRequestController::class, 'index']);
  Route::post('purchase-request', [PurchaseRequestController::class, 'store']);
});

