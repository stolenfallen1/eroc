<?php

use Illuminate\Http\Request;
use App\Models\BuildFile\Syssystems;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthPOSController;
use App\Http\Controllers\ClearanceController;
use App\Http\Controllers\POS\SettingController;
use App\Http\Controllers\ServiceRecord\PdfController;
use App\Http\Controllers\Schedules\SchedulingDashboard;
use App\Http\Controllers\POS\TerminalSettingsController;

Route::get('check-status',function(){
    $data = Syssystems::where('id',1)->select('isActive')->first();
    return response()->json($data,200);
});

Route::get('clearances', [ClearanceController::class, 'index']);
Route::get('/service_record/pdf/generate-save-pdf',  [PdfController::class,          'generatePDF']);
Route::controller(TerminalSettingsController::class)->group(function () {
    Route::post('store-terminal', 'store');
});
Route::get('scheduling-json', [SchedulingDashboard::class, 'getSchedulingDashboard']);
Route::post('login', [AuthController::class, 'login']);
Route::post('check-pos-terminal', [AuthPOSController::class, 'posterminal']);
Route::post('pos/login', [AuthPOSController::class, 'login']);
Route::get('get-schedule', [SettingController::class, 'schedule']);
Route::post('create-account', [UserController::class, 'createdoctor']);


