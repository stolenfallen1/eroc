<?php

namespace App\Http\Controllers\Schedules;

use App\Helpers\Scheduling\SeriesNo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Schedules\ORRoomsModel;
use App\Models\Schedules\ORNursesModel;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\Schedules\ORCaseTypeModel;
use App\Models\Schedules\ORResidentModel;
use App\Models\Schedules\ORSchedulesModel;
use App\Models\BuildFile\Hospital\Schedules;
use App\Models\Schedules\ORRoomTimeSlotModel;
use App\Models\Schedules\ORCirculatingNursesModel;
use App\Models\Schedules\ORRoomTimSlotTransactionModel;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;
use App\Models\Schedules\ORScrubNursesModel;
use App\Helpers\Scheduling\OperatingRoomSchedule;

use Illuminate\Support\Facades\Auth;
use DB;
class ORSchedulesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function confirmedchedules()
    {
        $data =  (new OperatingRoomSchedule())->confirmed_scheduled();
        return response()->json($data, 200);
    }

     public function pendingschedules()
    {
        $data =  (new OperatingRoomSchedule())->pending_scheduled();
        return response()->json($data, 200);
    }


    public function getdoctor()
    {
        $data['Surgeons'] = Doctor::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        $data['Anethesia'] = Doctor::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }

     public function getResident()
    {
        $data = ORCirculatingNursesModel::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }

    public function getORCirculatingNurses()
    {
        $data = ORCirculatingNursesModel::where('isactive', '1')->orderBy('lastname', 'asc')->get();
        return response()->json($data, 200);
    }
    public function getORScrubNurses()
    {
        $data = ORScrubNursesModel::where('isactive', '1')->orderBy('lastname', 'asc')->get();
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
        DB::connection('sqlsrv_schedules')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();

        try{
            $or_sequenceno = (new SeriesNo())->get_sequence('OR');
            $generat_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);

            $schedule = ORSchedulesModel::create([
                'orcase_no' => $generat_or_series,
                'case_id' => $request->payload['caseno_reg'] ?? '',
                'patient_id' => $request->payload['patientid_reg'] ?? '',
                'room_id' => $request->payload['room_id'] ?? '',
                'or_room_id' => $request->payload['or_room_id'] ?? '',
                'station_id' => $request->payload['station_id'] ?? '',
                'createdby' => Auth::user()->idnumber,
                'schedule_date' => $request->payload['scheduleddate'] ?? '',
                'sex' => $request->payload['sexes'] ?? '',
                'birthdate' => $request->payload['birthdate'] ?? '',
                'age' => $request->payload['age'] ?? '',
                'case_type_id' => $request->payload['case_type_id'] ?? '',
                'category_id' => $request->payload['ORCategory'] ?? '',
                'procedure_name' => $request->payload['procedurename'] ?? '',
            ]);
            $schedule->scheduleSurgeons()->create([
                'branch_id' => Auth::user()->branch_id,
                'doctor_id' => $request->payload['surgeon']['id'] ?? '',
                'lastname' => $request->payload['surgeon']['lastname'] ?? '',
                'firstname' => $request->payload['surgeon']['firstname'] ?? '',
                'middlename' => $request->payload['surgeon']['middlename'] ?? '',
                'createdby' => Auth::user()->idnumber,
            ]);
            
            $schedule->scheduleAnesthesia()->create([
              'branch_id' => Auth::user()->branch_id,
              'doctor_id' => $request->payload['surgeon']['id'] ?? '',
              'lastname' => $request->payload['surgeon']['lastname'] ?? '',
              'firstname' => $request->payload['surgeon']['firstname'] ?? '',
              'middlename' => $request->payload['surgeon']['middlename'] ?? '',
              'createdby' => Auth::user()->idnumber,
            ]);

            $schedule->scheduledRoomSlot()->create([
                'timeslot_date' => $request->payload['scheduleddate'] ?? '',
                'timeslot_id' => $request->payload['radioScheduledTime'] ?? '',
                'room_id' => $request->payload['or_room_id']?? '',
            ]);

            $schedule->scheduledResident()->create([
                'doctor_name' =>$request->payload['ORResident']['circulatingnurses'] ?? '',
                'createdby' => Auth::user()->idnumber,
            ]);

            $schedule->scheduledCirculatingNurses()->create([
                'branch_id' => Auth::user()->branch_id,
                'firstname' => $request->payload['ORCirculatingNurses']['firstname'] ?? '',
                'lastname' => $request->payload['ORCirculatingNurses']['lastname'] ?? '',
                'middlename' => $request->payload['ORCirculatingNurses']['middlename'] ?? '',
                'specialty_id' =>1,
            ]);

            if(count($request->payload['SNurse']) > 0){
                foreach($request->payload['SNurse'] as $row) {
                    $schedule->scheduledScrubNurses()->create([
                        'branch_id' => Auth::user()->branch_id,
                        'firstname' => $row['firstname'] ?? '',
                        'lastname' => $row['lastname'] ?? '',
                        'middlename' => $row['middlename'] ?? '',
                        'specialty_id' => 2,
                    ]);
                }
            }
            
            $seriesno = $or_sequenceno->seq_no + 1;
            $or_sequenceno->update([
                'seq_no' => $seriesno,
                'recent_generated' => $generat_or_series,
            ]);

            DB::connection('sqlsrv_schedules')->commit();
            DB::connection('sqlsrv')->commit();

            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_schedules')->rollback();
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);

        }
       

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
