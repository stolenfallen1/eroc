<?php

use App\Http\Controllers\HIS\PatientRegistrationController;
use App\Http\Controllers\HIS\ReportController;
use Illuminate\Support\Facades\Route;

Route::controller(PatientRegistrationController::class)->group(function () {
    Route::get('search-patient', 'search');
    Route::get('patient-details', 'check_patient_details');
    Route::post('submit-patient-form', 'store');
    Route::put('update-patient/{id}', 'update');
});
Route::resource('patient-registration', PatientRegistrationController::class);

Route::controller(ReportController::class)->group(function () {
    Route::get('all-montly-report', 'AllMontlyReport');
    Route::get('daily-transaction-report', 'DailyTransactionReport');
});


