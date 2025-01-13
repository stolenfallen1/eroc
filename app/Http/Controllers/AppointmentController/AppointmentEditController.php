<?php

namespace App\Http\Controllers\AppointmentController;

use App\Helpers\Appointment\AppointmentEditHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentEditController extends Controller
{
    protected $AppointmentEditHelper;

    public function __construct()
    {
        $this->AppointmentEditHelper = new AppointmentEditHelper();
    }


    public function editPatient(Request $request)
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        
        try{
        $payload = $request->all();
        $seq = 1;
        $data = $this->AppointmentEditHelper->processEditController($payload,$seq);
        // DB::connection('sqlsrv_patient_data')->commit;
        return response()->json(['message' => $data],201);
        }catch(\Exception $e)
        {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json(['error' => $e->getMessage(), 'message' => 'Edit Patient Details Failed'], 500);
        }
    }



}
