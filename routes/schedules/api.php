<?php

use App\Http\Controllers\Schedules\ORSchedulesController;
use Illuminate\Support\Facades\Route;




Route::get('/getOperationRoomConfirmedSchedules', [ORSchedulesController::class, 'confirmedchedules']);
Route::get('/getOperationRoomPendingSchedules', [ORSchedulesController::class, 'pendingschedules']);

Route::get('/schedules', [ORSchedulesController::class, 'index']);
Route::get('/getdoctor', [ORSchedulesController::class, 'getdoctor']);
Route::get('/getResident', [ORSchedulesController::class, 'getResident']);
Route::get('/getORCategory', [ORSchedulesController::class, 'getORCategory']);
Route::get('/searchPatientData', [ORSchedulePatientController::class, 'searchPatientData']);
Route::get('/getORRooms', [ORSchedulesController::class, 'getORRooms']);
Route::get('/getORRoomTimeSlot', [ORSchedulesController::class, 'getORRoomTimeSlot']);
Route::get('/getORCirculatingNurses', [ORSchedulesController::class, 'getORCirculatingNurses']);
Route::get('/getORScrubNurses', [ORSchedulesController::class, 'getORScrubNurses']);
Route::get('/getORCaseTypes', [ORSchedulesController::class, 'getORCaseTypes']);
Route::get('/checkRoomAvailability', [ORSchedulesController::class, 'checkRoomAvailability']);
Route::post('/submitschedule', [ORSchedulesController::class, 'store']);


// Route::controller(ORSchedulesController::class)->group(function () {
//     Route::get('get-schedules', 'index');
// });
