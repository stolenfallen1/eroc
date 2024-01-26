<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\POS\v1\ReportsController;
use App\Http\Controllers\POS\v1\BIRSettingsController;
use App\Http\Controllers\POS\v1\NewItemsController;
use App\Http\Controllers\POS\v1\OpenningController;
use App\Http\Controllers\POS\v1\RegistryController;
use App\Http\Controllers\POS\SeriesSettingsController;
use App\Http\Controllers\POS\CompanySettingsController;
use App\Http\Controllers\POS\v1\NewCustomersController;
use App\Http\Controllers\POS\TerminalSettingsController;
use App\Http\Controllers\POS\TakeOrderTerminalController;
use App\Http\Controllers\POS\v1\NewCustomerOrderController;
use App\Http\Controllers\POS\v1\NewCustomerPaymentController;
use App\Http\Controllers\POS\v1\NewReturnTransactionController;


Route::controller(OpenningController::class)->group(function () {
    Route::get('opening', 'index');
    Route::post('process-opening-amount', 'store');
    Route::get('cards/{id}', 'getcard');
});


Route::controller(NewCustomersController::class)->group(function () {
    Route::get('customers', 'index');
});


Route::controller(NewReturnTransactionController::class)->group(function () {
    Route::get('pos-get-return-orders', 'index');
    Route::post('pos-save-return-orders', 'store');
    Route::post('pos-approved-return-orders', 'approvedreturnedorder');

});

Route::controller(NewItemsController::class)->group(function () {
    Route::post('pos-warehouse-items', 'index');
});

Route::controller(NewCustomerOrderController::class)->group(function () {
    Route::get('print-picklist/{id}', 'print_picklist');
    Route::get('get-customer-orders', 'getcustomerorders');
    Route::post('pos-save-orders', 'store');
    Route::post('pos-cancel-orders', 'cancelorder');
});

Route::controller(NewCustomerPaymentController::class)->group(function () {
    Route::get('get-customer-payments', 'index');
    Route::get('print-receipt/{id}', 'print_receipt');
    Route::post('pos-processing-payment', 'store');
});


Route::controller(RegistryController::class)->group(function () {
    Route::get('get-registry', 'index');
    Route::post('denomination-registry', 'store');
    Route::post('post-registry', 'postregistry');
    Route::post('closed-registry', 'closedregistry');
});

Route::controller(CompanySettingsController::class)->group(function () {
    Route::get('get-company-settings', 'index');
    Route::put('update-company-settings/{id}', 'update');
    Route::post('store-company-settings', 'store');
    Route::delete('delete-company-settings/{id}', 'destroy');
});

Route::controller(TerminalSettingsController::class)->group(function () {
    Route::get('get-terminal', 'index');
    Route::put('update-terminal/{id}', 'update');
    Route::post('store-terminal', 'store');
    Route::delete('delete-terminal-settings/{id}', 'destroy');
});

Route::controller(TakeOrderTerminalController::class)->group(function () {
    Route::get('get-take-order-terminal/{id}', 'show');
    Route::put('update-take-order-terminal/{id}', 'update');
    Route::post('store-take-order-terminal', 'store');
    Route::delete('delete-take-order-terminal-settings/{id}', 'destroy');
});

Route::controller(SeriesSettingsController::class)->group(function () {
    Route::get('get-series-number', 'index');
    Route::put('update-series-number/{id}', 'update');
    Route::post('store-series-number', 'store');
    Route::delete('delete-series-settings/{id}', 'destroy');
});

Route::controller(BIRSettingsController::class)->group(function () {
    Route::get('get-bir-settings', 'index');
    Route::put('update-bir/{id}', 'update');
    Route::post('store-bir', 'store');
    Route::delete('delete-bir-settings/{id}', 'destroy');
});


Route::controller(ReportsController::class)->group(function () {
    Route::post('get-accountability-report', 'accountability_report');
});

?>