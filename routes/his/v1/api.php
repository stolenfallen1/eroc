<?php

use Illuminate\Support\Facades\Route;
use App\Models\Schedules\ORResidentModel;
use App\Http\Controllers\HIS\ReportController;
use App\Http\Controllers\BuildFile\DepartmentController;
use App\Http\Controllers\HIS\PatientRegistrationController;
use App\Http\Controllers\BuildFile\Hospital\DoctorController;

Route::controller(PatientRegistrationController::class)->group(function () {
    Route::get('search-patient', 'search');
    Route::get('patient-details', 'check_patient_details');
    Route::post('submit-patient-form', 'store');
    Route::put('update-patient/{id}', 'update');
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


