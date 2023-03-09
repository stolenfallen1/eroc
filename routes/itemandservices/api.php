<?php

use App\Http\Controllers\BuildFile\ItemandServicesController;
use App\Http\Controllers\BuildFile\ItemController;
use App\Http\Controllers\BuildFile\PriorityController;
use App\Http\Controllers\BuildFile\SystemSettingController;
use App\Http\Controllers\BuildFile\UnitController;
use Illuminate\Support\Facades\Route;

Route::controller(ItemandServicesController::class)->group(function () {
  Route::get('items-and-services', 'index');
  Route::post('items-and-services', 'store');
  Route::post('items-and-services/{id}', 'update');
  Route::delete('items-and-services/{id}', 'destroy');
});

