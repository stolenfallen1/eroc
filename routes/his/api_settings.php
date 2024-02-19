<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HIS\SettingController;

Route::controller(SettingController::class)->group(function () {
  Route::post('settings', 'store');
});