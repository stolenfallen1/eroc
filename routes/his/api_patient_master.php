<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HIS\MasterPatientController;

Route::get('search-patient-master', [MasterPatientController::class, 'list']);
Route::resource('patient-master', MasterPatientController::class);


