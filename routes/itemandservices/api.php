<?php

use App\Http\Controllers\BuildFile\ItemandServicesController;
use App\Http\Controllers\BuildFile\ItemController;
use App\Http\Controllers\BuildFile\PriorityController;
use App\Http\Controllers\BuildFile\SystemSettingController;
use App\Http\Controllers\BuildFile\UnitController;
use Illuminate\Support\Facades\Route;

Route::controller(ItemandServicesController::class)->group(function () {
  Route::get('items-and-services', 'index');
  Route::get('item-by-location', 'indexLocation');
  Route::post('items-and-services', 'store');
  Route::post('items-and-services/list-cost', 'updateListCost');
  Route::post('items-and-services/{id}', 'update');
  Route::post('check-duplication-name', 'checkNameDuplication');
  Route::post('add-to-location/{id}', 'addToLocation');
  Route::post('update-to-location/{id}', 'updateToLocation');
  Route::post('items-and-services/physical-count/{warehouse_item}', 'updatePhysicalCount');
  Route::delete('items-and-services/{id}', 'destroy');
});

