<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\OldMMIS\Branch;
use App\Models\BuildFile\Warehouses;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\POS\Report_ZController;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Http\Controllers\HIS\services\EmergencyRegistrationController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\HIS\CaseIndicatorController;
use App\Http\Controllers\HIS\his_functions\SOAController;


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


Route::get('view-image', [AppointmentController::class, 'image']);
Route::controller(Report_ZController::class)->group(function () {
    Route::get('get-z-report-all-shift', 'generate_z_report');
});

// Route::get('login', ['uses' => 'TCG\\Voyager\\Http\\Controllers\\VoyagerAuthController@login',     'as' => 'login']);
Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});

require_once('inventory_printout.php');
require_once('mmis_printout.php');
Route::group(['middleware' => 'admin.user'], function () {
    require_once('mmis/mmismainroute.php');
    Route::get('user-details', [AuthController::class, 'userDetails']);
});

Route::get('/fetch-data', [EmergencyRegistrationController::class, 'fetchData'])->where('id', '[0-9]+');
Route::get('/get-indicator', [CaseIndicatorController::class, 'list']);
Route::get('/get-emergency', [EmergencyRegistrationController::class, 'index']);
Route::get('/generate-statement', [SOAController::class, 'createStatmentOfAccount']);
Route::get('/generate-statement-summary', [SOAController::class, 'createStatmentOfAccountSummary']);
