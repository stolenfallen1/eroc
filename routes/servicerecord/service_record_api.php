<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceRecord\TbcMaster;
use App\Http\Controllers\ServiceRecord\PdfController;
use App\Http\Controllers\ServiceRecord\EmployeeTbcMaster;
use App\Http\Controllers\ServiceRecord\DepartmentTbcMaster;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::get('/dashboard',                                        [TbcMaster::class,              'dashboard']);
// Route::get('/employee-master',                                  [EmployeeTbcMaster::class,      'index']);
// Route::get('/employee-detail',                                  [EmployeeTbcMaster::class,      'getEmployeeDetail']);
// Route::get('/employee-leave',                                   [EmployeeTbcMaster::class,      'getEmployeeLeaves']);
// Route::get('/employee-undertime-summary',                       [EmployeeTbcMaster::class,      'getEmployeeUnderTime']);
// Route::get('/employee-tardiness-summary',                       [EmployeeTbcMaster::class,      'getEmployeeTardiness']);
// Route::get('/employee-serves-record',                           [EmployeeTbcMaster::class,      'getEmployeeServiceRecords']);
// Route::get('/paid-leave',                                       [EmployeeTbcMaster::class,      'getPainLeaves']);
// Route::get('/non-paid-leave',                                   [EmployeeTbcMaster::class,      'getNonPaidLeave']);
// Route::get('/employee-ot',                                      [EmployeeTbcMaster::class,      'getEmployeeOT']);
// Route::get('/department/department-list',                       [DepartmentTbcMaster::class,    'getDepartmentList']);
// Route::get('/department/department-employee',                   [DepartmentTbcMaster::class,    'getDeptEmployee']);

Route::controller(EmployeeTbcMaster::class)->group(function() {
    Route::get('get-employee', 'index');
    Route::get('get-employee-detail', 'getEmployeeDetail');
    Route::get('get-employee-leave', 'getEmployeeLeaves');
    Route::get('get-employee-undertime-summary', 'getEmployeeUnderTime');
    Route::get('get-employee-tardiness-summary', 'getEmployeeTardiness');
    Route::get('get-employee-serves-record', 'getEmployeeServiceRecords');
    Route::get('get-paid-leave', 'getPainLeaves');
    Route::get('get-non-paid-leave', 'getNonPaidLeave');
    Route::get('get-employee-ot', 'getEmployeeOT');
});

Route::controller(DepartmentTbcMaster::class)->group(function() {
    Route::get('get-department-list', 'getDepartmentList');
    Route::get('get-department-employee', 'getDeptEmployee');
});

