<?php

use App\Http\Controllers\MMIS\PurchaseRequestController;
use App\Http\Controllers\Api\ApiAuthController;

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


Route::group(['middleware' => 'auth:api'], function ()  {
    require_once __DIR__ . '/buildfile/api.php';
    require_once __DIR__ . '/approver/api.php';
    require_once __DIR__ . '/mmis/api.php';
    Route::post('test', [UserController::class, 'store']);
    Route::get('test', [PurchaseRequestController::class, 'index']);
});
