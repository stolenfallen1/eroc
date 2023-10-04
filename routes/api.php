<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthPOSController;
use App\Http\Controllers\POS\SettingController;
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

Route::get('/schedules', [ORSchedulesController::class, 'index']);
Route::get('/getdoctor', [ORSchedulesController::class, 'getdoctor']);
Route::get('/getORCategory', [ORSchedulesController::class, 'getORCategory']);
Route::get('/searchPatientData', [ORSchedulePatientController::class, 'searchPatientData']);
Route::get('/getORRooms', [ORSchedulesController::class, 'getORRooms']);
Route::get('/getORRoomTimeSlot', [ORSchedulesController::class, 'getORRoomTimeSlot']);
Route::get('/getORCirculatingNurses', [ORSchedulesController::class, 'getORCirculatingNurses']);
Route::get('/getORCaseTypes', [ORSchedulesController::class, 'getORCaseTypes']);
Route::get('/checkRoomAvailability', [ORSchedulesController::class, 'checkRoomAvailability']);

Route::post('/submitschedule', [ORSchedulesController::class, 'store']);




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
});
