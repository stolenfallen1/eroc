<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Appointment\PatientAppointmentController;
use App\Http\Controllers\Appointment\AppointmentRegistrationController;
use App\Http\Controllers\AuthAppointmentController;

Route::get('/get-zipcodes', [PatientAppointmentController::class, 'getZipCode']);
Route::post('/get-msc-getbarangay', [PatientAppointmentController::class, 'getBarangay']);
Route::post('/get-msc-nationality', [PatientAppointmentController::class, 'getnationality']);

Route::post('appointment-login', [AuthAppointmentController::class, 'login']);
Route::post('register-account', [PatientAppointmentController::class, 'registration']);
Route::post('appointment-registration', [PatientAppointmentController::class, 'registration']);
Route::get('centers', [AppointmentController::class, 'centers']);
Route::middleware('auth.patient')->group(function () {
    
    Route::get('get-patients', [AppointmentController::class, 'appointmentIndex']);
    Route::get('get-users', [AppointmentController::class, 'users']);
    Route::post('store-user', [AppointmentController::class, 'storeUser']);
    Route::post('confirmed-appointment', [AppointmentRegistrationController::class, 'register']);
    Route::post('checked-in', [AppointmentController::class, 'checkedIn']);
    Route::post('transfer', [AppointmentController::class, 'transfer']);
    Route::get('get-schedules', [AppointmentController::class, 'getschedules']);
    Route::get('manage-slots', [AppointmentController::class, 'getslots']);
    Route::get('appointment-type', [AppointmentController::class, 'getAppointmentType']);
    Route::post('save-slot', [AppointmentController::class, 'saveSlot']);
    Route::get('reserved-slot', [AppointmentController::class, 'reserveSlots']);
    Route::post('save-reserved-slot', [AppointmentController::class, 'saveReserveSlots']);
    Route::get('check-slot', [PatientAppointmentController::class, 'checkSlots']);
    
    Route::get('get-centers', [AppointmentController::class, 'getCenters']);
    Route::post('store-center', [AppointmentController::class, 'storeCenter']);
    Route::post('store-section', [AppointmentController::class, 'storeSection']);
    Route::post('send-message', [AppointmentController::class, 'sendMessage']);
    
    Route::get('get-branchs', [AppointmentController::class, 'branches']);
    // Route::get('get-centers', [AppointmentController::class, 'centers']);

    Route::get('appointment-user-details', [AuthAppointmentController::class, 'getdetails']);
    Route::post('appointment-logout', [AuthAppointmentController::class, 'logout']);
    Route::get('appointment-refresh', [AuthAppointmentController::class, 'refreshToken']);

    Route::get('procedures', [PatientAppointmentController::class, 'procedures']);
    Route::get('appointment-doctors', [PatientAppointmentController::class, 'doctors']);
    Route::post('submit-appointment', [PatientAppointmentController::class, 'submitpayment']);
    Route::get('slots', [PatientAppointmentController::class, 'slots']);

    // patient 
    Route::get('my-appointments', [PatientAppointmentController::class, 'index']);
});



