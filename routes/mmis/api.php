<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MMIS\UserController;
use App\Http\Controllers\MMIS\AuditController;
use App\Http\Controllers\MMIS\BatchController;
use App\Http\Controllers\MMIS\CanvasController;
use App\Http\Controllers\ManualUpdateController;
use App\Http\Controllers\MMIS\ExpenseController;
use App\Http\Controllers\MMIS\DeliveryController;
use App\Http\Controllers\MMIS\DashboardController;
use App\Http\Controllers\MMIS\AuditTrailController;
use App\Http\Controllers\MMIS\ExportDataController;
use App\Http\Controllers\MMIS\PurchaseOrderController;
use App\Http\Controllers\MMIS\StockTransferController;
use App\Http\Controllers\MMIS\PurchaseReturnController;
use App\Http\Controllers\MMIS\PurchaseRequestController;
use App\Http\Controllers\MMIS\StockRequisitionController;
use App\Http\Controllers\MMIS\ConsignmentDeliveryController;
use App\Http\Controllers\MMIS\PriceList\PriceListController;
use App\Http\Controllers\MMIS\InventoryStocksAlertController;
use App\Http\Controllers\MMIS\InventoryTransactionController;
use App\Http\Controllers\MMIS\Reports\PurchaseSubsidiaryReportController;

Route::controller(UserController::class)->group(function () {
  Route::get('getpermission', 'getpermission');
  Route::post('verify-passcode', 'getpermission');
  Route::post('update-password', 'updatePassword');
  Route::get('users-list','users');
});

Route::controller(DashboardController::class)->group(function () {
  Route::get('purchase-request-count', 'getPurchaseRequestCount');
  Route::get('canvass-count', 'getCanvasCount');
  Route::get('purchase-order-count', 'getPurchaseOrderCount');
  Route::get('department-cost', 'getDepartmentCost');
  Route::get('top-items', 'getTopItems');
});

Route::controller(PurchaseRequestController::class)->group(function () {
  Route::get('purchase-request', [PurchaseRequestController::class, 'index']);
  Route::put('restore-purchase-request/{id}', [PurchaseRequestController::class, 'restorePR']);
  Route::get('purchase-request/{id}', [PurchaseRequestController::class, 'show']);
  Route::put('purchase-request/void/{id}', [PurchaseRequestController::class, 'voidPR']);
  Route::post('purchase-request', [PurchaseRequestController::class, 'store']);
  Route::post('purchase-request/{id}', [PurchaseRequestController::class, 'update']);
  Route::post('purchase-request-items', [PurchaseRequestController::class, 'approveItems']);
  Route::delete('purchase-request/{id}', [PurchaseRequestController::class, 'destroy']);
  Route::delete('remove-item/{id}', [PurchaseRequestController::class, 'removeItem']);
  Route::post('update-item-attachment/{id}', [PurchaseRequestController::class, 'updateItemAttachment']);
  Route::get('get-all-pr', [PurchaseRequestController::class, 'allPR']);
});

Route::controller(CanvasController::class)->group(function () {
  Route::get('canvas', 'index');
  Route::get('count-for-po', 'countForPO');
  Route::post('canvas', 'store');
  Route::post('add-free-goods', 'store');
  Route::delete('canvas/{id}', 'destroy');
  Route::put('update-isrecommended/{id}', 'updateIsRecommended');
  Route::post('submit-canvas', 'submitCanvasItem');
  Route::post('approve-canvas', 'approveCanvasItem');
  Route::post('reconsider-canvas', 'reconsiderCanvas');
  
});

Route::controller(PurchaseOrderController::class)->group(function () {
  Route::get('purchase-orders-counts', 'getCount');
  Route::get('purchase-orders', 'index');
  Route::get('purchase-order/{id}', 'show');
  Route::post('purchase-order', 'store');
  Route::post('purchase-order-reconsider', 'reconsider');
  Route::get('purchase-order-by-number', 'getByNumber');
  Route::post('approve-purchase-order', 'approve');
});

