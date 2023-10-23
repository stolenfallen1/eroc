<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthPOSController;
use App\Http\Controllers\POS\SettingController;
use App\Http\Controllers\Schedules\SchedulingDashboard;
use App\Http\Controllers\MMIS\PurchaseRequestController;
use App\Http\Controllers\Schedules\ORSchedulesController;
use App\Http\Controllers\Schedules\ORSchedulePatientController;

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


/*require_once('/schedules/api.php');*/


Route::get('scheduling-json', [SchedulingDashboard::class, 'getSchedulingDashboard']);

Route::post('login', [AuthController::class, 'login']);
Route::post('pos/login', [AuthPOSController::class, 'login']);
Route::get('get-schedule', [SettingController::class, 'schedule']);

Route::group(['middleware' => 'auth:api'], function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('department/users', 'getDepartmentUsers');
    });
    Route::get('get-setting', [SettingController::class, 'index']);
    Route::get('user-details', [AuthController::class, 'userDetails']);
    Route::post('refresh', [AuthController::class, 'refreshToken']);

    Route::post('/pos/refresh', [AuthPOSController::class, 'refreshToken']);
    Route::get('pos/user-details', [AuthPOSController::class, 'userDetails']);
    Route::post('logout', [AuthController::class, 'logout']);
    require_once('pos/api.php');
    require_once('buildfile/api.php');
    require_once('approver/api.php');
    require_once('mmis/api.php');
    require_once('itemandservices/api.php');
    require_once('his/v1/api.php');
    require_once('schedules/api.php');
});


