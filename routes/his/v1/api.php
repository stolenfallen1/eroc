<?php

use Illuminate\Support\Facades\Route;
use App\Models\Schedules\ORResidentModel;
use App\Http\Controllers\HIS\ReportController;
use App\Http\Controllers\HIS\PostChargeController;
use App\Http\Controllers\BuildFile\DepartmentController;
use App\Http\Controllers\HIS\PatientRegistrationController;
use App\Http\Controllers\BuildFile\Hospital\DoctorController;
use App\Http\Controllers\HIS\HemodialysisMonitoringController;
use App\Http\Controllers\HIS\HemodialysisDailyCensusController;

Route::controller(PatientRegistrationController::class)->group(function () {
    Route::get('search-patient', 'search');
    Route::get('patient-details', 'check_patient_details');
    Route::post('submit-patient-form', 'store');
    Route::put('update-patient/{id}', 'update');
});


Route::controller(HemodialysisDailyCensusController::class)->group(function () {
    Route::get('in-patient-daily-census', 'inpatient');
    Route::get('out-patient-daily-census', 'outpatient');
    Route::get('in-patient-daily-census-report', 'inpatient_daily_census_report');
    Route::get('out-patient-daily-census-report', 'outpatient_daily_census_report');

});

Route::controller(HemodialysisMonitoringController::class)->group(function () {
    Route::get('patient-monitoring', 'index');
 
});

Route::controller(PostChargeController::class)->group(function () {
    Route::post('getcharges', 'getcharges');
    Route::post('postcharge', 'postcharge');
    Route::post('cancellationcharges', 'cancelcharges');
});

Route::resource('patient-registration', PatientRegistrationController::class);

Route::controller(DoctorController::class)->group(function () {
    Route::get('his/doctors-list', 'index');
});


Route::controller(DepartmentController::class)->group(function () {
    Route::get('his/departments-list', 'departmentlist');
});

Route::controller(ReportController::class)->group(function () {
    Route::get('all-montly-report', 'AllMontlyReport');
    Route::get('daily-transaction-report', 'DailyTransactionReport');
});


