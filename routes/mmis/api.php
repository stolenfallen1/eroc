<?php

use App\Http\Controllers\MMIS\PurchaseRequestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MMIS\UserController;

Route::get('purchase-request', [UserController::class, 'index']);
Route::post('purchase-request', [PurchaseRequestController::class, 'store']);
Route::get('getpermission', [UserController::class, 'getpermission']);

