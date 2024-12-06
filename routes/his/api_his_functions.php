<?php

use App\Http\Controllers\HIS\his_functions\AncillaryController;
use App\Http\Controllers\HIS\his_functions\CashierController;
use App\Http\Controllers\HIS\his_functions\HISCashAssestmentController;
use App\Http\Controllers\HIS\his_functions\HISGlobalController;
use App\Http\Controllers\HIS\his_functions\LaboratoryController;
use App\Http\Controllers\HIS\his_functions\opd_specific\OPDMedicinesSuppliesController;
use App\Http\Controllers\HIS\his_functions\PharmacyController;
use App\Http\Controllers\HIS\his_functions\RequisitionController;
use App\Http\Controllers\HIS\his_functions\SOAController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HIS\his_functions\HISPostChargesController;
use App\Http\Controllers\HIS\EmergencyRoomMedicine;
use App\Http\Controllers\HIS\NurseActivity;


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
    Route::get('get-or-for-cancellation', 'getORForCancellation');
    Route::get('get-payment-codes', 'getpaymentcode');
    Route::get('get-or-sequence', 'getORSequence');
    Route::get('get-patient-by-caseno', 'populatePatientDataByCaseNo');
    Route::get('get-cashier-discount', 'getCashierDiscount');
    Route::get('get-company-details', 'getCompanyDetails');
    Route::post('get-opd-bill', 'getOPDBill');
    Route::post('cashier-settings', 'cashiersettings');
    Route::post('save-cash-transaction', 'saveCashAssessment');
    Route::post('save-opbill', 'saveOPDBill');
    Route::post('save-companybill', 'saveCompanyTransaction');
    Route::put('cancel-ornumber', 'cancelOR');
});
// SOA Routes
Route::controller(SOAController::class)->group(function () {
    Route::get('get-his-patient-soa', 'getPatientSOA');
});
// Laboratory Routes
Route::controller(LaboratoryController::class)->group(function() {
    Route::get('get-opd-patients', 'getOPDPatients');
    Route::get('get-er-patients', 'getERPatients');
    Route::get('get-ipd-patients', 'getIPDPatient');
    Route::get('get-laboratory-opd-orders', 'getOPDPendingLabRequest');
    Route::get('get-laboratory-er-orders', 'getERPendingLabRequest');
    Route::get('get-laboratory-ipd-orders', 'getIPDPendingLabRequest');
    Route::post('get-laboratory-exams', 'getAllLabExamsByPatient'); // Get All Laboratory Exams
    Route::post('carry-laboratory-exam', 'carryOrder');
    Route::post('cancel-laboratory-exam', 'cancelOrder'); 
    Route::post('get-patient-lab-request-status', 'checkPatientLabStatusRequest');
});
// Ancillary Routes
Route::controller(AncillaryController::class)->group(function() {
    Route::get('get-opd-ancillary-orders', 'getOPDOrders');
    Route::get('get-er-ancillary-orders', 'getEROrders');
    Route::get('get-ipd-ancillary-orders', 'getIPDOrders');
    Route::get('get-ancillary-posted-supplies', 'getPostedSuppliesByCaseNo');
    Route::post('carry-ancillary-order', 'carryOrder');
    Route::post('cancel-ancillary-order', 'cancelOrder');
    Route::post('post-return-supplies', 'postReturnSupplies');
});
// Pharmacy Routes
Route::controller(PharmacyController::class)->group(function() {
    Route::get('get-opd-pharmacy-orders', 'getOPDOrders');
    Route::get('get-er-pharmacy-orders', 'getEROrders');
    Route::get('get-pharmacy-posted-meds', 'getPostedMedicineByCaseNo');
    Route::get('get-ipd-pharmacy-orders', 'getIPDOrders');
    Route::post('carry-pharmacy-order', 'carryOrder');
    Route::post('cancel-pharmacy-order', 'cancelOrder');
    Route::post('post-return-medicine', 'postReturnMedicine');
});
// Requisition Routes 
Route::controller(RequisitionController::class)->group(function() {
    Route::get('get-warehouses', 'getWarehouses');
    Route::post('get-patients-requisitions', 'getPatientRequisitions');
    Route::post('get-rendered-transactions', 'getRenderedPatientRequisitions');
    Route::post('get-cancelled-transactions', 'getCancelledRequisitions');
    Route::post('get-warehouse-items', 'getWarehouseItems');
    Route::post('save-supply-requisition', 'saveSupplyRequisition');
    Route::post('save-medicine-requisition', 'saveMedicineRequisition');
    Route::post('save-procedure-requisition', 'saveProcedureRequisition');
    Route::post('cancel-requisition-request', 'onRevokeRequisition');
});
// OPD SPECIFIC - Routes
// Post Medicine / Supplies Routes
Route::controller(OPDMedicinesSuppliesController::class)->group(function() {
    Route::post('get-medicine-supplies', 'medicineSuppliesList');
    Route::post('charge-medicine-supplies', 'chargeMedicineSupplies');
    Route::post('get-medicine-supplies-charge-history', 'getPostedMedicineSupplies');
    Route::put('revoke-medicine-supplies-charge', 'revokecharge');
});

Route::controller(EmergencyRoomMedicine::class)->group(function() {
    Route::post('er-get-medicine-suplies', 'erRoomMedicine');
    Route::post('er-medicine-supplies-charges', 'chargePatientMedicineSupply');
    Route::get('get-charge-items/{id}', 'getMedicineSupplyCharges');
    Route::post('er-cancel-charge', 'cancelCharges');
});

Route::controller(NurseActivity::class)->group(function() {
    Route::get('get-charges-list/{id}', 'getChargeList');
});

// Naa dre ang mga reusable nga functions for the HIS LIKE GENERAL NGA REPORTING
Route::controller(HISGlobalController::class)->group(function() {
    Route::get('daily-income-report', 'printDailyIncomeReport');
    Route::get('monthly-income-report', 'printMonthlyIncomeReport');
});
