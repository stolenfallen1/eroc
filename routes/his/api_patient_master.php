<?php

use App\Http\Controllers\HIS\services\EmergencyRegistrationController;
use App\Http\Controllers\HIS\services\OutpatientRegistrationController;
use App\Http\Controllers\HIS\services\InpatientRegistrationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HIS\MasterPatientController;

Route::get('search-patient-master', [MasterPatientController::class, 'list']);
Route::resource('patient-master', MasterPatientController::class);

Route::controller(OutpatientRegistrationController::class)->group(function () {
    Route::get('get-outpatient', 'index');
    Route::get('get-revoked-outpatient', 'getrevokedoutpatient');
    Route::post('register-outpatient', 'register');
    Route::put('update-outpatient/{id}', 'update');
    Route::put('revoke-patient/{id}', 'revokepatient');
    Route::put('unrevoke-patient/{id}', 'unrevokepatient');
}); 
Route::controller(EmergencyRegistrationController::class)->group(function () {
    Route::get('get-emergency', 'index');
    Route::get('/patient-brought-by', 'getPatientBroughtBy');
    Route::get('/get-msc-complaint', 'getComplaintList');
    Route::get('disposition', 'getDisposition');
    Route::post('register-emergency', 'register');
    Route::put('update-emergency/{id}', 'update');
});
Route::controller(InpatientRegistrationController::class)->group(function () {
    Route::get('get-inpatient', 'index');
    Route::post('register-inpatient', 'register');
    Route::put('update-inpatient/{id}', 'update'); 
});