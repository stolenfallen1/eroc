<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\POS\OrdersController;
use App\Http\Controllers\POS\PaymentTransaction;
use App\Http\Controllers\POS\CustomersController;
use App\Http\Controllers\POS\PosTransactionController;
use App\Http\Controllers\POS\OpenningAmountTransaction;
use App\Http\Controllers\POS\ReturnTransactionController;
use App\Http\Controllers\POS\ClosingTransactionController;


// get all items 

Route::controller(PosTransactionController::class)->group(function () {
    Route::post('get-all-items', 'index');
    Route::get('get-msc-build', 'getMscbuild');
    Route::get('get-card-type/{id}', 'getcard');
    Route::get('check-opening-status', 'openingstatus');
    Route::post('get-item-batchno', 'getbatchno');
    Route::post('get-refund-type', 'getrefundtype');
    
});

Route::controller(PaymentTransaction::class)->group(function () {
  Route::post('save-payment', 'store');
  Route::put('update-orders/{id}', 'update');
  Route::post('reprint-receipt', 'reprintreceipt');
  Route::post('print-refund-receipt','printRefund');
  
});


Route::controller(OrdersController::class)->group(function () {
  Route::post('get-all-orders', 'index');
  Route::post('save-orders', 'store');
  Route::put('update-orders/{id}', 'update');
  Route::post('reprint-picklist', 'reprintpicklist');
  Route::post('cancel-order', 'cancelorder');
});

Route::controller(ReturnTransactionController::class)->group(function () {
  Route::post('save-return-order', 'store');
  Route::put('submit-forapproval/{id}', 'update');
  Route::post('get-all-return-order', 'index');
  Route::post('get-refund-details', 'getRefundDetails');
  Route::post('submit-excess-payment', 'submitexcesspayment');
});



Route::controller(CustomersController::class)->group(function () {
  Route::get('get-all-customer', 'index');
  Route::post('create-customers', 'store');
  Route::post('search-customer', 'index');
});


Route::controller(OpenningAmountTransaction::class)->group(function () {
  Route::get('get-beginning', 'index');
  Route::post('get-beginning', 'index');
  Route::post('save-opening-amount', 'store');
  Route::put('update-opening-amount/{id}', 'update');
});

Route::controller(ClosingTransactionController::class)->group(function () {
  Route::post('save-closing-transaction', 'store');
  Route::put('update-closing-transaction/{id}', 'update');
});
