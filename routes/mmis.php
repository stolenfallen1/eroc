<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('layouts.mmis');
})->where('any', '.*');
