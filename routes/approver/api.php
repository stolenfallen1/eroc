<?php

use App\Http\Controllers\Approver\StatusController;
use Illuminate\Support\Facades\Route;

Route::controller(StatusController::class)->group(function () {
  Route::get('status', 'index');
});