Route::controller(BatchController::class)->group(function () {
  Route::get('item/batch', 'getItemBatchs');
  Route::get('item/models', 'getItemModels');
  Route::post('models', 'storeModel');
  Route::post('batch', 'store');
  Route::put('batch', 'update');
  Route::get('check-batch', 'checkAvailability');
});
Route::controller(InventoryTransactionController::class)->group(function () {
  Route::get('item-transaction', 'index');
  Route::post('reorder-item', 'reorderitem');
});


Route::controller(DeliveryController::class)->group(function () {
  Route::get('deliveries', 'index');
  Route::post('deliveries', 'store');
  Route::put('deliveries', 'update');
  Route::get('delivery/{id}', 'show');
  Route::get('delivery', 'show');
  Route::get('warehouse-deliveries/{id}', 'warehouseDelivery');

});

Route::controller(ConsignmentDeliveryController::class)->group(function () {
  Route::get('consignments', 'index');
  Route::get('audit-consignments', 'auditconsignment');
  Route::get('audited-consignments', 'auditedconsignment');
  Route::post('consignments', 'store');
  Route::get('get-consignment', 'list');
  Route::get('get-purchase-order-consignment', 'consignment_puchase_order');
  
  Route::put('consignments/{id}', 'update');
  Route::post('update-po-consignments', 'updatePOconsignment');
  Route::post('consignment-pr', 'createConsignmentPr');
});

Route::controller(ExpenseController::class)->group(function () {
  Route::get('expense-requisitions', 'index');
  Route::post('expense-requisition', 'store');
});

Route::controller(StockTransferController::class)->group(function () {
  Route::put('stock-transfer-approved/{stock_transfer}', 'receiveTransfer');
  Route::get('stock-transfer', 'index');
  Route::post('stock-transfer', 'store');
});

Route::controller(StockRequisitionController::class)->group(function () {
  Route::get('stock-requisition/release-count', 'releaseCount');
  Route::get('stock-requisition/receive-count', 'receiveCount');
  // Route::put('stock-requisition-approved/{stock_requisition}', 'receiveTransfer');
  Route::get('stock-requisitions', 'index');
  Route::post('stock-requisition', 'store');
  Route::put('stock-release/{stock_requisition}', 'releaseStock');
  Route::put('stock-receive/{stock_requisition}', 'receiveTransfer');
  Route::put('stock-approve/{stock_requisition}', 'approve');
  Route::get('stock-requisition/{stock_requisition}', 'show');
  Route::put('stock-requisition/{stock_requisition}', 'update');
  Route::delete('stock-requisition/{id}', 'destroy');
});

Route::controller(AuditController::class)->group(function () {
  Route::get('audits', 'index');
  Route::get('get-audit', 'index');
  Route::post('audit', 'store');
  Route::put('audit/{audit}', 'update');
  Route::put('audit-consignment/{audit}', 'updateConsignment');
  Route::post('audit-consignment', 'storeConsignment');
  
  // Route::post('stock-requisition', 'store');
});

Route::controller(ExportDataController::class)->group(function () {
  Route::post('export-data', 'exportData');
  // Route::post('stock-requisition', 'store');
});

Route::controller(AuditTrailController::class)->group(function () {
  Route::get('audit-trails', 'index');
  Route::post('audit-trail/import-top-consume', 'store');
  // Route::post('stock-requisition', 'store');
});



Route::controller(ManualUpdateController::class)->group(function () {
  Route::post('update-pr-details', 'update_purchaserequest');
  Route::post('update-pr-canvas-details', 'update_purchasecanvass');
});





Route::controller(InventoryStocksAlertController::class)->group(function () {
  Route::get('inventory-alert', 'index');  
  Route::get('/sales-per-vendor', 'GenerateSalesPerVendor');
});



Route::controller(PurchaseReturnController::class)->group(function () {
  Route::get('purchase-returned', 'index');  
  Route::get('get-purchase-items', 'list');  
  Route::post('save-returned-purchased', 'store');  
 
});

Route::controller(PurchaseSubsidiaryReportController::class)->group(function () {
  Route::post('subsidiary-report', 'allsupplier');  
});

Route::controller(PriceListController::class)->group(function () {
  Route::post('price-list-report', 'allPriceList');  
 
});


