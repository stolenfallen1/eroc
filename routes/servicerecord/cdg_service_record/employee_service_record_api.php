<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceRecord\cdg_employee_service_record\dashboard\Dashboard;
use App\Http\Controllers\ServiceRecord\cdg_employee_service_record\EmployeeMasterRecord;
use App\Http\Controllers\ServiceRecord\cdg_employee_service_record\by_department\Department;
use App\Http\Controllers\ServiceRecord\cdg_employee_service_record\by_employee\Employee;
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

// Route::get('/get-employee-detail', [Employee::class, 'getEmployeeDetail']);

Route::controller(Dashboard::class)->group(function() {
    Route::get('dashboard', 'index');
    Route::get('service-record-dashboard', 'serviceRecordDashboard');
});

Route::controller(EmployeeMasterRecord::class)->group(function() {
    Route::get('get-employee-serves-record', 'getEmployeeServiceRecords');
    Route::get('get-employee-leave', 'getEmployeeLeaves');
    Route::get('get-employee-undertime-summary', 'getEmployeeUnderTime');
    Route::get('get-employee-tardiness-summary', 'getEmployeeTardiness');
    Route::get('get-paid-leave', 'getPaidLeaves');
    Route::get('get-non-paid-leave', 'getNonPaidLeave');
    Route::get('get-employee-ot', 'getEmployeeOT');
});

Route::controller(Employee::class)->group(function() {
    Route::get('get-employee-detail', 'getEmployeeDetail');
});

Route::controller(Department::class)->group(function() {
    Route::get('get-department-list', 'getDepartmentList');
    Route::get('get-department-employee', 'getDeptEmployee');
});

