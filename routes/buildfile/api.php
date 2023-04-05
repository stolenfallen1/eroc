<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BuildFile\ItemController;
use App\Http\Controllers\BuildFile\UnitController;
use App\Http\Controllers\BuildFile\BrandController;
use App\Http\Controllers\BuildFile\CategoryController;
use App\Http\Controllers\BuildFile\PriorityController;
use App\Http\Controllers\BuildFile\SupplierController;
use App\Http\Controllers\BuildFile\AntibioticController;
use App\Http\Controllers\BuildFile\BranchController;
use App\Http\Controllers\BuildFile\DepartmentController;
use App\Http\Controllers\BuildFile\DrugAdministrationController;
use App\Http\Controllers\BuildFile\GenericNameController;
use App\Http\Controllers\BuildFile\SystemSettingController;
use App\Http\Controllers\BuildFile\TherapeuticClassController;
use App\Http\Controllers\BuildFile\VendorController;

Route::controller(CategoryController::class)->group(function () {
  Route::get('categories', 'getAllCategory');
  Route::get('sub-categories', 'getAllSubCategories');
  Route::get('classifications', 'getAllClassifications');
  Route::get('supplier-categories', 'getAllSupplierCategories');
  Route::get('supplier-terms', 'getAllSupplierTerms');
});

Route::controller(VendorController::class)->group(function () {
  Route::get('vendors', 'index');
  Route::post('vendors', 'store');
  Route::put('vendors/{vendor}', 'update');
  Route::delete('vendors/{vendor}', 'destroy');
});

Route::controller(ItemController::class)->group(function () {
  Route::get('items', 'searchItem');
  Route::get('items-group', 'getItemGroup');
});

Route::controller(UnitController::class)->group(function () {
  Route::get('units', 'index');
  Route::get('currencies', 'getCurrencies');
});

Route::controller(PriorityController::class)->group(function () {
  Route::get('priorities', 'index');
});

Route::controller(SystemSettingController::class)->group(function () {
  Route::get('system-settings', 'getPRSNSequences');
});

Route::controller(BrandController::class)->group(function () {
  Route::get('brand', 'index');
});

Route::controller(AntibioticController::class)->group(function () {
  Route::get('antibiotic', 'index');
});

Route::controller(GenericNameController::class)->group(function () {
  Route::get('generic-name', 'index');
});

Route::controller(DrugAdministrationController::class)->group(function () {
  Route::get('drug-administration', 'index');
});

Route::controller(TherapeuticClassController::class)->group(function () {
  Route::get('therapeutic-class', 'index');
});

Route::controller(BranchController::class)->group(function () {
  Route::get('branches', 'index');
});

Route::controller(DepartmentController::class)->group(function () {
  Route::get('departments', 'index');
});