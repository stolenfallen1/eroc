<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\MMIS\PurchaseReturnController;
use App\Http\Controllers\MMIS\InventoryStocksAlertController;

Route::get('/expired-items/{branch_id}/{warehouse_id}', [InventoryStocksAlertController::class,'PrintExpiredItems']);
Route::get('/expired-items-within-14days/{branch_id}/{warehouse_id}', [InventoryStocksAlertController::class,'PrintExpiredItems14Days']);
Route::get('/reorder-stocks/{branch_id}/{warehouse_id}', [InventoryStocksAlertController::class,'PrintReOrder']);
Route::get('/sales-per-vendor-pdf', [InventoryStocksAlertController::class,'PrintPDFSalesPerVendor']);
Route::get('/sales-per-vendor-excel', [InventoryStocksAlertController::class,'PrintExcelSalesPerVendor']);
Route::get('/returned-items/{branch_id}/{rid}', [PurchaseReturnController::class,'PrintReturnedItems']);




