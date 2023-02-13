<?php

use App\Http\Controllers\BuildFile\CategoryController;
use Illuminate\Support\Facades\Route;

Route::controller(CategoryController::class)->group(function () {
  Route::get('categories', 'getAllCategory');
  Route::get('sub-categories', 'getAllSubCategories');
  Route::get('classifications', 'getAllClassifications');
});