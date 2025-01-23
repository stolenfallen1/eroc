<?php

use App\Http\Controllers\HIS\basic_form_registration_data\BasicRegistryData;
use App\Http\Controllers\HIS\services\emergency\EmergencyPatientList;
use App\Http\Controllers\HIS\services\in_patient\InPatientList;
use App\Http\Controllers\HIS\services\out_patient\OutPatientList;
use App\Http\Controllers\HIS\services\OutpatientRegistrationController;
use App\Http\Controllers\HIS\services\InpatientRegistrationController;
use App\Http\Controllers\HIS\services\patient_registration\PatientRegistration;
use App\Http\Controllers\HIS\AllergyTypeController;
use App\Http\Controllers\HIS\services\revoke_patient_registration\RevokePatientRegistration;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HIS\MasterPatientController;
use App\Http\Controllers\HIS\his_functions\SOAController;
use App\Http\Controllers\HIS\NursingService\ReportMaster;
use App\Http\Controllers\HIS\discharge_patient\DischargePatient;
use App\Http\Controllers\HIS\patient_may_go_home\PatientForMayGoHome;

Route::get('search-patient-master', [MasterPatientController::class, 'list']);
Route::resource('patient-master', MasterPatientController::class);
Route::post('get-patient-allergy-history', [AllergyTypeController::class, 'getPatientAllergyHistory']);

Route::controller(BasicRegistryData::class)->group(function() {
    Route::get('patient-brought-by', 'getPatientBroughtBy');
    Route::get('disposition', 'getDisposition');
    Route::get('service-type', 'getServiceType');
    Route::get('get-msc-complaint', 'getComplaintList');
});

Route::controller(PatientRegistration::class)->group(function() {
    Route::post('register-patient', 'register');
    Route::put('update-patient/{id}', 'update');
});

Route::controller(OutPatientList::class)->group(function () {
    Route::get('get-out-patient', 'registeredPatientList');
    Route::get('get-revoked-out-patient', 'revokedPatientList');
});

Route::controller(EmergencyPatientList::class)->group(function () {
    Route::get('get-emergency-patient', 'registeredPatientList');
    Route::get('get-revoked-emergency-patient', 'revokedPatientList');
});

Route::controller(InPatientList::class)->group(function () {
    Route::get('get-in-patient', 'registeredPatientList');
    Route::get('patient-for-admission', 'getPatientForAdmission');
    Route::get('get-selected-patient-for-admission/{id}', 'getSelectedPatientForAdmission');
    Route::get('get-revoked-in-patient', 'revokedPatientList');
});

Route::controller(RevokePatientRegistration::class)->group(function () {
    Route::put('revoke-patient/{id}', 'revokedPatientRegistration');
    Route::post('unrevoke-patient', 'unRevokedPatientRegistration');
});

Route::controller(SOAController::class)->group(function() {
    Route::get('generate-statement/{id}', 'createStatmentOfAccount');
    Route::get('generate-statement-summary/{id}', 'createStatmentOfAccountSummary');
});

Route::controller(ReportMaster::class)->group(function() {
    Route::get('generate-er-daily-report', 'ERDailyCensusReport');
});

Route::controller(DischargePatient::class)->group(function(){
    Route::get('check-elgibility-for-discharge/{id}', 'checkPatientEligibilityForDischarge');
    Route::put('discharge-patient/{id}', 'dischargePatient'); 
    Route::get('patient-balance/{id}', 'getTotalCharges');
});

Route::controller(PatientForMayGoHome::class)->group(function(){
    Route::put('tag-patient-maygohome/{id}', 'mayGoHome');
    Route::put('untag-patient-maygohome/{id}', 'untagMGH');
    Route::get('patient-billing-charges/{id}', 'getPatientChargesStatus');
    Route::get('doctors-list', 'getDoctorsList');
    Route::get('patient-status', 'getPatientStatusList');
    Route::get('get-er-result', 'erResult');
});
