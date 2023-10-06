<?php

use App\Http\Controllers\MMIS\AuditController;
use App\Http\Controllers\MMIS\AuditTrailController;
use App\Http\Controllers\MMIS\BatchController;
use App\Http\Controllers\MMIS\CanvasController;
use App\Http\Controllers\MMIS\DeliveryController;
use App\Http\Controllers\MMIS\ExportDataController;
use App\Http\Controllers\MMIS\PurchaseOrderController;
use App\Http\Controllers\MMIS\PurchaseRequestController;
use App\Http\Controllers\MMIS\StockRequisitionController;
use App\Http\Controllers\MMIS\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::controller(UserController::class)->group(function () {
  Route::get('getpermission', 'getpermission');
  Route::post('verify-passcode', 'getpermission');
});

Route::controller(PurchaseRequestController::class)->group(function () {
  Route::get('purchase-request', [PurchaseRequestController::class, 'index']);
  Route::get('purchase-request/{id}', [PurchaseRequestController::class, 'show']);
  Route::post('purchase-request', [PurchaseRequestController::class, 'store']);
  Route::post('purchase-request/{id}', [PurchaseRequestController::class, 'update']);
  Route::post('purchase-request-items', [PurchaseRequestController::class, 'approveItems']);
  Route::delete('purchase-request/{id}', [PurchaseRequestController::class, 'destroy']);
  Route::delete('remove-item/{id}', [PurchaseRequestController::class, 'removeItem']);
  Route::post('update-item-attachment/{id}', [PurchaseRequestController::class, 'updateItemAttachment']);
});

Route::controller(CanvasController::class)->group(function () {
  Route::get('canvas', 'index');
  Route::get('count-for-po', 'countForPO');
  Route::post('canvas', 'store');
  Route::delete('canvas/{id}', 'destroy');
  Route::put('update-isrecommended/{id}', 'updateIsRecommended');
  Route::post('submit-canvas', 'submitCanvasItem');
  Route::post('approve-canvas', 'approveCanvasItem');
});

Route::controller(PurchaseOrderController::class)->group(function () {
  Route::get('purchase-orders', 'index');
  Route::get('purchase-order/{id}', 'show');
  Route::post('purchase-order', 'store');
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

Route::controller(DeliveryController::class)->group(function () {
  Route::get('deliveries', 'index');
  Route::post('deliveries', 'store');
  Route::post('consignments', 'storeConsignment');
  Route::put('deliveries', 'update');
  Route::get('delivery/{id}', 'show');
  Route::get('warehouse-deliveries/{id}', 'warehouseDelivery');
});

Route::controller(StockTransferController::class)->group(function () {
  Route::put('stock-transfer-approved/{stock_transfer}', 'receiveTransfer');
  Route::get('stock-transfer', 'index');
  Route::post('stock-transfer', 'store');
});

Route::controller(StockRequisitionController::class)->group(function () {
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
  Route::post('audit', 'store');
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

