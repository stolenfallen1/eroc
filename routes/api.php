<?php

use App\Http\Controllers\MMIS\PurchaseRequestController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:api'], function ()  {
    Route::get('user-details', [AuthController::class, 'userDetails']);
    Route::post('logout', [AuthController::class, 'logout']);
    require_once __DIR__ . './pos/api.php'; 
    require_once __DIR__ . '/buildfile/api.php';
    require_once __DIR__ . '/approver/api.php';
    require_once __DIR__ . '/mmis/api.php';
    require_once __DIR__ . '/itemandservices/api.php';
});
