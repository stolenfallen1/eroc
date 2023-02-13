<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MMIS\UserController;

Route::get('purchase-request', [UserController::class, 'index']);
Route::get('getpermission', [UserController::class, 'getpermission']);

