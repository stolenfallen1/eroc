<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'mmis'], function () {
    Route::get('/{any}', function () {
        return view('layouts.mmis');
    })->where('any', '.*');
});