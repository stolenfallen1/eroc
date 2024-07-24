<?php

use App\Http\Controllers\HIS\his_functions\HISCashAssestmentController;
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

Route::controller(CashierController::class)->group(function () {
    Route::get('get-charge-item', 'populatechargeitem');
    Route::post('save-payment', 'save');
    Route::get('get-ornumber', 'getOR');
    Route::put('cancel-ornumber', 'cancelOR');
});