<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManager\UserManagerController;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('login', ['uses' => 'TCG\\Voyager\\Http\\Controllers\\VoyagerAuthController@login',     'as' => 'login']);

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});


Route::group(['middleware' => 'admin.user'], function () {
    require_once __DIR__ . './mmis/mmismainroute.php';
    Route::get('user-details', [AuthController::class, 'userDetails']);
    Route::get('/{any}', function () {
        return view('layouts.main');
    })->where('any', '.*');
});
