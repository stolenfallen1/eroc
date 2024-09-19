<?php

use App\Http\Controllers\HIS\his_functions\CashierController;
use App\Http\Controllers\HIS\his_functions\HISCashAssestmentController;
use App\Http\Controllers\HIS\his_functions\SOAController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HIS\his_functions\HISPostChargesController;


// Charge for Company / Insurance
Route::controller(HISPostChargesController::class)->group(function () {
    Route::post('post-charge-history', 'chargehistory');
    Route::post('post-his-charge', 'charge');
    Route::put('revoke-his-charge', 'revokecharge');
});
// Charge for Cash Assessment
Route::controller(HISCashAssestmentController::class)->group(function () {
    Route::post('cash-assessment-history', 'getcashassessment');
    Route::post('post-cash-assessment', 'cashassessment');
    Route::put('revoke-cash-assessment', 'revokecashassessment');
});
// Cashier Routes 
Route::controller(CashierController::class)->group(function () {
    Route::get('get-charge-item', 'populatechargeitem');
    Route::get('get-ornumber', 'getOR');
    Route::get('get-payment-codes', 'getpaymentcode');
    Route::get('get-or-sequence', 'getORSequence');
    Route::get('get-patient-by-caseno', 'populatePatientDataByCaseNo');
    Route::get('get-cashier-discount', 'getCashierDiscount');
    Route::get('get-company-details', 'getCompanyDetails');
    Route::post('get-opd-bill', 'getOPDBill');
    Route::post('cashier-settings', 'cashiersettings');
    Route::post('save-payment', 'saveCashAssessment');
    Route::post('save-opbill', 'saveOPDBill');
    Route::post('save-companybill', 'saveCompanyTransaction');
    Route::put('cancel-ornumber', 'cancelOR');
});
// SOA Routes
Route::controller(SOAController::class)->group(function () {
    Route::get('get-his-patient-soa', 'getPatientSOA');
});
