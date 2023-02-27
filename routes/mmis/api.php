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
  Route::post('purchase-request/{id}', [PurchaseRequestController::class, 'update']);
  Route::post('purchase-request-items', [PurchaseRequestController::class, 'approveItems']);
  Route::delete('purchase-request/{id}', [PurchaseRequestController::class, 'destroy']);
  Route::delete('remove-item/{id}', [PurchaseRequestController::class, 'removeItem']);
  Route::post('update-item-attachment/{id}', [PurchaseRequestController::class, 'updateItemAttachment']);
});

