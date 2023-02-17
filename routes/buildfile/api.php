<?php

use App\Http\Controllers\BuildFile\CategoryController;
use App\Http\Controllers\BuildFile\ItemController;
use App\Http\Controllers\BuildFile\PriorityController;
use App\Http\Controllers\BuildFile\UnitController;
use Illuminate\Support\Facades\Route;

Route::controller(CategoryController::class)->group(function () {
  Route::get('categories', 'getAllCategory');
  Route::get('sub-categories', 'getAllSubCategories');
  Route::get('classifications', 'getAllClassifications');
});

Route::controller(ItemController::class)->group(function () {
  Route::get('items', 'searchItem');
});

Route::controller(UnitController::class)->group(function () {
  Route::get('units', 'index');
});

Route::controller(PriorityController::class)->group(function () {
  Route::get('priorities', 'index');
});