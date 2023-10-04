<?php

namespace App\Http\Controllers\Schedules;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Schedules\ORRoomsModel;
use App\Models\Schedules\ORNursesModel;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\Schedules\ORCaseTypeModel;
use App\Models\Schedules\ORSchedulesModel;
use App\Models\BuildFile\Hospital\Schedules;
use App\Models\Schedules\ORRoomTimeSlotModel;
use App\Models\Schedules\ORCirculatingNursesModel;
use App\Models\Schedules\ORRoomTimSlotTransactionModel;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;

class ORSchedulesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data =  ORSchedulesModel::get();
        return response()->json($data, 200);
    }

    public function getdoctor()
    {
        $data['Surgeons'] = Doctor::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        $data['Anethesia'] = Doctor::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }

    public function getORCirculatingNurses()
    {
        $data = ORCirculatingNursesModel::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }

    public function getORCategory()
    {
        $data = OperatingRoomCategory::where('isactive', '1')->get();
        return response()->json($data, 200);
    }

    public function getORRooms()
    {
        $data = ORRoomsModel::where('isactive', '1')->get();
        return response()->json($data, 200);
    }

    public function getORCaseTypes()
    {
        $data = ORCaseTypeModel::where('isactive', '1')->get();
        return response()->json($data, 200);
    }



    public function getORRoomTimeSlot()
    {
        $data = ORRoomTimeSlotModel::where('isactive', '1')->get();
        return response()->json($data, 200);
    }

    public function checkRoomAvailability()
    {
        $ORRoomTimSlotTransactionModel = ORRoomTimSlotTransactionModel::where('room_id', Request()->room_id);

        if (Request()->or_date) {
            $ORRoomTimSlotTransactionModel->where('timeslot_date', Request()->or_date);
        }
        $data = $ORRoomTimSlotTransactionModel->get();
        return response()->json($data, 200);
    }




    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //return Request()->all();
        $schedule = ORSchedulesModel::create([
            'orcase_no' => $request->payload['caseno'] ?? '',

            'case_id' => $request->payload['caseno_reg'] ?? '',
            'patient_id' => $request->payload['patientid_reg'] ?? '',
            'room_id' => $request->payload['room_id'] ?? '',


            'schedule_date' => $request->payload['scheduleddate'] ?? '',
            'schedule_time' => $request->payload['radioScheduledTime'] ?? '',
            'sex' => $request->payload['sexes'] ?? '',
            'birthdate' => $request->payload['birthdate'] ?? '',
            'age' => $request->payload['age'] ?? '',

            'category_id' => $request->payload['ORCategory']['id'] ?? '',
            'procedure_name' => $request->payload['procedurename'] ?? '',


        ]);
        $schedule->scheduleSurgeons()->create([
            'doctor_id' => $request->payload['surgeon']['id'] ?? '',
            'lastname' => $request->payload['surgeon']['lastname'] ?? '',
            'firstname' => $request->payload['surgeon']['firstname'] ?? '',
            'middlename' => $request->payload['surgeon']['middlename'] ?? '',
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function show(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function edit(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }
}
