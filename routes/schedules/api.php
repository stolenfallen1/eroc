<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Schedules\ORRoomsController;
use App\Http\Controllers\Schedules\ORCaseTypeController;
use App\Http\Controllers\Schedules\ORCategoryController;
use App\Http\Controllers\Schedules\ORSchedulesController;
use App\Http\Controllers\Schedules\ORStaffNurseController;
use App\Http\Controllers\Schedules\ORNursePositionController;
use App\Http\Controllers\Schedules\ORDoctorSpecialtyController;
use App\Http\Controllers\Schedules\ORSchedulePatientController;
use App\Http\Controllers\Schedules\ORDoctorController;


Route::get('/registration', [ORSchedulesController::class, 'registration']);

Route::get('/getScheduledQueue', [ORSchedulesController::class, 'ORScheduledQueue']);
Route::get('/getOPTHAScheduledQueue', [ORSchedulesController::class, 'OROPTHAScheduledQueue']);
Route::get('/scheduling-search-patient', [ORSchedulePatientController::class, 'searchschedulingPatientData']);
Route::get('/getOperationRoomConfirmedSchedules', [ORSchedulesController::class, 'confirmedchedules']);
Route::get('/getOperationRoomPendingSchedules', [ORSchedulesController::class, 'pendingschedules']);
Route::get('/getOperationRoomSchedulesStatus', [ORSchedulesController::class, 'OperatingroomSchedulesStatus']);
Route::get('/getORSchedules', [ORSchedulesController::class, 'getORSchedules']);
Route::get('/getORPatientDetails', [ORSchedulesController::class, 'getORPatientDetails']);
Route::resource('or-categories', ORCategoryController::class);
Route::resource('or-case-types', ORCaseTypeController::class);
Route::resource('or-doctor-specialties', ORDoctorSpecialtyController::class);
Route::resource('or-nurse-positions', ORNursePositionController::class);
Route::resource('or-rooms', ORRoomsController::class);
Route::resource('or-staff-nurse', ORStaffNurseController::class);
Route::resource('or-doctors', ORDoctorController::class);




Route::get('/schedules', [ORSchedulesController::class, 'index']);
Route::get('/getdoctor', [ORSchedulesController::class, 'getdoctor']);
Route::get('/getResident', [ORSchedulesController::class, 'getResident']);
Route::get('/getORCategory', [ORSchedulesController::class, 'getORCategory']);
Route::get('/getORStatus', [ORSchedulesController::class, 'getORStatus']);
Route::get('/searchPatientData', [ORSchedulePatientController::class, 'searchPatientData']);
Route::get('/getORRooms', [ORSchedulesController::class, 'getORRooms']);
Route::get('/getORRoomTimeSlot', [ORSchedulesController::class, 'getORRoomTimeSlot']);
Route::get('/getORCirculatingNurses', [ORSchedulesController::class, 'getORCirculatingNurses']);
Route::get('/getORScrubNurses', [ORSchedulesController::class, 'getORScrubNurses']);
Route::get('/getORCaseTypes', [ORSchedulesController::class, 'getORCaseTypes']);
Route::get('/checkRoomAvailability', [ORSchedulesController::class, 'checkRoomAvailability']);
Route::post('/submitschedule', [ORSchedulesController::class, 'store']);
Route::post('/ProccedWaitingRoom', [ORSchedulesController::class, 'ProccedWaitingRoom']);
Route::post('/update-seleted-timeslot', [ORSchedulesController::class, 'updateseletedtimeslot']);
Route::get('/getORProcedures', [ORSchedulesController::class, 'getORProcedures']);

Route::post('/submit-procedure', [ORSchedulesController::class, 'submitprocedure']);
Route::post('/book-appointment', [ORSchedulesController::class, 'bookappointment']);


