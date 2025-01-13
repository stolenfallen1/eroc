    <?php


    use App\Http\Controllers\AppointmentController\AppointmentEditController;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AppointmentController\AppointmentsController;
    use App\Http\Controllers\AppointmentController\FieldDetails;
    use App\Http\Controllers\AppointmentController\AuthController;
    use App\Http\Controllers\AppointmentController\FetchAppointmentController;
    use App\Http\Controllers\AppointmentController\RegistryController;

    Route::post('/authLogin/appointment', [AuthController::class, 'Login']);
    Route::post('/temporary/store', [AppointmentsController::class, 'store']);
    Route::controller(FieldDetails::class)->group(function () {
        // Route::get('/getFormDetails', 'getFormDetails');
        Route::get('/getRegions', 'getRegion');
        Route::get('/getProvinces/{region_code}', 'getProvinces');
        Route::post('/getBarangay/{code}', 'getBarangay');
        Route::get('/getFormDetails', 'getCivil');
        Route::post('/test/aki08', 'test');
    });

    Route::middleware('auth.user')->group(function () {
        Route::post('authLogout/appointment', [AuthController::class, 'logout']);
        Route::controller(FieldDetails::class)->group(function () {
            Route::get('getDoctors', 'getDoctors');
            Route::get('getAppointmentCenter', 'getAppointmentCenter');
            Route::match(['post', 'get'], 'getProcedure/{trans_code}', 'getProcedure');
        });
        Route::controller(RegistryController::class)->group(function () {
            Route::post('confirm/appointment', 'AppointmentRegistry');
            Route::post('checkIn/appointment', 'AppointmentCheckIn');
            Route::post('make-done/appointment', 'DoneAppointment');
            Route::post('getSlot', 'seclectedSlot');
            Route::post('patient/reminder', 'Reminder');
            Route::post('appointment/reschedule', 'RescheduleAppointment');
            Route::post('appointment/cancel', 'CancelAppointment');
        });
        Route::controller(FetchAppointmentController::class)->group(function () {
            Route::get('getTemporary/patient', 'index');
            Route::post('getAppointment/cashier', 'getAppointmentCashier');
            Route::get('getAppointment/Patient', 'getAppointmentPatient');
            Route::get('getDone/Appointment/Patient', 'getDoneAppointmentPatient');
            Route::post('getAppointment/Recieptionist', 'getAppointmentRecieptionist');
            Route::post('getAppointment/checkIn/Recieptionist', 'getAppointmentCheckInRecieptionist');
            Route::post('getDone/Appointment/checkIn/Recieptionist', 'getDoneAppointmentCheckInRecieptionist');
        });
        Route::controller(AppointmentsController::class)->group(function () {
            Route::get('/user/token', 'getCurrentUserToken');
            Route::post('store/patient/appointment', 'store_appointment');
            Route::post('payment/appointment', 'store_payment');
            Route::get('appointment/patient/details', 'getUserDetails');
        });
        Route::controller(AppointmentEditController::class)->group(function () {
            Route::post('edit/patient/appointment', 'editPatient');

        });
    });
