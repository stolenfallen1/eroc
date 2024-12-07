<?php

use App\Http\Controllers\HIS\services\EmergencyRegistrationController;
use App\Http\Controllers\HIS\services\OutpatientRegistrationController;
use App\Http\Controllers\HIS\services\InpatientRegistrationController;
use App\Http\Controllers\HIS\services\RegistrationController;
use App\Http\Controllers\HIS\AllergyTypeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HIS\MasterPatientController;
use App\Http\Controllers\HIS\his_functions\SOAController;
use App\Http\Controllers\HIS\PatientDischarge;
use App\Http\Controllers\HIS\NursingService\ReportMaster;

Route::get('search-patient-master', [MasterPatientController::class, 'list']);
Route::resource('patient-master', MasterPatientController::class);
Route::post('get-patient-allergy-history', [AllergyTypeController::class, 'getPatientAllergyHistory']);

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
    Route::get('get-staff-id', 'getStaffId');
    Route::put('revoke-patient/{id}', 'revokepatient');
    Route::get('get-revoked-emergency-patient', 'getrevokedemergencypatient');
    Route::get('/patient-brought-by', 'getPatientBroughtBy');
    Route::get('/get-msc-complaint', 'getComplaintList');
    Route::get('disposition', 'getDisposition');
    Route::get('service-type', 'getServiceType');
    // Route::post('register-emergency', 'register');
    // Route::put('update-emergency/{id}', 'update');
});

Route::controller(RegistrationController::class)->group(function() {
    Route::post('register-emergency', 'register');
    Route::put('update-emergency/{id}', 'update');
});

Route::controller(SOAController::class)->group(function() {
    Route::get('generate-statement/{id}', 'createStatmentOfAccount');
    Route::get('generate-statement-summary/{id}', 'createStatmentOfAccountSummary');
});

Route::controller(ReportMaster::class)->group(function() {
    Route::get('generate-er-daily-report', 'ERDailyCensusReport');
});

Route::controller(PatientDischarge::class)->group(function(){
    Route::get('doctors-list', 'getDoctorsList');
    Route::get('patient-status', 'getPatientStatusList');
    Route::get('patient-billing-charges/{id}', 'getPatientChargesStatus');
    Route::put('tag-patient-maygohome/{id}', 'mayGoHome');
    Route::put('untag-patient-maygohome/{id}', 'untagMGH');
    Route::get('check-elgibility-for-discharge/{id}', 'checkPatientEligibilityForDischarge');
    Route::put('discharge-patient/{id}', 'dischargePatient');
    Route::get('patient-balance/{id}', 'getTotalCharges');
    Route::get('get-er-result', 'erResult');
});

Route::controller(InpatientRegistrationController::class)->group(function () {
    Route::get('get-inpatient', 'index');
    Route::get('get-revoked-inpatient', 'getrevokedinpatient');
    Route::post('register-inpatient', 'register');
    Route::put('update-inpatient/{id}', 'update'); 
});