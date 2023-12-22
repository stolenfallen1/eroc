<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\POS\OrdersController;
use App\Http\Controllers\POS\ReportsController;
use App\Http\Controllers\POS\PaymentTransaction;
use App\Http\Controllers\POS\Report_ZController;
use App\Http\Controllers\POS\CustomersController;
use App\Http\Controllers\POS\DashboardController;
use App\Http\Controllers\POS\BIRSettingsController;
use App\Http\Controllers\POS\ExpiredItemsController;
use App\Http\Controllers\POS\CustomerOrderController;
use App\Http\Controllers\POS\CustomerGroupsController;
use App\Http\Controllers\POS\PosTransactionController;
use App\Http\Controllers\POS\Report_SummaryController;
use App\Http\Controllers\POS\SeriesSettingsController;
use App\Http\Controllers\POS\CompanySettingsController;
use App\Http\Controllers\POS\OpenningAmountTransaction;
use App\Http\Controllers\POS\Report_ItemizedController;

use App\Http\Controllers\POS\TerminalSettingsController;
use App\Http\Controllers\POS\Report_DailySalesController;
use App\Http\Controllers\POS\Report_SalesBatchController;
use App\Http\Controllers\POS\ReturnTransactionController;
use App\Http\Controllers\POS\TakeOrderTerminalController;
use App\Http\Controllers\POS\ClosingTransactionController;

// get all items 


Route::controller(ExpiredItemsController::class)->group(function () {
  Route::get('get-expired-items', 'index');
});
Route::controller(DashboardController::class)->group(function () {
  Route::post('generate-dashboard', 'index');
  Route::post('get-expired-items', 'expired');
});


Route::controller(PosTransactionController::class)->group(function () {
    Route::get('get-all-items', 'index');
    Route::get('get-all-user', 'getDepartmentUsers');
    Route::get('get-msc-build', 'getMscbuild');
    Route::get('get-card-type/{id}', 'getcard');
    Route::get('check-opening-status', 'openingstatus');
    Route::post('get-item-batchno', 'getbatchno');
    Route::post('get-refund-type', 'getrefundtype');
});

Route::controller(PaymentTransaction::class)->group(function () {
  Route::post('save-payment', 'store');
  Route::post('accept-payment', 'save_payment');
  
  Route::put('update-orders/{id}', 'update');
  Route::post('reprint-receipt', 'reprintreceipt');
  Route::post('print-refund-receipt','printRefund');
});

Route::controller(CustomerOrderController::class)->group(function () {
  Route::post('create-order', 'create_orders');
  Route::post('remove-order-item', 'remove_orders');
  Route::post('save-customer-orders', 'save_orders');
 
});

Route::controller(OrdersController::class)->group(function () {
  Route::post('create-order1', 'create_orders');
  Route::post('checkout-orders', 'checkout_orders');
  
  Route::post('other-discount', 'apply_other_dicount');
  Route::post('remove-order', 'remove_orders');
  Route::post('remove-item', 'remove_item');
  Route::post('clear-cart', 'clear_order');
  
  // Route::post('get-default-customer', 'get_orders');
  Route::get('get-all-orders', 'index');
  Route::get('get-sales-orders', 'sales_order');
  Route::post('store-pick-list-item', 'picklist');
  Route::get('get-pick-list-item', 'getpicklist');

  Route::post('save-orders', 'store');
  Route::put('update-orders/{id}', 'update');
  Route::post('reprint-picklist', 'reprintpicklist');
  Route::post('cancel-order', 'cancelorder');
});

Route::controller(ReturnTransactionController::class)->group(function () {
  Route::post('get-return-orders', 'get_order_transaction');
  Route::post('return-item', 'return_item');
  Route::post('return-approval', 'return_approval');
  Route::post('submit-payment-excess', 'submitexcesspayment');

  Route::post('save-return-order', 'store');
  Route::post('submit-return-order', 'save_return');
  Route::post('create-json-return-order', 'return_exchange_orders');
  Route::put('submit-forapproval/{id}', 'update');
  Route::post('get-all-return-order', 'index');
  Route::post('get-orders', 'returnorder');
  
  Route::post('search-return-orders', 'index');
  Route::post('get-refund-details', 'getRefundDetails');
  Route::post('submit-excess-payment', 'submitexcesspayment');
});

Route::controller(CustomersController::class)->group(function () {
  Route::get('get-all-customers', 'index');
  Route::get('get-default-customer', 'default');
  Route::get('get-all-customer', 'index');
  Route::post('create-customers', 'store');
  Route::post('search-customer', 'index');
});

Route::controller(CustomerGroupsController::class)->group(function () {
  Route::get('get-all-customer-group', 'index');
});


Route::controller(Report_ZController::class)->group(function () {
    Route::post('get-x-report-per-shift', 'Xreading_per_shift');
    Route::post('get-z-report-all-shift', 'generate_z_report');
});

Route::controller(OpenningAmountTransaction::class)->group(function () {
  Route::get('get-beginning', 'index');
  Route::get('check-cash-registry', 'cash_registry');
  Route::get('cash-registry', 'cash_registry_movement');
  Route::get('get-beginning-transaction', 'beginning_transaction');
  Route::post('save-opening-amount', 'store');
  Route::put('update-opening-amount/{id}', 'update');
});

Route::controller(ClosingTransactionController::class)->group(function () {
  Route::post('save-closing-transaction', 'store');
  Route::post('update-closing-transaction/{id}', 'store');
  Route::put('closing-transaction/{id}', 'closing_transaction');
  Route::put('post-transaction/{id}', 'posting_transaction');
  
  
});






Route::controller(Report_ItemizedController::class)->group(function () {
  Route::post('get-itemized-report', 'itemizedReport');
});
Route::controller(Report_SalesBatchController::class)->group(function () {
  Route::post('get-itembatch-report', 'itemBatchReport');
});
Route::controller(Report_SummaryController::class)->group(function () {
  Route::post('get-itemsummary-report', 'itemSummaryReport');
});
Route::controller(Report_DailySalesController::class)->group(function () {
  Route::post('get-daily-sales-report', 'DailysalesReport');
});

Route::controller(ReportsController::class)->group(function () {
  Route::post('get-accountability-report', 'accountability_report');
  Route::post('get-bank-summary-report', 'BanknoteSummaryReport');
  Route::post('get-itemsummary-details-report', 'itemSummaryDetailReport');
  Route::post('get-cashier-name', 'getcashiername');
  
});

